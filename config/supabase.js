// Supabase Configuration
const SUPABASE_URL = 'https://your-project.supabase.co'; // Replace with your actual Supabase URL
const SUPABASE_ANON_KEY = 'your-anon-key'; // Replace with your actual anon key

// Initialize Supabase client
const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// Export for use in other files
window.supabase = supabase;
