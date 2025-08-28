const { createClient } = require('@supabase/supabase-js');
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey);

class UserModel {
  static async create(userData) {
    const { data, error } = await supabase
      .from('users')
      .insert([userData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  static async findById(id) {
    const { data, error } = await supabase
      .from('users')
      .select('*')
      .eq('id', id)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findByEmail(email) {
    const { data, error } = await supabase
      .from('users')
      .select('*')
      .eq('email', email)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findByUsername(username) {
    const { data, error } = await supabase
      .from('users')
      .select('*')
      .eq('username', username)
      .single();
    
    if (error && error.code !== 'PGRST116') throw error;
    return data;
  }
  
  static async findAll({ limit = 20, offset = 0, search = '', role = '' } = {}) {
    let query = supabase
      .from('users')
      .select('id, username, email, role, age_group, created_at', { count: 'exact' })
      .order('created_at', { ascending: false })
      .range(offset, offset + limit - 1);
    
    if (search) {
      query = query.or(`username.ilike.%${search}%,email.ilike.%${search}%`);
    }
    
    if (role) {
      query = query.eq('role', role);
    }
    
    const { data, error, count } = await query;
    
    if (error) throw error;
    return { users: data, total: count };
  }
  
  static async updateRole(id, role) {
    const { data, error } = await supabase
      .from('users')
      .update({ role })
      .eq('id', id)
      .select('id, username, email, role, age_group, created_at')
      .single();
    
    if (error) throw error;
    return data;
  }

  static async updateRoleWithPermission(id, newRole, currentUserRole) {
    // Only super admins can create other super admins or judges
    if (newRole === 'super_admin' && currentUserRole !== 'super_admin') {
      throw new Error('Only super admins can create other super admins');
    }
    
    if (newRole === 'judge' && currentUserRole !== 'super_admin') {
      throw new Error('Only super admins can create judges');
    }
    
    const { data, error } = await supabase
      .from('users')
      .update({ role: newRole })
      .eq('id', id)
      .select('id, username, email, role, age_group, created_at')
      .single();
    
    if (error) throw error;
    return data;
  }

  static async createSuperAdmin(userData) {
    // Ensure the user being created has super_admin role
    userData.role = 'super_admin';
    
    const { data, error } = await supabase
      .from('users')
      .insert([userData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }

  static async createJudge(userData) {
    // Ensure the user being created has judge role
    userData.role = 'judge';
    
    const { data, error } = await supabase
      .from('users')
      .insert([userData])
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }

  static async findSuperAdmins() {
    const { data, error } = await supabase
      .from('users')
      .select('id, username, email, role, age_group, created_at')
      .eq('role', 'super_admin')
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }

  static async findJudges() {
    const { data, error } = await supabase
      .from('users')
      .select('id, username, email, role, age_group, created_at')
      .eq('role', 'judge')
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
  
  static async delete(id) {
    const { error } = await supabase
      .from('users')
      .delete()
      .eq('id', id);
    
    if (error) throw error;
    return true;
  }
  
  static async count() {
    const { count, error } = await supabase
      .from('users')
      .select('*', { count: 'exact', head: true });
    
    if (error) throw error;
    return count;
  }
  
  static async countByRole() {
    const { data, error } = await supabase
      .from('users')
      .select('role')
      .order('role');
    
    if (error) throw error;
    
    const roleCounts = {};
    data.forEach(user => {
      roleCounts[user.role] = (roleCounts[user.role] || 0) + 1;
    });
    
    return roleCounts;
  }
}

module.exports = UserModel;