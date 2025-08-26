const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class VideoModel {
  static async create(videoData) {
    const { data, error } = await supabase
      .from('videos')
      .insert([videoData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findById(id) {
    const { data, error } = await supabase
      .from('videos')
      .select(`
        *,
        uploader:users!uploaded_by(id, username)
      `)
      .eq('id', id)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findAll({ limit = 12, offset = 0, search = '', category = '' } = {}) {
    let query = supabase
      .from('videos')
      .select(`
        *,
        uploader:users!uploaded_by(id, username)
      `, { count: 'exact' })
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (search) {
      query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
    }
    
    if (category) {
      query = query.eq('category', category);
    }
    
    const { data, error, count } = await query;
    
    if (error) throw error;
    return { videos: data, total: count };
  }
  
  static async findByUser(userId, { limit = 12, offset = 0 } = {}) {
    const { data, error, count } = await supabase
      .from('videos')
      .select('*', { count: 'exact' })
      .eq('uploaded_by', userId)
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (error) throw error;
    return { videos: data, total: count };
  }
  
  static async findAllWithUsers({ limit = 20, offset = 0, search = '' } = {}) {
    let query = supabase
      .from('videos')
      .select(`
        *,
        uploader:users!uploaded_by(id, username, email)
      `, { count: 'exact' })
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (search) {
      query = query.or(`title.ilike.%${search}%,description.ilike.%${search}%`);
    }
    
    const { data, error, count } = await query;
    
    if (error) throw error;
    return { videos: data, total: count };
  }
  
  static async update(id, updateData) {
    const { data, error } = await supabase
      .from('videos')
      .update(updateData)
      .eq('id', id)
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async delete(id) {
    const { error } = await supabase
      .from('videos')
      .delete()
      .eq('id', id);
    
    if (error) throw error;
    return true;
  }
  
  static async count() {
    const { count, error } = await supabase
      .from('videos')
      .select('*', { count: 'exact', head: true });
    
    if (error) throw error;
    return count;
  }
  
  static async countByMonth() {
    const { data, error } = await supabase
      .rpc('get_video_counts_by_month');
    
    if (error) {
      // Fallback if RPC function doesn't exist
      const { data: fallbackData, error: fallbackError } = await supabase
        .from('videos')
        .select('created_at')
        .order('created_at', { ascending: false });
      
      if (fallbackError) throw fallbackError;
      
      const monthCounts = {};
      fallbackData.forEach(video => {
        const month = new Date(video.created_at).toISOString().substring(0, 7);
        monthCounts[month] = (monthCounts[month] || 0) + 1;
      });
      
      return Object.entries(monthCounts).map(([month, count]) => ({ month, count }));
    }
    
    return data;
  }
}

module.exports = VideoModel;