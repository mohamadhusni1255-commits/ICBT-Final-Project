/*
  # TalentUp Sri Lanka Database Schema

  1. New Tables
    - `users` - User accounts with role-based access (user, judge, admin)
    - `videos` - Video uploads with metadata and storage paths
    - `likes` - User likes on videos
    - `feedback` - Judge feedback with structured scoring
    - `competitions` - Contest management
    - `competition_entries` - Video entries in competitions
    - `feedback_summary` - Aggregated feedback summaries and categories

  2. Security
    - Enable RLS on all tables
    - Add appropriate policies for role-based access
    - Use UUID primary keys with gen_random_uuid()

  3. Views
    - `video_avg_scores` - Computed average scores per video
*/

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  username text UNIQUE NOT NULL,
  email text UNIQUE NOT NULL,
  password_hash text NOT NULL,
  role text CHECK (role IN ('user', 'judge', 'admin')) NOT NULL DEFAULT 'user',
  age_group text CHECK (age_group IN ('10-14', '15-19', '20-30')),
  created_at timestamptz DEFAULT now()
);

-- Videos table
CREATE TABLE IF NOT EXISTS videos (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  description text,
  storage_path text NOT NULL,
  uploaded_by uuid REFERENCES users(id) ON DELETE SET NULL,
  category text,
  created_at timestamptz DEFAULT now()
);

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES users(id) ON DELETE CASCADE,
  video_id uuid REFERENCES videos(id) ON DELETE CASCADE,
  created_at timestamptz DEFAULT now(),
  UNIQUE(user_id, video_id)
);

-- Feedback table
CREATE TABLE IF NOT EXISTS feedback (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  video_id uuid REFERENCES videos(id) ON DELETE CASCADE,
  judge_id uuid REFERENCES users(id) ON DELETE CASCADE,
  score_voice smallint CHECK (score_voice >= 0 AND score_voice <= 10),
  score_creativity smallint CHECK (score_creativity >= 0 AND score_creativity <= 10),
  score_presentation smallint CHECK (score_presentation >= 0 AND score_presentation <= 10),
  comments text,
  created_at timestamptz DEFAULT now(),
  UNIQUE(video_id, judge_id)
);

-- Competitions table
CREATE TABLE IF NOT EXISTS competitions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  start_date timestamptz,
  end_date timestamptz,
  status text CHECK (status IN ('draft', 'active', 'closed')) DEFAULT 'draft',
  created_at timestamptz DEFAULT now()
);

-- Competition entries table
CREATE TABLE IF NOT EXISTS competition_entries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  competition_id uuid REFERENCES competitions(id) ON DELETE CASCADE,
  video_id uuid REFERENCES videos(id) ON DELETE CASCADE,
  created_at timestamptz DEFAULT now(),
  UNIQUE(competition_id, video_id)
);

-- Feedback summary table
CREATE TABLE IF NOT EXISTS feedback_summary (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  video_id uuid UNIQUE REFERENCES videos(id) ON DELETE CASCADE,
  avg_voice numeric(4,2),
  avg_creativity numeric(4,2),
  avg_presentation numeric(4,2),
  aggregated_text text,
  category_label text,
  updated_at timestamptz DEFAULT now()
);

-- Create view for average scores
CREATE OR REPLACE VIEW video_avg_scores AS
SELECT 
  v.id as video_id,
  v.title,
  v.uploaded_by,
  v.created_at,
  AVG(f.score_voice) as avg_voice,
  AVG(f.score_creativity) as avg_creativity,
  AVG(f.score_presentation) as avg_presentation,
  COUNT(f.id) as feedback_count
FROM videos v
LEFT JOIN feedback f ON v.id = f.video_id
GROUP BY v.id, v.title, v.uploaded_by, v.created_at;

-- Enable Row Level Security
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE videos ENABLE ROW LEVEL SECURITY;
ALTER TABLE likes ENABLE ROW LEVEL SECURITY;
ALTER TABLE feedback ENABLE ROW LEVEL SECURITY;
ALTER TABLE competitions ENABLE ROW LEVEL SECURITY;
ALTER TABLE competition_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE feedback_summary ENABLE ROW LEVEL SECURITY;

-- RLS Policies
-- Users: Users can read all users, but only update themselves
CREATE POLICY "Users can read all users" ON users FOR SELECT TO authenticated USING (true);
CREATE POLICY "Users can update themselves" ON users FOR UPDATE TO authenticated USING (auth.uid() = id);

-- Videos: All can read, owners can update/delete
CREATE POLICY "Anyone can read videos" ON videos FOR SELECT TO authenticated USING (true);
CREATE POLICY "Users can insert their own videos" ON videos FOR INSERT TO authenticated WITH CHECK (auth.uid() = uploaded_by);
CREATE POLICY "Users can update their own videos" ON videos FOR UPDATE TO authenticated USING (auth.uid() = uploaded_by);
CREATE POLICY "Admins can manage all videos" ON videos FOR ALL TO authenticated USING (EXISTS (SELECT 1 FROM users WHERE id = auth.uid() AND role = 'admin'));

-- Likes: Users can manage their own likes
CREATE POLICY "Users can read likes" ON likes FOR SELECT TO authenticated USING (true);
CREATE POLICY "Users can manage their own likes" ON likes FOR ALL TO authenticated USING (auth.uid() = user_id);

-- Feedback: Judges can create, all can read
CREATE POLICY "Anyone can read feedback" ON feedback FOR SELECT TO authenticated USING (true);
CREATE POLICY "Judges can create feedback" ON feedback FOR INSERT TO authenticated WITH CHECK (EXISTS (SELECT 1 FROM users WHERE id = auth.uid() AND role IN ('judge', 'admin')));
CREATE POLICY "Judges can update their own feedback" ON feedback FOR UPDATE TO authenticated USING (auth.uid() = judge_id);

-- Competitions: Admins manage, all can read active ones
CREATE POLICY "Anyone can read active competitions" ON competitions FOR SELECT TO authenticated USING (status = 'active');
CREATE POLICY "Admins can manage competitions" ON competitions FOR ALL TO authenticated USING (EXISTS (SELECT 1 FROM users WHERE id = auth.uid() AND role = 'admin'));

-- Competition entries: Users can enter, all can read
CREATE POLICY "Anyone can read competition entries" ON competition_entries FOR SELECT TO authenticated USING (true);
CREATE POLICY "Users can enter competitions" ON competition_entries FOR INSERT TO authenticated WITH CHECK (EXISTS (SELECT 1 FROM videos WHERE id = video_id AND uploaded_by = auth.uid()));

-- Feedback summary: All can read, system can update
CREATE POLICY "Anyone can read feedback summaries" ON feedback_summary FOR SELECT TO authenticated USING (true);
CREATE POLICY "System can manage feedback summaries" ON feedback_summary FOR ALL TO service_role USING (true);