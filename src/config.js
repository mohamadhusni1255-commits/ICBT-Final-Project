require('dotenv').config();

const config = {
  supabase: {
    url: process.env.SUPABASE_URL,
    serviceKey: process.env.SUPABASE_SERVICE_KEY,
    anonKey: process.env.SUPABASE_ANON_KEY,
    storageBucket: process.env.SUPABASE_STORAGE_BUCKET || 'videos'
  },
  app: {
    url: process.env.APP_URL || 'http://localhost:3000',
    port: process.env.NODE_PORT || 3000,
    env: process.env.NODE_ENV || 'development'
  },
  session: {
    secret: process.env.SESSION_SECRET || 'fallback-secret-key-change-in-production'
  },
  upload: {
    maxFileSize: parseInt(process.env.MAX_FILE_SIZE) || 52428800, // 50MB
    allowedMimeTypes: ['video/mp4'],
    allowedExtensions: ['.mp4']
  },
  security: {
    bcryptRounds: 10,
    csrfTokenLength: 32,
    signedUrlExpiration: 3600 // 1 hour
  },
  pagination: {
    defaultLimit: 12,
    maxLimit: 50
  }
};

// Validate required environment variables
const requiredEnvVars = [
  'SUPABASE_URL',
  'SUPABASE_SERVICE_KEY',
  'SUPABASE_ANON_KEY'
];

for (const envVar of requiredEnvVars) {
  if (!process.env[envVar]) {
    console.error(`Missing required environment variable: ${envVar}`);
    process.exit(1);
  }
}

module.exports = config;