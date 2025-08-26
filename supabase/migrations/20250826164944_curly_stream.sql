/*
  Sample Seed Data for TalentUp Sri Lanka
  
  IMPORTANT: Generate password hashes before running this script:
  
  Node.js:
  const bcrypt = require('bcrypt');
  console.log('admin123:', bcrypt.hashSync('admin123', 10));
  console.log('judge123:', bcrypt.hashSync('judge123', 10));  
  console.log('user123:', bcrypt.hashSync('user123', 10));
  
  PHP:
  echo 'admin123: ' . password_hash('admin123', PASSWORD_DEFAULT) . "\n";
  echo 'judge123: ' . password_hash('judge123', PASSWORD_DEFAULT) . "\n";
  echo 'user123: ' . password_hash('user123', PASSWORD_DEFAULT) . "\n";
  
  Replace the hash values below with generated hashes.
*/

-- Sample Users (replace password hashes with actual generated values)
INSERT INTO users (id, username, email, password_hash, role, age_group) VALUES
('550e8400-e29b-41d4-a716-446655440001', 'admin', 'admin@example.com', '$2b$10$REPLACE_WITH_ACTUAL_HASH', 'admin', NULL),
('550e8400-e29b-41d4-a716-446655440002', 'judge1', 'judge1@example.com', '$2b$10$REPLACE_WITH_ACTUAL_HASH', 'judge', NULL),
('550e8400-e29b-41d4-a716-446655440003', 'user1', 'user1@example.com', '$2b$10$REPLACE_WITH_ACTUAL_HASH', 'user', '15-19'),
('550e8400-e29b-41d4-a716-446655440004', 'judge2', 'judge2@example.com', '$2b$10$REPLACE_WITH_ACTUAL_HASH', 'judge', NULL),
('550e8400-e29b-41d4-a716-446655440005', 'user2', 'user2@example.com', '$2b$10$REPLACE_WITH_ACTUAL_HASH', 'user', '10-14')
ON CONFLICT (email) DO NOTHING;

-- Sample Competition
INSERT INTO competitions (id, title, start_date, end_date, status) VALUES
('660e8400-e29b-41d4-a716-446655440001', 'Rural Talent Showcase 2025', '2025-01-01 00:00:00+00', '2025-03-31 23:59:59+00', 'active')
ON CONFLICT (id) DO NOTHING;

-- Sample Videos (these will need actual uploaded files)
INSERT INTO videos (id, title, description, storage_path, uploaded_by, category) VALUES
('770e8400-e29b-41d4-a716-446655440001', 'My First Talent Video', 'A sample video showing singing talent', 'videos/sample1.mp4', '550e8400-e29b-41d4-a716-446655440003', 'Music'),
('770e8400-e29b-41d4-a716-446655440002', 'Dance Performance', 'Traditional Sri Lankan dance', 'videos/sample2.mp4', '550e8400-e29b-41d4-a716-446655440005', 'Dance')
ON CONFLICT (id) DO NOTHING;

-- Sample Feedback
INSERT INTO feedback (video_id, judge_id, score_voice, score_creativity, score_presentation, comments) VALUES
('770e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440002', 8, 7, 6, 'Great voice quality and pitch control. Could improve stage presence.'),
('770e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440004', 7, 8, 7, 'Creative song choice and good vocal range. Nice overall performance.'),
('770e8400-e29b-41d4-a716-446655440002', '550e8400-e29b-41d4-a716-446655440002', 6, 9, 9, 'Excellent traditional dance technique. Very creative choreography and outstanding presentation skills.')
ON CONFLICT (video_id, judge_id) DO NOTHING;

-- Sample Likes
INSERT INTO likes (user_id, video_id) VALUES
('550e8400-e29b-41d4-a716-446655440003', '770e8400-e29b-41d4-a716-446655440002'),
('550e8400-e29b-41d4-a716-446655440005', '770e8400-e29b-41d4-a716-446655440001')
ON CONFLICT (user_id, video_id) DO NOTHING;

-- Sample Competition Entries
INSERT INTO competition_entries (competition_id, video_id) VALUES
('660e8400-e29b-41d4-a716-446655440001', '770e8400-e29b-41d4-a716-446655440001'),
('660e8400-e29b-41d4-a716-446655440001', '770e8400-e29b-41d4-a716-446655440002')
ON CONFLICT (competition_id, video_id) DO NOTHING;