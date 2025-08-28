// Supabase Configuration
// For demo purposes, using a test project
const SUPABASE_URL = 'https://demo-project.supabase.co';
const SUPABASE_ANON_KEY = 'demo-anon-key';

console.log('Supabase config loading...');
console.log('SUPABASE_URL:', SUPABASE_URL);
console.log('SUPABASE_ANON_KEY:', SUPABASE_ANON_KEY);

// Function to initialize Supabase client
function initializeSupabaseClient() {
    if (window.supabaseClient) {
        console.log('Supabase client already initialized');
        return;
    }

    console.log('Initializing Supabase client...');
    
    try {
        // Since we don't have real credentials, always use mock client for demo
        console.log('Using demo mode - Mock Supabase client');
        
        // Create a robust mock client for testing
        window.supabaseClient = {
            auth: {
                signInWithPassword: async (credentials) => {
                    console.log('Mock signInWithPassword called with:', credentials);
                    
                    // Simulate authentication delay
                    await new Promise(resolve => setTimeout(resolve, 800));
                    
                    // Mock successful login for demo purposes
                    if (credentials.email && credentials.password) {
                        console.log('Mock login successful');
                        
                        // Check if user exists in localStorage (simulating database)
                        const existingUsers = JSON.parse(localStorage.getItem('demoUsers') || '[]');
                        const user = existingUsers.find(u => u.email === credentials.email);
                        
                        if (user) {
                            return {
                                data: {
                                    user: {
                                        id: user.id,
                                        email: user.email,
                                        user_metadata: user.metadata || { role: 'user' }
                                    }
                                },
                                error: null
                            };
                        } else {
                            return {
                                data: null,
                                error: { message: 'User not found. Please register first.' }
                            };
                        }
                    } else {
                        console.log('Mock login failed - invalid credentials');
                        return {
                            data: null,
                            error: { message: 'Invalid credentials' }
                        };
                    }
                },
                signUp: async (credentials) => {
                    console.log('Mock signUp called with:', credentials);
                    console.log('Credentials received:', credentials);
                    
                    // Simulate registration delay
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    if (credentials.email && credentials.password) {
                        console.log('Mock registration successful');
                        
                        // Create a new user
                        const newUser = {
                            id: 'user-' + Date.now(),
                            email: credentials.email,
                            password: credentials.password, // In real app, this would be hashed
                            metadata: credentials.options?.data || {},
                            created_at: new Date().toISOString()
                        };
                        
                        // Store in localStorage (simulating database)
                        const existingUsers = JSON.parse(localStorage.getItem('demoUsers') || '[]');
                        existingUsers.push(newUser);
                        localStorage.setItem('demoUsers', JSON.stringify(existingUsers));
                        
                        console.log('Created mock user:', newUser);
                        console.log('Total users in demo database:', existingUsers.length);
                        
                        return {
                            data: {
                                user: {
                                    id: newUser.id,
                                    email: newUser.email,
                                    user_metadata: newUser.metadata
                                }
                            },
                            error: null
                        };
                    } else {
                        console.log('Mock registration failed - invalid data');
                        return {
                            data: null,
                            error: { message: 'Invalid registration data' }
                        };
                    }
                },
                signOut: async () => {
                    console.log('Mock signOut called');
                    // Clear any session data
                    localStorage.removeItem('currentUser');
                    return { error: null };
                }
            }
        };
        console.log('Demo Supabase client initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize Supabase client:', error);
        // Fallback: create a basic mock client
        window.supabaseClient = {
            auth: {
                signInWithPassword: async () => ({ data: null, error: { message: 'Supabase initialization failed' } }),
                signUp: async () => ({ data: null, error: { message: 'Supabase initialization failed' } }),
                signOut: async () => ({ error: { message: 'Supabase initialization failed' } })
            }
        };
        console.log('Fallback mock client created due to initialization error');
    }
    
    console.log('Final supabaseClient state:', window.supabaseClient);
}

// Initialize immediately
initializeSupabaseClient();

// Also initialize when DOM is ready (in case scripts load before DOM)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSupabaseClient);
} else {
    // DOM is already ready
    initializeSupabaseClient();
}

// Export for use in other scripts
window.initializeSupabaseClient = initializeSupabaseClient;
