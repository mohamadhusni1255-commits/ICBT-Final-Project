/**
 * Backend Integration
 * Handles all backend API calls and integration without modifying HTML/CSS
 */
class BackendIntegration {
    constructor() {
        this.baseUrl = window.location.origin;
        this.csrfToken = null;
        this.maxRetries = 1;
        this.init();
    }

    init() {
        this.loadCSRFToken();
        this.setupGlobalErrorHandling();
        this.setupRetryLogic();
    }

    async loadCSRFToken() {
        try {
            // For Supabase, we don't need CSRF tokens as it handles auth differently
            // Generate a simple session token for compatibility
            this.csrfToken = Math.random().toString(36).substring(2) + Date.now().toString(36);
            
            // Update meta tag if it exists
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                metaTag.setAttribute('content', this.csrfToken);
            }
            
            // Update hidden input if it exists
            const hiddenInput = document.getElementById('csrfToken');
            if (hiddenInput) {
                hiddenInput.value = this.csrfToken;
            }
            
            console.log('Session token generated for Supabase compatibility');
        } catch (error) {
            console.error('Failed to generate session token:', error);
        }
    }

    setupGlobalErrorHandling() {
        // Global error handler for fetch requests
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.showGlobalError('An unexpected error occurred. Please try again.');
        });
    }

    setupRetryLogic() {
        // For Supabase, we don't need to override fetch
        // Just log that we're using Supabase
        console.log('Using Supabase - fetch override disabled');
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async makeRequest(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${this.baseUrl}${endpoint}`;
        
        // Default headers
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        // Add CSRF token if available
        if (this.csrfToken && !headers['X-CSRF-Token']) {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
        
        // Default options
        const requestOptions = {
            credentials: 'include', // Include cookies for sessions
            ...options,
            headers
        };
        
        try {
            const response = await fetch(url, requestOptions);
            
            // Handle different response types
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                return { response, data };
            } else {
                const text = await response.text();
                return { response, data: text };
            }
        } catch (error) {
            console.error(`Request failed for ${endpoint}:`, error);
            throw error;
        }
    }

    async get(endpoint, options = {}) {
        return this.makeRequest(endpoint, { ...options, method: 'GET' });
    }

    async post(endpoint, data = {}, options = {}) {
        return this.makeRequest(endpoint, {
            ...options,
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data = {}, options = {}) {
        return this.makeRequest(endpoint, {
            ...options,
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint, options = {}) {
        return this.makeRequest(endpoint, { ...options, method: 'DELETE' });
    }

    async uploadFile(endpoint, file, options = {}) {
        const formData = new FormData();
        formData.append('file', file);
        
        // Add other data if provided
        if (options.data) {
            Object.keys(options.data).forEach(key => {
                formData.append(key, options.data[key]);
            });
        }
        
        const url = endpoint.startsWith('http') ? endpoint : `${this.baseUrl}${endpoint}`;
        
        const headers = {};
        if (this.csrfToken) {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: formData,
                credentials: 'include'
            });
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                return { response, data };
            } else {
                const text = await response.text();
                return { response, data: text };
            }
        } catch (error) {
            console.error(`File upload failed for ${endpoint}:`, error);
            throw error;
        }
    }

    showGlobalError(message) {
        // Create a global error notification
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef476f;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 300px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        errorDiv.textContent = message;
        
        document.body.appendChild(errorDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
        
        // Allow manual dismissal
        errorDiv.addEventListener('click', () => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        });
    }

    showGlobalSuccess(message) {
        // Create a global success notification
        const successDiv = document.createElement('div');
        successDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #06d6a0;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 300px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        successDiv.textContent = message;
        
        document.body.appendChild(successDiv);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 3000);
        
        // Allow manual dismissal
        successDiv.addEventListener('click', () => {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        });
    }

    // Utility method to check if user is authenticated
    async checkAuthStatus() {
        try {
            const { response, data } = await this.get('/api/auth/me');
            return response.ok ? data : null;
        } catch (error) {
            console.error('Failed to check auth status:', error);
            return null;
        }
    }

    // Utility method to logout
    async logout() {
        try {
            await this.post('/api/auth/logout');
            window.location.href = '/login.html';
        } catch (error) {
            console.error('Logout failed:', error);
            // Force redirect anyway
            window.location.href = '/login.html';
        }
    }

    // Utility method to refresh CSRF token
    async refreshCSRFToken() {
        await this.loadCSRFToken();
    }

    // Get current CSRF token
    getCSRFToken() {
        return this.csrfToken;
    }
}

// Initialize backend integration when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.backendIntegration = new BackendIntegration();
});
