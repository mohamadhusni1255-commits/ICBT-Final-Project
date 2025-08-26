# Supabase Storage Configuration Guide

## Storage Bucket Setup

### 1. Create Videos Bucket
In Supabase Dashboard > Storage:
1. Create bucket named `videos`
2. Set bucket to **Public** (for thumbnail access)
3. Maximum file size: 50MB

### 2. Storage Policies (RLS)

#### For Public Read Access (thumbnails)
```sql
CREATE POLICY "Public can view video files" ON storage.objects
FOR SELECT TO anon, authenticated
USING (bucket_id = 'videos' AND (storage.foldername(name))[1] = 'public');
```

#### For Authenticated Upload
```sql
CREATE POLICY "Authenticated users can upload videos" ON storage.objects
FOR INSERT TO authenticated
WITH CHECK (bucket_id = 'videos' AND auth.uid()::text = (storage.foldername(name))[1]);
```

#### For Owner Management
```sql
CREATE POLICY "Users can manage their own videos" ON storage.objects
FOR ALL TO authenticated
USING (bucket_id = 'videos' AND auth.uid()::text = (storage.foldername(name))[1]);
```

## Signed URL Generation

### Node.js Implementation
```javascript
const { createClient } = require('@supabase/supabase-js');

const supabase = createClient(
  process.env.SUPABASE_URL,
  process.env.SUPABASE_SERVICE_KEY // Use service key for server-side
);

// Generate signed URL for video playback (expires in 1 hour)
async function getVideoSignedUrl(storagePath) {
  try {
    const { data, error } = await supabase.storage
      .from('videos')
      .createSignedUrl(storagePath, 3600); // 1 hour
    
    if (error) throw error;
    return data.signedUrl;
  } catch (error) {
    console.error('Signed URL error:', error);
    return null;
  }
}

// Upload video file
async function uploadVideo(file, userId, fileName) {
  try {
    const filePath = `${userId}/${fileName}`;
    
    const { data, error } = await supabase.storage
      .from('videos')
      .upload(filePath, file, {
        cacheControl: '3600',
        upsert: false
      });
    
    if (error) throw error;
    return data.path;
  } catch (error) {
    console.error('Upload error:', error);
    return null;
  }
}

module.exports = { getVideoSignedUrl, uploadVideo };
```

### PHP Implementation (Fallback)
```php
<?php
// PHP Supabase Storage Helper (fallback)
class SupabaseStorage {
    private $url;
    private $serviceKey;
    private $bucket;
    
    public function __construct($url, $serviceKey, $bucket = 'videos') {
        $this->url = rtrim($url, '/');
        $this->serviceKey = $serviceKey;
        $this->bucket = $bucket;
    }
    
    // Generate signed URL using Supabase REST API
    public function createSignedUrl($path, $expiresIn = 3600) {
        $endpoint = "{$this->url}/storage/v1/object/sign/{$this->bucket}/{$path}";
        
        $data = json_encode(['expiresIn' => $expiresIn]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->serviceKey
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $this->url . '/storage/v1' . $result['signedURL'];
        }
        
        return false;
    }
    
    // Upload file to Supabase Storage
    public function upload($filePath, $localFile) {
        $endpoint = "{$this->url}/storage/v1/object/{$this->bucket}/{$filePath}";
        
        $fileData = file_get_contents($localFile);
        $mimeType = mime_content_type($localFile);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fileData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $mimeType,
                'Authorization: Bearer ' . $this->serviceKey,
                'Cache-Control: 3600'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}

// Usage example:
/*
$storage = new SupabaseStorage(
    $_ENV['SUPABASE_URL'],
    $_ENV['SUPABASE_SERVICE_KEY']
);

// Generate signed URL
$signedUrl = $storage->createSignedUrl('user123/video.mp4', 3600);

// Upload file
$success = $storage->upload('user123/video.mp4', '/tmp/uploaded_file.mp4');
*/
?>
```

## CORS Configuration

Add this CORS policy in Supabase Dashboard > Storage > Settings:

```json
[
  {
    "allowedOrigins": ["http://localhost:3000", "https://yourdomian.com"],
    "allowedHeaders": ["*"],
    "allowedMethods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    "maxAgeSeconds": 3000
  }
]
```

## File Organization Structure

```
videos/
├── {user_id}/
│   ├── {video_id}.mp4
│   └── {video_id}_thumb.jpg
├── public/
│   ├── thumbnails/
│   └── assets/
└── temp/
    └── processing/
```

## Security Notes

1. **Service Role Key**: Never expose in client-side code
2. **Signed URLs**: Always generate server-side with appropriate expiration
3. **File Validation**: Validate MIME type and size before upload
4. **Path Security**: Use UUID-based paths to prevent enumeration
5. **RLS Policies**: Ensure proper access control on storage objects