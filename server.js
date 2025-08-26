const express = require('express');
const session = require('express-session');
const rateLimit = require('express-rate-limit');
const helmet = require('helmet');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

const config = require('./src/config');
const authRoutes = require('./src/controllers/AuthController');
const videoRoutes = require('./src/controllers/VideoController');
const feedbackRoutes = require('./src/controllers/FeedbackController');
const adminRoutes = require('./src/controllers/AdminController');

const app = express();

// Security middleware
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      scriptSrc: ["'self'", "'unsafe-inline'"],
      imgSrc: ["'self'", "data:", "https:"],
      mediaSrc: ["'self'", "https:"]
    }
  }
}));

app.use(cors({
  origin: config.app.url,
  credentials: true
}));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100 // limit each IP to 100 requests per windowMs
});
app.use(limiter);

// Body parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Session configuration
app.use(session({
  secret: config.session.secret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: config.app.env === 'production',
    httpOnly: true,
    maxAge: 24 * 60 * 60 * 1000 // 24 hours
  }
}));

// Static files
app.use(express.static('public'));
app.use('/assets', express.static('assets'));
app.use('/lang', express.static('lang'));

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/videos', videoRoutes);
app.use('/api/feedback', feedbackRoutes);
app.use('/api/admin', adminRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

// Serve HTML pages
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Catch-all for SPA routing
app.get('*', (req, res) => {
  // Check if it's an API request
  if (req.path.startsWith('/api/')) {
    return res.status(404).json({ error: 'API endpoint not found' });
  }
  
  // For non-API requests, serve the appropriate HTML page
  const pageName = req.path.slice(1) || 'index';
  const filePath = path.join(__dirname, 'public', `${pageName}.html`);
  
  // Check if file exists, otherwise serve index
  require('fs').access(filePath, require('fs').constants.F_OK, (err) => {
    if (err) {
      res.sendFile(path.join(__dirname, 'public', 'index.html'));
    } else {
      res.sendFile(filePath);
    }
  });
});

// Error handling middleware
app.use((error, req, res, next) => {
  console.error('Server error:', error);
  res.status(500).json({ 
    error: 'Internal server error',
    message: config.app.env === 'development' ? error.message : 'Something went wrong'
  });
});

const PORT = config.app.port || 3000;
app.listen(PORT, () => {
  console.log(`TalentUp Sri Lanka server running on port ${PORT}`);
  console.log(`Environment: ${config.app.env}`);
  console.log(`App URL: ${config.app.url}`);
});