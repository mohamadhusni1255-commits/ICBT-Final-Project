const express = require('express');
const bcrypt = require('bcrypt');
const { v4: uuidv4 } = require('uuid');
const router = express.Router();

const UserModel = require('../models/UserModel');
const { generateCSRFToken, validateCSRFToken } = require('../middleware/csrf');
const config = require('../config');

// Register endpoint
router.post('/register', async (req, res) => {
  try {
    const { username, email, password, age_group, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    // Validate input
    if (!username || !email || !password) {
      return res.status(400).json({ error: 'Username, email and password are required' });
    }
    
    if (password.length < 6) {
      return res.status(400).json({ error: 'Password must be at least 6 characters' });
    }
    
    // Check if user exists
    const existingUser = await UserModel.findByEmail(email);
    if (existingUser) {
      return res.status(400).json({ error: 'User with this email already exists' });
    }
    
    const existingUsername = await UserModel.findByUsername(username);
    if (existingUsername) {
      return res.status(400).json({ error: 'Username already taken' });
    }
    
    // Hash password
    const passwordHash = await bcrypt.hash(password, config.security.bcryptRounds);
    
    // Create user
    const userId = uuidv4();
    const user = await UserModel.create({
      id: userId,
      username,
      email,
      password_hash: passwordHash,
      role: 'user', // Default role
      age_group: age_group || null
    });
    
    // Set session
    req.session.userId = user.id;
    req.session.userRole = user.role;
    req.session.username = user.username;
    
    // Regenerate session ID for security
    req.session.regenerate((err) => {
      if (err) {
        console.error('Session regeneration error:', err);
      }
    });
    
    res.status(201).json({
      message: 'User registered successfully',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        role: user.role,
        age_group: user.age_group
      }
    });
    
  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({ error: 'Registration failed' });
  }
});

// Login endpoint
router.post('/login', async (req, res) => {
  try {
    const { email, password, csrf_token } = req.body;
    
    // Validate CSRF token
    if (!validateCSRFToken(req.session, csrf_token)) {
      return res.status(403).json({ error: 'Invalid CSRF token' });
    }
    
    if (!email || !password) {
      return res.status(400).json({ error: 'Email and password are required' });
    }
    
    // Find user
    const user = await UserModel.findByEmail(email);
    if (!user) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }
    
    // Verify password
    const passwordValid = await bcrypt.compare(password, user.password_hash);
    if (!passwordValid) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }
    
    // Set session
    req.session.userId = user.id;
    req.session.userRole = user.role;
    req.session.username = user.username;
    
    // Regenerate session ID for security
    req.session.regenerate((err) => {
      if (err) {
        console.error('Session regeneration error:', err);
      }
    });
    
    res.json({
      message: 'Login successful',
      user: {
        id: user.id,
        username: user.username,
        email: user.email,
        role: user.role,
        age_group: user.age_group
      }
    });
    
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Login failed' });
  }
});

// Logout endpoint
router.post('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) {
      console.error('Logout error:', err);
      return res.status(500).json({ error: 'Logout failed' });
    }
    res.json({ message: 'Logout successful' });
  });
});

// Get current user
router.get('/me', (req, res) => {
  if (!req.session.userId) {
    return res.status(401).json({ error: 'Not authenticated' });
  }
  
  res.json({
    user: {
      id: req.session.userId,
      username: req.session.username,
      role: req.session.userRole
    }
  });
});

// Generate CSRF token
router.get('/csrf-token', (req, res) => {
  const token = generateCSRFToken(req.session);
  res.json({ csrf_token: token });
});

module.exports = router;