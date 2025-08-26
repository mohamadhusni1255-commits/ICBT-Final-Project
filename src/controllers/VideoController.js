const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs').promises;
const { v4: uuidv4 } = require('uuid');
const router = express.Router();

const VideoModel = require('../models/VideoModel');
const LikeModel = require('../models/LikeModel');
const FeedbackSummaryModel = require('../models/FeedbackSummaryModel');
const SupabaseService = require('../services/SupabaseService');
const { requireAuth } = require('../middleware/auth');
const { validateCSRFToken } = require('../middleware/csrf');
const config = require('../config');

// Configure multer for temporary file storage
const upload = multer({
  dest: '/tmp/uploads/',
  limits: {
    fileSize: config.upload.maxFileSize
  },
  fileFilter: (req, file, cb) => {
    // Check file extension
    const ext = path.extname(file.originalname).toLowerCase();
    if (!config.upload.allowedExtensions.includes(ext)) {
      return cb(new Error('Only MP4 files are allowed'));
    }
    
    // Check MIME type
    if (!config.upload.allowedMimeTypes.includes(file.mimetype)) {
      return cb(new Error('Invalid file type'));
    }
    
    cb(null, true);
  }
});

// Upload video endpoint
router.post('/upload', requireAuth, upload.single('video'), async (req, res) => {
  let tempFilePath = null;
  
  try {
    const { title, description, csrf_token } = req.body;
    const uploadedFile = req.file;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!uploadedFile) {
      return res.status(400).json({ error: 'No video file uploaded' });
    }
    
    if (!title) {
      return res.status(400).json({ error: 'Title is required' });
    }
    
    tempFilePath = uploadedFile.path;
    
    // Additional server-side file validation
    const fileBuffer = await fs.readFile(tempFilePath);
    const fileSignature = fileBuffer.slice(0, 12).toString('hex');
    
    // Check MP4 file signature (ftyp box)
    if (!fileSignature.includes('667479706d703') && // mp4
        !fileSignature.includes('66747970697') && // mp4
        !fileSignature.includes('667479704d53')) { // mp4
      await fs.unlink(tempFilePath);
      return res.status(400).json({ error: 'Invalid MP4 file format' });
    }
    
    // Generate unique filename and storage path
    const videoId = uuidv4();
    const fileExtension = path.extname(uploadedFile.originalname);
    const storagePath = `${req.session.userId}/${videoId}${fileExtension}`;
    
    // Upload to Supabase Storage
    const uploadSuccess = await SupabaseService.uploadVideo(tempFilePath, storagePath);
    if (!uploadSuccess) {
      await fs.unlink(tempFilePath);
      return res.status(500).json({ error: 'Failed to upload video to storage' });
    }
    
    // Save video metadata to database
    const video = await VideoModel.create({
      id: videoId,
      title,
      description: description || '',
      storage_path: storagePath,
      uploaded_by: req.session.userId
    });
    
    // Clean up temp file
    await fs.unlink(tempFilePath);
    
    res.status(201).json({
      message: 'Video uploaded successfully',
      video: {
        id: video.id,
        title: video.title,
        description: video.description,
        created_at: video.created_at
      }
    });
    
  } catch (error) {
    console.error('Video upload error:', error);
    
    // Clean up temp file if exists
    if (tempFilePath) {
      try {
        await fs.unlink(tempFilePath);
      } catch (unlinkError) {
        console.error('Failed to cleanup temp file:', unlinkError);
      }
    }
    
    if (error.code === 'LIMIT_FILE_SIZE') {
      return res.status(400).json({ error: 'File size too large. Maximum 50MB allowed.' });
    }
    
    res.status(500).json({ error: 'Video upload failed' });
  }
});

// Get videos list with pagination
router.get('/list', async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || config.pagination.defaultLimit, config.pagination.maxLimit);
    const search = req.query.search || '';
    const category = req.query.category || '';
    
    const offset = (page - 1) * limit;
    
    const { videos, total } = await VideoModel.findAll({
      limit,
      offset,
      search,
      category
    });
    
    // Add like counts and user likes
    const videosWithData = await Promise.all(videos.map(async (video) => {
      const likeCount = await LikeModel.countByVideo(video.id);
      const userLiked = req.session.userId ? 
        await LikeModel.findByUserAndVideo(req.session.userId, video.id) : false;
      
      // Get feedback summary if available
      const summary = await FeedbackSummaryModel.findByVideoId(video.id);
      
      return {
        ...video,
        like_count: likeCount,
        user_liked: !!userLiked,
        feedback_summary: summary
      };
    }));
    
    res.json({
      videos: videosWithData,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get videos error:', error);
    res.status(500).json({ error: 'Failed to fetch videos' });
  }
});

// Get single video with signed URL
router.get('/:id', async (req, res) => {
  try {
    const videoId = req.params.id;
    
    const video = await VideoModel.findById(videoId);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    // Generate signed URL for video playback
    const signedUrl = await SupabaseService.getVideoSignedUrl(video.storage_path);
    if (!signedUrl) {
      return res.status(500).json({ error: 'Failed to generate video URL' });
    }
    
    // Get additional video data
    const likeCount = await LikeModel.countByVideo(videoId);
    const userLiked = req.session.userId ? 
      await LikeModel.findByUserAndVideo(req.session.userId, videoId) : false;
    
    const summary = await FeedbackSummaryModel.findByVideoId(videoId);
    
    res.json({
      video: {
        ...video,
        signed_url: signedUrl,
        like_count: likeCount,
        user_liked: !!userLiked,
        feedback_summary: summary
      }
    });
    
  } catch (error) {
    console.error('Get video error:', error);
    res.status(500).json({ error: 'Failed to fetch video' });
  }
});

// Toggle like on video
router.post('/:id/like', requireAuth, async (req, res) => {
  try {
    const videoId = req.params.id;
    const userId = req.session.userId;
    const { csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    // Check if video exists
    const video = await VideoModel.findById(videoId);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    // Check if already liked
    const existingLike = await LikeModel.findByUserAndVideo(userId, videoId);
    
    let liked;
    if (existingLike) {
      // Remove like
      await LikeModel.delete(existingLike.id);
      liked = false;
    } else {
      // Add like
      await LikeModel.create({
        id: uuidv4(),
        user_id: userId,
        video_id: videoId
      });
      liked = true;
    }
    
    const likeCount = await LikeModel.countByVideo(videoId);
    
    res.json({
      liked,
      like_count: likeCount
    });
    
  } catch (error) {
    console.error('Toggle like error:', error);
    res.status(500).json({ error: 'Failed to toggle like' });
  }
});

// Get user's own videos
router.get('/user/my-videos', requireAuth, async (req, res) => {
  try {
    const userId = req.session.userId;
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || config.pagination.defaultLimit, config.pagination.maxLimit);
    
    const offset = (page - 1) * limit;
    
    const { videos, total } = await VideoModel.findByUser(userId, { limit, offset });
    
    const videosWithData = await Promise.all(videos.map(async (video) => {
      const likeCount = await LikeModel.countByVideo(video.id);
      const summary = await FeedbackSummaryModel.findByVideoId(video.id);
      
      return {
        ...video,
        like_count: likeCount,
        feedback_summary: summary
      };
    }));
    
    res.json({
      videos: videosWithData,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get user videos error:', error);
    res.status(500).json({ error: 'Failed to fetch user videos' });
  }
});

module.exports = router;