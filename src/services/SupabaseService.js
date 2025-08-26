const { createClient } = require('@supabase/supabase-js');
const fs = require('fs').promises;
const config = require('../config');

const supabase = createClient(config.supabase.url, config.supabase.serviceKey, {
  auth: {
    autoRefreshToken: false,
    persistSession: false
  }
});

class SupabaseService {
  // Upload video file to Supabase Storage
  static async uploadVideo(localFilePath, storagePath) {
    try {
      // Read file as buffer
      const fileBuffer = await fs.readFile(localFilePath);
      
      const { data, error } = await supabase.storage
        .from(config.supabase.storageBucket)
        .upload(storagePath, fileBuffer, {
          cacheControl: '3600',
          upsert: false,
          contentType: 'video/mp4'
        });
      
      if (error) {
        console.error('Supabase upload error:', error);
        return false;
      }
      
      return data.path;
    } catch (error) {
      console.error('Upload video error:', error);
      return false;
    }
  }
  
  // Generate signed URL for video playback
  static async getVideoSignedUrl(storagePath) {
    try {
      const { data, error } = await supabase.storage
        .from(config.supabase.storageBucket)
        .createSignedUrl(storagePath, config.security.signedUrlExpiration);
      
      if (error) {
        console.error('Signed URL error:', error);
        return null;
      }
      
      return data.signedUrl;
    } catch (error) {
      console.error('Get signed URL error:', error);
      return null;
    }
  }
  
  // Delete video from storage
  static async deleteVideo(storagePath) {
    try {
      const { error } = await supabase.storage
        .from(config.supabase.storageBucket)
        .remove([storagePath]);
      
      if (error) {
        console.error('Delete video error:', error);
        return false;
      }
      
      return true;
    } catch (error) {
      console.error('Delete video error:', error);
      return false;
    }
  }
  
  // List files in storage (for admin purposes)
  static async listFiles(folder = '') {
    try {
      const { data, error } = await supabase.storage
        .from(config.supabase.storageBucket)
        .list(folder, {
          limit: 100,
          offset: 0
        });
      
      if (error) {
        console.error('List files error:', error);
        return [];
      }
      
      return data;
    } catch (error) {
      console.error('List files error:', error);
      return [];
    }
  }
  
  // Get public URL for thumbnails (if bucket is public)
  static getPublicUrl(storagePath) {
    try {
      const { data } = supabase.storage
        .from(config.supabase.storageBucket)
        .getPublicUrl(storagePath);
      
      return data.publicUrl;
    } catch (error) {
      console.error('Get public URL error:', error);
      return null;
    }
  }
  
  // Upload thumbnail image
  static async uploadThumbnail(localFilePath, storagePath) {
    try {
      const fileBuffer = await fs.readFile(localFilePath);
      
      const { data, error } = await supabase.storage
        .from(config.supabase.storageBucket)
        .upload(storagePath, fileBuffer, {
          cacheControl: '3600',
          upsert: true,
          contentType: 'image/jpeg'
        });
      
      if (error) {
        console.error('Thumbnail upload error:', error);
        return false;
      }
      
      return data.path;
    } catch (error) {
      console.error('Upload thumbnail error:', error);
      return false;
    }
  }
  
  // Check storage bucket status and quota
  static async getStorageStatus() {
    try {
      // This would require admin API access to get quota info
      // For now, just check if we can list files
      const files = await this.listFiles();
      return {
        accessible: true,
        fileCount: files.length
      };
    } catch (error) {
      console.error('Storage status error:', error);
      return {
        accessible: false,
        error: error.message
      };
    }
  }
}

module.exports = SupabaseService;