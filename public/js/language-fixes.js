/**
 * Language Fixes
 * Handles language switching and internationalization without modifying HTML/CSS
 */
class LanguageFixes {
    constructor() {
        this.currentLanguage = localStorage.getItem('preferredLanguage') || 'en';
        this.translations = {};
        this.init();
    }

    init() {
        this.loadLanguage(this.currentLanguage);
        this.setupLanguageToggle();
    }

    async loadLanguage(lang) {
        try {
            // For now, use hardcoded translations to avoid fetch issues
            // In production, you can load from Supabase or static files
            this.translations = this.getDefaultTranslations(lang);
            this.applyTranslations();
            this.currentLanguage = lang;
            localStorage.setItem('preferredLanguage', lang);
            console.log(`Language loaded: ${lang}`);
        } catch (error) {
            console.error('Failed to load language:', error);
            // Fallback to English
            if (lang !== 'en') {
                this.loadLanguage('en');
            }
        }
    }

    getDefaultTranslations(lang) {
        // Basic translations for common UI elements
        const translations = {
            en: {
                'login': 'Login',
                'register': 'Register',
                'email': 'Email',
                'password': 'Password',
                'username': 'Username',
                'submit': 'Submit',
                'cancel': 'Cancel',
                'save': 'Save',
                'delete': 'Delete',
                'edit': 'Edit',
                'view': 'View',
                'upload': 'Upload',
                'search': 'Search',
                'loading': 'Loading...',
                'error': 'Error',
                'success': 'Success',
                'home': 'Home',
                'dashboard': 'Dashboard',
                'profile': 'Profile',
                'settings': 'Settings',
                'logout': 'Logout'
            },
            si: {
                'login': 'පිවිසෙන්න',
                'register': 'ලියාපදිංචි වන්න',
                'email': 'විද්‍යුත් තැපෑල',
                'password': 'මුරපදය',
                'username': 'පරිශීලක නාමය',
                'submit': 'ඉදිරිපත් කරන්න',
                'cancel': 'අවලංගු කරන්න',
                'save': 'සුරකින්න',
                'delete': 'මකන්න',
                'edit': 'සංස්කරණය කරන්න',
                'view': 'බලන්න',
                'upload': 'උඩුගත කරන්න',
                'search': 'සොයන්න',
                'loading': 'පූරණය වෙමින්...',
                'error': 'දෝෂය',
                'success': 'සාර්ථකයි',
                'home': 'මුල් පිටුව',
                'dashboard': 'උපකරණ පුවරුව',
                'profile': 'පැතිකඩ',
                'settings': 'සැකසුම්',
                'logout': 'පිටවීම'
            }
        };
        
        return translations[lang] || translations['en'];
    }

    applyTranslations() {
        // Apply translations to elements with data-i18n attribute
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            if (this.translations[key]) {
                element.textContent = this.translations[key];
            }
        });

        // Apply translations to placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.getAttribute('data-i18n-placeholder');
            if (this.translations[key]) {
                element.placeholder = this.translations[key];
            }
        });

        // Apply translations to title attributes
        document.querySelectorAll('[data-i18n-title]').forEach(element => {
            const key = element.getAttribute('data-i18n-title');
            if (this.translations[key]) {
                element.title = this.translations[key];
            }
        });
    }

    setupLanguageToggle() {
        // Find language toggle elements and attach event listeners
        const languageToggles = document.querySelectorAll('[data-language-toggle]');
        languageToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const lang = toggle.getAttribute('data-language-toggle');
                this.switchLanguage(lang);
            });
        });
    }

    switchLanguage(lang) {
        this.loadLanguage(lang);
        
        // Update active state of language toggles
        document.querySelectorAll('[data-language-toggle]').forEach(toggle => {
            toggle.classList.remove('active');
            if (toggle.getAttribute('data-language-toggle') === lang) {
                toggle.classList.add('active');
            }
        });
    }

    getCurrentLanguage() {
        return this.currentLanguage;
    }

    getTranslation(key, fallback = '') {
        return this.translations[key] || fallback;
    }
}

// Initialize language fixes when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.languageFixes = new LanguageFixes();
});
