const express = require('express');
const { v4: uuidv4 } = require('uuid');
const router = express.Router();

const FeedbackModel = require('../models/FeedbackModel');
const VideoModel = require('../models/VideoModel');
const { requireAuth } = require('../middleware/auth');
const { requireRole } = require('../middleware/roleCheck');
const { validateCSRFToken } = require('../middleware/csrf');

// Submit feedback (judges and admins only)
router.post('/submit', requireAuth, requireRole(['judge', 'admin']), async (req, res) => {
  try {
    const {
      video_id,
      score_voice,
      score_creativity,
      score_presentation,
      comments,
      csrf_token
    } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    // Validate required fields
    if (!video_id || score_voice === undefined || score_creativity === undefined || score_presentation === undefined) {
      return res.status(400).json({ error: 'Video ID and all scores are required' });
    }
    
    // Validate score ranges
    const scores = [score_voice, score_creativity, score_presentation];
    for (const score of scores) {
      if (score < 0 || score > 10 || !Number.isInteger(Number(score))) {
        return res.status(400).json({ error: 'Scores must be integers between 0 and 10' });
      }
    }
    
    // Check if video exists
    const video = await VideoModel.findById(video_id);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    // Check if judge already provided feedback for this video
    const existingFeedback = await FeedbackModel.findByVideoAndJudge(video_id, req.session.userId);
    if (existingFeedback) {
      // Update existing feedback
      const updatedFeedback = await FeedbackModel.update(existingFeedback.id, {
        score_voice: parseInt(score_voice),
        score_creativity: parseInt(score_creativity),
        score_presentation: parseInt(score_presentation),
        comments: comments || ''
      });
      
      res.json({
        message: 'Feedback updated successfully',
        feedback: updatedFeedback
      });
    } else {
      // Create new feedback
      const feedback = await FeedbackModel.create({
        id: uuidv4(),
        video_id,
        judge_id: req.session.userId,
        score_voice: parseInt(score_voice),
        score_creativity: parseInt(score_creativity),
        score_presentation: parseInt(score_presentation),
        comments: comments || ''
      });
      
      res.status(201).json({
        message: 'Feedback submitted successfully',
        feedback
      });
    }
    
  } catch (error) {
    console.error('Submit feedback error:', error);
    res.status(500).json({ error: 'Failed to submit feedback' });
  }
});

// Get feedback for a video
router.get('/video/:videoId', async (req, res) => {
  try {
    const videoId = req.params.videoId;
    
    // Check if video exists
    const video = await VideoModel.findById(videoId);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    const feedback = await FeedbackModel.findByVideoId(videoId);
    
    res.json({ feedback });
    
  } catch (error) {
    console.error('Get feedback error:', error);
    res.status(500).json({ error: 'Failed to fetch feedback' });
  }
});

// Get feedback by judge (for judge's own feedback history)
router.get('/judge/my-feedback', requireAuth, requireRole(['judge', 'admin']), async (req, res) => {
  try {
    const judgeId = req.session.userId;
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || 20, 50);
    const offset = (page - 1) * limit;
    
    const { feedback, total } = await FeedbackModel.findByJudgeId(judgeId, { limit, offset });
    
    res.json({
      feedback,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get judge feedback error:', error);
    res.status(500).json({ error: 'Failed to fetch feedback' });
  }
});

// Get feedback statistics for a video
router.get('/stats/:videoId', async (req, res) => {
  try {
    const videoId = req.params.videoId;
    
    // Check if video exists
    const video = await VideoModel.findById(videoId);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    const stats = await FeedbackModel.getVideoStats(videoId);
    
    res.json({ stats });
    
  } catch (error) {
    console.error('Get feedback stats error:', error);
    res.status(500).json({ error: 'Failed to fetch feedback statistics' });
  }
});

// Get all feedback (admin only)
router.get('/all', requireAuth, requireRole(['admin']), async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || 20, 50);
    const offset = (page - 1) * limit;
    
    const { feedback, total } = await FeedbackModel.findAll({ limit, offset });
    
    res.json({
      feedback,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get all feedback error:', error);
    res.status(500).json({ error: 'Failed to fetch all feedback' });
  }
});

// Delete feedback (admin only)
router.delete('/:id', requireAuth, requireRole(['admin']), async (req, res) => {
  try {
    const feedbackId = req.params.id;
    const { csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    const feedback = await FeedbackModel.findById(feedbackId);
    if (!feedback) {
      return res.status(404).json({ error: 'Feedback not found' });
    }
    
    await FeedbackModel.delete(feedbackId);
    
    res.json({ message: 'Feedback deleted successfully' });
    
  } catch (error) {
    console.error('Delete feedback error:', error);
    res.status(500).json({ error: 'Failed to delete feedback' });
  }
});

module.exports = router;