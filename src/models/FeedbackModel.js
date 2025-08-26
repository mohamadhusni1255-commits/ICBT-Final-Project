const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class FeedbackModel {
  static async create(feedbackData) {
    const { data, error } = await supabase
      .from('feedback')
      .insert([feedbackData])
      .select(`
        *,
        judge:users!judge_id(id, username),
        video:videos!video_id(id, title)
      `)
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findById(id) {
    const { data, error } = await supabase
      .from('feedback')
      .select(`
        *,
        judge:users!judge_id(id, username),
        video:videos!video_id(id, title)
      `)
      .eq('id', id)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findByVideoId(videoId) {
    const { data, error } = await supabase
      .from('feedback')
      .select(`
        *,
        judge:users!judge_id(id, username)
      `)
      .eq('video_id', videoId)
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
  
  static async findByJudgeId(judgeId, { limit = 20, offset = 0 } = {}) {
    const { data, error, count } = await supabase
      .from('feedback')
      .select(`
        *,
        video:videos!video_id(id, title, uploader:users!uploaded_by(username))
      `, { count: 'exact' })
      .eq('judge_id', judgeId)
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (error) throw error;
    return { feedback: data, total: count };
  }
  
  static async findByVideoAndJudge(videoId, judgeId) {
    const { data, error } = await supabase
      .from('feedback')
      .select('*')
      .eq('video_id', videoId)
      .eq('judge_id', judgeId)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async update(id, updateData) {
    const { data, error } = await supabase
      .from('feedback')
      .update(updateData)
      .eq('id', id)
      .select(`
        *,
        judge:users!judge_id(id, username),
        video:videos!video_id(id, title)
      `)
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async delete(id) {
    const { error } = await supabase
      .from('feedback')
      .delete()
      .eq('id', id);
    
    if (error) throw error;
    return true;
  }
  
  static async findAll({ limit = 20, offset = 0 } = {}) {
    const { data, error, count } = await supabase
      .from('feedback')
      .select(`
        *,
        judge:users!judge_id(id, username),
        video:videos!video_id(id, title, uploader:users!uploaded_by(username))
      `, { count: 'exact' })
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (error) throw error;
    return { feedback: data, total: count };
  }
  
  static async count() {
    const { count, error } = await supabase
      .from('feedback')
      .select('*', { count: 'exact', head: true });
    
    if (error) throw error;
    return count;
  }
  
  static async getVideoStats(videoId) {
    const { data, error } = await supabase
      .from('video_avg_scores')
      .select('*')
      .eq('video_id', videoId)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  // Get average scores for feedback aggregation
  static async getVideoAverages() {
    const { data, error } = await supabase
      .from('video_avg_scores')
      .select('*')
      .not('feedback_count', 'eq', 0);
    
    if (error) throw error;
    return data;
  }
}

module.exports = FeedbackModel;