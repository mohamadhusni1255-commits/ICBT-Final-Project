# TalentUp Sri Lanka - Backend Implementation

## Overview
This document explains the complete PHP backend implementation that connects all UI elements to Supabase database functionality. The backend follows MVC architecture and provides secure, scalable APIs for the talent platform.

## Project Structure

```
ICBT-final/
├── config/                          # Configuration files
│   ├── database.php                 # Database connection class
│   ├── supabase.php                 # Supabase API client
│   └── config.env.example           # Environment variables template
├── models/                          # Data models
│   ├── UserModel.php                # User operations
│   ├── VideoModel.php               # Video operations
│   ├── FeedbackModel.php            # Feedback operations
│   └── LikeModel.php                # Like operations
├── controllers/                     # Business logic controllers
│   ├── AuthController.php           # Authentication logic
│   ├── VideoController.php          # Video management logic
│   └── AdminController.php          # Admin operations logic
├── api/                            # API endpoints
│   ├── auth/                       # Authentication APIs
│   │   ├── login.php               # User login
│   │   ├── register.php            # User registration
│   │   ├── logout.php              # User logout
│   │   ├── me.php                  # Get current user
│   │   └── csrf-token.php          # CSRF token generation
│   ├── videos/                     # Video management APIs
│   │   ├── list.php                # Get video list
│   │   ├── detail.php              # Get video details
│   │   ├── upload.php              # Upload video
│   │   └── like.php                # Toggle video like
│   └── admin/                      # Admin APIs
│       └── stats.php                # System statistics
├── public/                         # UI files (unchanged)
├── assets/                         # Static assets
└── lang/                          # Language files
```

## Backend Features Implemented

### 1. Authentication System
- **User Registration**: Complete user registration with validation
- **User Login**: Secure authentication with session management
- **Role-Based Access**: User, Judge, and Admin roles
- **Session Management**: Secure PHP sessions with CSRF protection
- **Password Security**: Bcrypt hashing for passwords

### 2. Video Management
- **Video Upload**: Secure file upload to Supabase Storage
- **Video CRUD**: Create, read, update, delete operations
- **Video Approval**: Judge/Admin approval system
- **Video Categories**: Organized video classification
- **Search & Filters**: Advanced video search and filtering

### 3. User Interaction Features
- **Video Likes**: Like/unlike functionality with real-time updates
- **Feedback System**: Rating and comment system for videos
- **View Tracking**: Video view count tracking
- **User Profiles**: User profile management

### 4. Admin & Judge Features
- **User Management**: Admin user role and status management
- **Video Approval**: Judge video review and approval system
- **System Statistics**: Comprehensive platform analytics
- **Content Moderation**: Admin content management tools

### 5. Security Features
- **CSRF Protection**: Cross-site request forgery prevention
- **Input Validation**: Comprehensive data validation
- **SQL Injection Prevention**: Prepared statements
- **File Upload Security**: Secure file handling
- **Session Security**: Secure session management

## API Endpoints

### Authentication APIs
```
POST /api/auth/login          # User login
POST /api/auth/register       # User registration
POST /api/auth/logout         # User logout
GET  /api/auth/me             # Get current user
GET  /api/auth/csrf-token     # Get CSRF token
```

### Video APIs
```
GET  /api/videos/list         # Get video list with filters
GET  /api/videos/detail       # Get video details
POST /api/videos/upload       # Upload video
POST /api/videos/like         # Toggle video like
```

