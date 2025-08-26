const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class LikeModel {
  static async create(likeData) {
    const { data, error } = await supabase
      .from('likes')
      .insert([likeData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findByUserAndVideo(userId, videoId) {
    const { data, error } = await supabase
      .from('likes')
      .select('*')
      .eq('user_id', userId)
      .eq('video_id', videoId)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async countByVideo(videoId) {
    const { count, error } = await supabase
      .from('likes')
      .select('*', { count: 'exact', head: true })
      .eq('video_id', videoId);
    
    if (error) throw error;
    return count || 0;
  }
  
  static async countByUser(userId) {
    const { count, error } = await supabase
      .from('likes')
      .select('*', { count: 'exact', head: true })
      .eq('user_id', userId);
    
    if (error) throw error;
    return count || 0;
  }
  
  static async delete(id) {
    const { error } = await supabase
      .from('likes')
      .delete()
      .eq('id', id);
    
    if (error) throw error;
    return true;
  }
  
  static async findByUser(userId, { limit = 20, offset = 0 } = {}) {
    const { data, error, count } = await supabase
      .from('likes')
      .select(`
        *,
        video:videos(id, title, description, created_at, uploader:users!uploaded_by(username))
      `, { count: 'exact' })
      .eq('user_id', userId)
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (error) throw error;
    return { likes: data, total: count };
  }
}

module.exports = LikeModel;