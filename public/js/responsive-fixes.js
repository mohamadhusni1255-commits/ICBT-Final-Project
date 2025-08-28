/**
 * Responsive Fixes
 * Handles responsive design issues without modifying HTML/CSS
 */
class ResponsiveFixes {
    constructor() {
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth <= 992 && window.innerWidth > 768;
        this.init();
    }

    init() {
        this.fixViewport();
        this.fixNavigation();
        this.fixFormLayouts();
        this.fixButtonSizes();
        this.setupResizeListener();
        this.fixTableResponsiveness();
        this.fixModalResponsiveness();
    }

    fixViewport() {
        // Ensure viewport meta tag exists and is correct
        let viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) {
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            document.head.appendChild(viewport);
        }
        
        // Set appropriate viewport content
        if (this.isMobile) {
            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        } else {
            viewport.content = 'width=device-width, initial-scale=1.0';
        }
    }

    fixNavigation() {
        // Fix navigation layout on small screens
        const nav = document.querySelector('nav, .nav-links, .navigation');
        if (nav && this.isMobile) {
            // Add mobile navigation styles
            nav.style.flexDirection = 'column';
            nav.style.gap = '15px';
            nav.style.alignItems = 'center';
            
            // Fix logo positioning
            const logo = document.querySelector('.logo, .nav-logo');
            if (logo) {
                logo.style.marginBottom = '10px';
            }
        }
    }

    fixFormLayouts() {
        // Fix form layouts on small screens
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (this.isMobile) {
                // Ensure form inputs are properly sized on mobile
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type !== 'checkbox' && input.type !== 'radio') {
                        input.style.fontSize = '16px'; // Prevent zoom on iOS
                    }
                });
                
                // Fix form groups spacing
                const formGroups = form.querySelectorAll('.form-group');
                formGroups.forEach(group => {
                    group.style.marginBottom = '15px';
                });
            }
        });
    }

    fixButtonSizes() {
        // Ensure buttons are properly sized for touch devices
        const buttons = document.querySelectorAll('button, .btn, input[type="submit"], input[type="button"]');
        buttons.forEach(button => {
            if (this.isMobile) {
                // Ensure minimum touch target size (44px)
                const computedStyle = window.getComputedStyle(button);
                const height = parseInt(computedStyle.height);
                const minHeight = Math.max(height, 44);
                
                button.style.minHeight = minHeight + 'px';
                button.style.minWidth = '44px';
                button.style.padding = '12px 16px';
            }
        });
    }

    fixTableResponsiveness() {
        // Make tables responsive on small screens
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (this.isMobile) {
                // Add horizontal scroll wrapper
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    wrapper.style.cssText = 'overflow-x: auto; -webkit-overflow-scrolling: touch;';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            }
        });
    }

    fixModalResponsiveness() {
        // Fix modal positioning and sizing on small screens
        const modals = document.querySelectorAll('.modal, .popup, [role="dialog"]');
        modals.forEach(modal => {
            if (this.isMobile) {
                modal.style.width = '95%';
                modal.style.maxWidth = '95%';
                modal.style.margin = '10px auto';
                modal.style.maxHeight = '90vh';
                modal.style.overflowY = 'auto';
            }
        });
    }

    setupResizeListener() {
        // Handle window resize events
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.isMobile = window.innerWidth <= 768;
                this.isTablet = window.innerWidth <= 992 && window.innerWidth > 768;
                
                // Reapply fixes
                this.fixViewport();
                this.fixNavigation();
                this.fixFormLayouts();
                this.fixButtonSizes();
                this.fixTableResponsiveness();
                this.fixModalResponsiveness();
            }, 250);
        });
    }

    // Public method to check current device type
    getDeviceType() {
        if (this.isMobile) return 'mobile';
        if (this.isTablet) return 'tablet';
        return 'desktop';
    }

    // Public method to check if device is touch-enabled
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    // Public method to apply specific mobile fixes
    applyMobileFixes() {
        this.isMobile = true;
        this.fixViewport();
        this.fixNavigation();
        this.fixFormLayouts();
        this.fixButtonSizes();
        this.fixTableResponsiveness();
        this.fixModalResponsiveness();
    }

    // Public method to apply specific desktop fixes
    applyDesktopFixes() {
        this.isMobile = false;
        this.isTablet = false;
        this.fixViewport();
        this.fixNavigation();
        this.fixFormLayouts();
        this.fixButtonSizes();
        this.fixTableResponsiveness();
        this.fixModalResponsiveness();
    }
}

// Initialize responsive fixes when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.responsiveFixes = new ResponsiveFixes();
});