### Admin APIs
```
GET  /api/admin/stats         # Get system statistics
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    age_group VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Videos Table
```sql
CREATE TABLE videos (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    user_id INTEGER REFERENCES users(id),
    storage_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    file_size BIGINT,
    duration INTEGER,
    view_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    is_approved BOOLEAN DEFAULT false,
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Feedback Table
```sql
CREATE TABLE feedback (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    video_id INTEGER REFERENCES videos(id),
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Likes Table
```sql
CREATE TABLE likes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    video_id INTEGER REFERENCES videos(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, video_id)
);
```

## How UI Elements Connect to Backend

### 1. Homepage (index.html)
- **Statistics Display**: Connected to `/api/admin/stats` for live platform metrics
- **Authentication Status**: Connected to `/api/auth/me` for user session
- **Language Support**: Backend supports multilingual content

### 2. Login Page (login.html)
- **Login Form**: Connected to `/api/auth/login` for authentication
- **CSRF Protection**: Uses `/api/auth/csrf-token` for security
- **Demo Accounts**: Pre-configured test accounts for different roles

### 3. Registration Page (register.html)
- **Registration Form**: Connected to `/api/auth/register` for user creation
- **Form Validation**: Backend validates all input fields
- **Role Assignment**: Automatically assigns 'user' role

### 4. User Dashboard (dashboard_user.html)
- **Video Management**: Connected to video APIs for CRUD operations
- **Profile Management**: Connected to user update APIs
- **Upload Interface**: Connected to `/api/videos/upload`

### 5. Video List (video_list.html)
- **Video Display**: Connected to `/api/videos/list` with pagination
- **Search & Filters**: Backend supports advanced filtering
- **Category Browsing**: Dynamic category loading from database

### 6. Video Detail (video_detail.html)
- **Video Playback**: Connected to Supabase Storage for video streaming
- **Like System**: Connected to `/api/videos/like` for interactions
- **Feedback System**: Connected to feedback APIs
- **View Tracking**: Automatic view count updates

### 7. Admin Panel (admin_panel.html)
- **User Management**: Connected to admin user management APIs
- **System Statistics**: Connected to `/api/admin/stats`
- **Content Moderation**: Connected to video approval APIs
- **Platform Health**: Connected to system health monitoring

### 8. Judge Panel (judge_panel.html)
- **Video Review**: Connected to pending video approval APIs
- **Feedback Management**: Connected to feedback moderation APIs
- **Quality Assessment**: Connected to video evaluation tools

## Setup Instructions

### 1. Environment Configuration
```bash
# Copy the example environment file
cp config.env.example .env

# Edit .env with your Supabase credentials
nano .env
```

### 2. Database Setup
```sql
-- Run the SQL schema in your Supabase database
-- The schema is provided in the supabase/migrations/ folder
```

### 3. File Permissions
```bash
# Ensure upload directories are writable
chmod 755 uploads/
chmod 755 temp/
```

### 4. Web Server Configuration
```apache
# Apache .htaccess example
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [QSA,L]
```

## Security Considerations

### 1. CSRF Protection
- All forms include CSRF tokens
- Backend validates tokens on every request
- Tokens are regenerated on login

### 2. Input Validation
- Server-side validation for all inputs
- SQL injection prevention with prepared statements
- File upload security with type and size validation

### 3. Authentication
- Secure password hashing with bcrypt
- Session-based authentication
- Role-based access control

### 4. File Security
- Secure file upload handling
- File type validation
- Size limits enforcement
- Secure storage paths

## Performance Optimizations

### 1. Database
- Prepared statements for query optimization
- Connection pooling with PDO
- Efficient indexing on frequently queried fields

### 2. File Handling
- Asynchronous file uploads
- Efficient storage path generation
- Signed URL generation for secure access

### 3. Caching
- Session-based caching for user data
- Database query result caching
- Static asset optimization

## Error Handling

### 1. User-Friendly Messages
- Clear error messages for users
- Detailed logging for developers
- Graceful fallbacks for failures

### 2. Logging
- Comprehensive error logging
- User action tracking
- Performance monitoring

## Testing

### 1. API Testing
```bash
# Test authentication
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Test video list
curl http://localhost/api/videos/list?limit=5
```

### 2. Demo Accounts
- **Admin**: admin@example.com / admin123
- **Judge**: judge@example.com / judge123
- **User**: user@example.com / user123

## Deployment

### 1. Production Considerations
- Use HTTPS for all communications
- Set secure session configuration
- Configure proper file permissions
- Set up monitoring and logging

### 2. Scaling
- Database connection pooling
- File storage optimization
- CDN integration for static assets
- Load balancing for high traffic

## Support

For technical support or questions about the backend implementation:
- Check the error logs in your web server
- Verify environment variable configuration
- Ensure database schema is properly set up
- Test API endpoints individually

## Conclusion

This backend implementation provides a complete, secure, and scalable foundation for the TalentUp Sri Lanka platform. All UI elements are now connected to proper backend logic, ensuring a fully functional talent showcase platform with comprehensive user management, video handling, and administrative capabilities.
