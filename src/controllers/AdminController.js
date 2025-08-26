const express = require('express');
const bcrypt = require('bcrypt');
const { v4: uuidv4 } = require('uuid');
const router = express.Router();

const UserModel = require('../models/UserModel');
const VideoModel = require('../models/VideoModel');
const FeedbackModel = require('../models/FeedbackModel');
const CompetitionModel = require('../models/CompetitionModel');
const FeedbackSummaryModel = require('../models/FeedbackSummaryModel');
const SupabaseService = require('../services/SupabaseService');
const { requireAuth } = require('../middleware/auth');
const { requireRole } = require('../middleware/roleCheck');
const { validateCSRFToken } = require('../middleware/csrf');
const config = require('../config');

// Admin-only middleware
const requireAdmin = [requireAuth, requireRole(['admin'])];

// Get dashboard stats
router.get('/stats', ...requireAdmin, async (req, res) => {
  try {
    const stats = {
      users: await UserModel.count(),
      videos: await VideoModel.count(),
      feedback: await FeedbackModel.count(),
      competitions: await CompetitionModel.count()
    };
    
    const usersByRole = await UserModel.countByRole();
    const videosByMonth = await VideoModel.countByMonth();
    
    res.json({
      stats,
      usersByRole,
      videosByMonth
    });
    
  } catch (error) {
    console.error('Get admin stats error:', error);
    res.status(500).json({ error: 'Failed to fetch dashboard statistics' });
  }
});

// User management
router.get('/users', ...requireAdmin, async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || 20, 50);
    const offset = (page - 1) * limit;
    const search = req.query.search || '';
    const role = req.query.role || '';
    
    const { users, total } = await UserModel.findAll({
      limit,
      offset,
      search,
      role
    });
    
    res.json({
      users,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get users error:', error);
    res.status(500).json({ error: 'Failed to fetch users' });
  }
});

// Update user role
router.put('/users/:id/role', ...requireAdmin, async (req, res) => {
  try {
    const userId = req.params.id;
    const { role, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!['user', 'judge', 'admin'].includes(role)) {
      return res.status(400).json({ error: 'Invalid role' });
    }
    
    const user = await UserModel.findById(userId);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    // Prevent self-demotion from admin
    if (userId === req.session.userId && role !== 'admin') {
      return res.status(400).json({ error: 'Cannot change your own admin role' });
    }
    
    const updatedUser = await UserModel.updateRole(userId, role);
    
    res.json({
      message: 'User role updated successfully',
      user: updatedUser
    });
    
  } catch (error) {
    console.error('Update user role error:', error);
    res.status(500).json({ error: 'Failed to update user role' });
  }
});

// Delete user
router.delete('/users/:id', ...requireAdmin, async (req, res) => {
  try {
    const userId = req.params.id;
    const { csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    // Prevent self-deletion
    if (userId === req.session.userId) {
      return res.status(400).json({ error: 'Cannot delete your own account' });
    }
    
    const user = await UserModel.findById(userId);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }
    
    await UserModel.delete(userId);
    
    res.json({ message: 'User deleted successfully' });
    
  } catch (error) {
    console.error('Delete user error:', error);
    res.status(500).json({ error: 'Failed to delete user' });
  }
});

// Video management
router.get('/videos', ...requireAdmin, async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = Math.min(parseInt(req.query.limit) || 20, 50);
    const offset = (page - 1) * limit;
    const search = req.query.search || '';
    
    const { videos, total } = await VideoModel.findAllWithUsers({
      limit,
      offset,
      search
    });
    
    res.json({
      videos,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Get admin videos error:', error);
    res.status(500).json({ error: 'Failed to fetch videos' });
  }
});

// Delete video
router.delete('/videos/:id', ...requireAdmin, async (req, res) => {
  try {
    const videoId = req.params.id;
    const { csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    const video = await VideoModel.findById(videoId);
    if (!video) {
      return res.status(404).json({ error: 'Video not found' });
    }
    
    // Delete from Supabase Storage
    await SupabaseService.deleteVideo(video.storage_path);
    
    // Delete from database (cascades to feedback, likes, etc.)
    await VideoModel.delete(videoId);
    
    res.json({ message: 'Video deleted successfully' });
    
  } catch (error) {
    console.error('Delete video error:', error);
    res.status(500).json({ error: 'Failed to delete video' });
  }
});

// Competition management
router.get('/competitions', ...requireAdmin, async (req, res) => {
  try {
    const competitions = await CompetitionModel.findAll();
    res.json({ competitions });
  } catch (error) {
    console.error('Get competitions error:', error);
    res.status(500).json({ error: 'Failed to fetch competitions' });
  }
});

// Create competition
router.post('/competitions', ...requireAdmin, async (req, res) => {
  try {
    const { title, start_date, end_date, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!title) {
      return res.status(400).json({ error: 'Title is required' });
    }
    
    const competition = await CompetitionModel.create({
      id: uuidv4(),
      title,
      start_date: start_date || null,
      end_date: end_date || null,
      status: 'draft'
    });
    
    res.status(201).json({
      message: 'Competition created successfully',
      competition
    });
    
  } catch (error) {
    console.error('Create competition error:', error);
    res.status(500).json({ error: 'Failed to create competition' });
  }
});

// Update competition status
router.put('/competitions/:id/status', ...requireAdmin, async (req, res) => {
  try {
    const competitionId = req.params.id;
    const { status, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!['draft', 'active', 'closed'].includes(status)) {
      return res.status(400).json({ error: 'Invalid status' });
    }
    
    const competition = await CompetitionModel.updateStatus(competitionId, status);
    if (!competition) {
      return res.status(404).json({ error: 'Competition not found' });
    }
    
    res.json({
      message: 'Competition status updated successfully',
      competition
    });
    
  } catch (error) {
    console.error('Update competition status error:', error);
    res.status(500).json({ error: 'Failed to update competition status' });
  }
});

// Aggregate feedback (trigger manually)
router.post('/aggregate-feedback', ...requireAdmin, async (req, res) => {
  try {
    const { csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    // Import and run aggregation job
    const aggregateJob = require('../jobs/aggregate_feedback');
    const result = await aggregateJob.runAggregation();
    
    res.json({
      message: 'Feedback aggregation completed successfully',
      processed: result.processed,
      updated: result.updated
    });
    
  } catch (error) {
    console.error('Aggregate feedback error:', error);
    res.status(500).json({ error: 'Failed to aggregate feedback' });
  }
});

// API Key management (secure placeholder)
let storedApiKey = null; // In production, use secure storage

router.post('/api-key', ...requireAdmin, async (req, res) => {
  try {
    const { api_key, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!api_key || api_key.length < 10) {
      return res.status(400).json({ error: 'API key must be at least 10 characters' });
    }
    
    // Store API key securely (placeholder implementation)
    storedApiKey = api_key;
    
    res.json({ message: 'API key stored successfully' });
    
  } catch (error) {
    console.error('Store API key error:', error);
    res.status(500).json({ error: 'Failed to store API key' });
  }
});

router.get('/api-key/status', ...requireAdmin, async (req, res) => {
  try {
    res.json({
      has_key: !!storedApiKey,
      key_preview: storedApiKey ? `${storedApiKey.substring(0, 8)}...` : null
    });
  } catch (error) {
    console.error('Get API key status error:', error);
    res.status(500).json({ error: 'Failed to get API key status' });
  }
});

module.exports = router;