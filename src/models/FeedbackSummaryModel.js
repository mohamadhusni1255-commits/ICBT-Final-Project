const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class FeedbackSummaryModel {
  static async create(summaryData) {
    const { data, error } = await supabase
      .from('feedback_summary')
      .insert([summaryData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findByVideoId(videoId) {
    const { data, error } = await supabase
      .from('feedback_summary')
      .select('*')
      .eq('video_id', videoId)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async upsert(summaryData) {
    const { data, error } = await supabase
      .from('feedback_summary')
      .upsert(summaryData, { onConflict: 'video_id' })
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async update(videoId, updateData) {
    const { data, error } = await supabase
      .from('feedback_summary')
      .update({ ...updateData, updated_at: new Date().toISOString() })
      .eq('video_id', videoId)
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findAll({ limit = 20, offset = 0 } = {}) {
    const { data, error, count } = await supabase
      .from('feedback_summary')
      .select(`
        *,
        video:videos(id, title, uploader:users!uploaded_by(username))
      `, { count: 'exact' })
      .order('updated_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (error) throw error;
    return { summaries: data, total: count };
  }
  
  static async findBestByCategory() {
    const { data, error } = await supabase
      .from('feedback_summary')
      .select(`
        *,
        video:videos(id, title, uploader:users!uploaded_by(username))
      `)
      .not('category_label', 'eq', 'Needs Improvement')
      .order('avg_voice', { ascending: false })
      .limit(10);
    
    if (error) throw error;
    return data;
  }
  
  static async delete(videoId) {
    const { error } = await supabase
      .from('feedback_summary')
      .delete()
      .eq('video_id', videoId);
    
    if (error) throw error;
    return true;
  }
}

module.exports = FeedbackSummaryModel;