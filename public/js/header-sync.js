/**
 * Header Sync Module
 * Ensures consistent top buttons across pages without modifying HTML/CSS
 */

class HeaderSync {
    constructor() {
        this.baseUrl = window.location.origin;
        this.headerData = null;
        this.init();
    }

    init() {
        this.syncHeader();
        this.setupPeriodicSync();
        this.handleNavigationChanges();
    }

    async syncHeader() {
        try {
            const response = await fetch('/api/ui/header', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.headerData = data;
                    this.updateHeaderUI();
                    console.log('Header synced successfully');
                } else {
                    console.error('Header sync failed:', data.error);
                }
            } else {
                console.error('Header sync HTTP error:', response.status);
            }
        } catch (error) {
            console.error('Header sync error:', error);
            // Fallback to default header
            this.setDefaultHeader();
        }
    }

    updateHeaderUI() {
        if (!this.headerData) return;

        // Update navigation links
        this.updateNavigation();
        
        // Update user menu
        this.updateUserMenu();
        
        // Update header branding
        this.updateHeaderBranding();
        
        // Update mobile menu
        this.updateMobileMenu();
    }

    updateNavigation() {
        const nav = document.querySelector('nav, .nav-links, .navigation');
        if (!nav || !this.headerData.navigation) return;

        // Update main navigation items
        const navItems = this.headerData.navigation.main || [];
        navItems.forEach(item => {
            const existingLink = nav.querySelector(`a[href="${item.url}"]`);
            if (existingLink) {
                existingLink.textContent = item.text;
                existingLink.title = item.title || item.text;
                
                // Update active state
                if (item.url === window.location.pathname) {
                    existingLink.classList.add('active');
                } else {
                    existingLink.classList.remove('active');
                }
            }
        });

        // Update secondary navigation
        const secondaryNav = document.querySelector('.nav-secondary, .secondary-nav');
        if (secondaryNav && this.headerData.navigation.secondary) {
            this.updateSecondaryNavigation(secondaryNav, this.headerData.navigation.secondary);
        }
    }

    updateSecondaryNavigation(container, items) {
        // Clear existing secondary nav
        container.innerHTML = '';
        
        // Add new items
        items.forEach(item => {
            const link = document.createElement('a');
            link.href = item.url;
            link.textContent = item.text;
            link.className = item.class || 'nav-link';
            link.title = item.title || item.text;
            
            if (item.url === window.location.pathname) {
                link.classList.add('active');
            }
            
            container.appendChild(link);
        });
    }

    updateUserMenu() {
        if (!this.headerData.user_menu) return;

        const userMenu = document.querySelector('.user-menu, .user-nav, .auth-buttons');
        if (!userMenu) return;

        if (this.headerData.is_logged_in) {
            // User is logged in - show user menu
            this.showLoggedInMenu(userMenu);
        } else {
            // User is not logged in - show auth buttons
            this.showAuthButtons(userMenu);
        }
    }

    showLoggedInMenu(container) {
        const user = this.headerData.user_menu;
        
        container.innerHTML = `
            <div class="user-profile">
                <span class="user-name">${user.display_name || user.username}</span>
                <div class="user-avatar">
                    ${this.getUserInitials(user.display_name || user.username)}
                </div>
            </div>
            <div class="user-dropdown">
                <button class="dropdown-toggle">▼</button>
                <div class="dropdown-menu">
                    <a href="/dashboard_user.html" class="dropdown-item">Dashboard</a>
                    <a href="/profile.html" class="dropdown-item">Profile</a>
                    ${user.role === 'admin' ? '<a href="/admin_panel.html" class="dropdown-item">Admin Panel</a>' : ''}
                    ${user.role === 'judge' ? '<a href="/judge_panel.html" class="dropdown-item">Judge Panel</a>' : ''}
                    <a href="/api/auth/logout" class="dropdown-item logout">Logout</a>
                </div>
            </div>
        `;

        // Add dropdown functionality
        this.setupUserDropdown(container);
    }

    showAuthButtons(container) {
        container.innerHTML = `
            <a href="/login.html" class="btn btn-outline">Login</a>
            <a href="/register.html" class="btn btn-primary">Join Now</a>
        `;
    }

    getUserInitials(name) {
        if (!name) return 'U';
        return name.split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .join('')
            .substring(0, 2);
    }

    setupUserDropdown(container) {
        const toggle = container.querySelector('.dropdown-toggle');
        const menu = container.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                menu.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    }

    updateHeaderBranding() {
        if (!this.headerData.header) return;

        // Update logo/brand
        const logo = document.querySelector('.logo, .brand, .site-title');
        if (logo && this.headerData.header.brand) {
            logo.textContent = this.headerData.header.brand;
            logo.title = this.headerData.header.brand_title || this.headerData.header.brand;
        }

        // Update tagline
        const tagline = document.querySelector('.tagline, .brand-subtitle');
        if (tagline && this.headerData.header.tagline) {
            tagline.textContent = this.headerData.header.tagline;
        }
    }

    updateMobileMenu() {
        const mobileMenu = document.querySelector('.mobile-menu, .nav-mobile');
        if (!mobileMenu || !this.headerData.navigation.mobile) return;

        // Update mobile navigation
        const mobileItems = this.headerData.navigation.mobile;
        mobileMenu.innerHTML = '';

        mobileItems.forEach(item => {
            const link = document.createElement('a');
            link.href = item.url;
            link.textContent = item.text;
            link.className = 'mobile-nav-item';
            
            if (item.url === window.location.pathname) {
                link.classList.add('active');
            }
            
            mobileMenu.appendChild(link);
        });

        // Add mobile menu toggle functionality
        this.setupMobileMenuToggle();
    }

    setupMobileMenuToggle() {
        const toggle = document.querySelector('.mobile-menu-toggle, .hamburger');
        const menu = document.querySelector('.mobile-menu, .nav-mobile');
        
        if (toggle && menu) {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                menu.classList.toggle('show');
                toggle.classList.toggle('active');
            });
        }
    }

    setDefaultHeader() {
        // Fallback header when API fails
        const nav = document.querySelector('nav, .nav-links, .navigation');
        if (nav) {
            // Ensure basic navigation exists
            const basicNav = `
                <a href="/" class="nav-link">Home</a>
                <a href="/video_list.html" class="nav-link">Videos</a>
                <a href="/about.html" class="nav-link">About</a>
            `;
            
            if (!nav.querySelector('.nav-link')) {
                nav.innerHTML = basicNav;
            }
        }

        // Ensure auth buttons exist
        const authContainer = document.querySelector('.user-menu, .user-nav, .auth-buttons');
        if (authContainer && !authContainer.querySelector('.btn')) {
            authContainer.innerHTML = `
                <a href="/login.html" class="btn btn-outline">Login</a>
                <a href="/register.html" class="btn btn-primary">Join Now</a>
            `;
        }
    }

    setupPeriodicSync() {
        // Sync header every 5 minutes
        setInterval(() => {
            this.syncHeader();
        }, 5 * 60 * 1000);
    }

    handleNavigationChanges() {
        // Listen for navigation changes (SPA-like behavior)
        window.addEventListener('popstate', () => {
            setTimeout(() => this.syncHeader(), 100);
        });

        // Listen for custom navigation events
        window.addEventListener('navigation-change', () => {
            setTimeout(() => this.syncHeader(), 100);
        });
    }

    // Public method to force header refresh
    refresh() {
        this.syncHeader();
    }

    // Public method to get current header data
    getHeaderData() {
        return this.headerData;
    }

    // Public method to check if user is logged in
    isLoggedIn() {
        return this.headerData ? this.headerData.is_logged_in : false;
    }

    // Public method to get user role
    getUserRole() {
        return this.headerData ? this.headerData.user_role : null;
    }
}

// Initialize header sync when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.headerSync = new HeaderSync();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeaderSync;
}
