const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class CompetitionModel {
  static async create(competitionData) {
    const { data, error } = await supabase
      .from('competitions')
      .insert([competitionData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findById(id) {
    const { data, error } = await supabase
      .from('competitions')
      .select('*')
      .eq('id', id)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findAll() {
    const { data, error } = await supabase
      .from('competitions')
      .select('*')
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
  
  static async findActive() {
    const { data, error } = await supabase
      .from('competitions')
      .select('*')
      .eq('status', 'active')
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
  
  static async updateStatus(id, status) {
    const { data, error } = await supabase
      .from('competitions')
      .update({ status })
      .eq('id', id)
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async delete(id) {
    const { error } = await supabase
      .from('competitions')
      .delete()
      .eq('id', id);
    
    if (error) throw error;
    return true;
  }
  
  static async count() {
    const { count, error } = await supabase
      .from('competitions')
      .select('*', { count: 'exact', head: true });
    
    if (error) throw error;
    return count;
  }
  
  static async addEntry(competitionId, videoId) {
    const { data, error } = await supabase
      .from('competition_entries')
      .insert([{
        competition_id: competitionId,
        video_id: videoId
      }])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async getEntries(competitionId) {
    const { data, error } = await supabase
      .from('competition_entries')
      .select(`
        *,
        video:videos(*, uploader:users!uploaded_by(username))
      `)
      .eq('competition_id', competitionId)
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
}

module.exports = CompetitionModel;