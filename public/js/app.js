/**
 * ============================================
 * MAIN APPLICATION JAVASCRIPT
 * ============================================
 * 
 * Core JavaScript functionality for the application
 * Loaded on all pages
 * 
 * Features:
 * - CSRF token setup for AJAX requests
 * - User menu toggle
 * - Mobile menu toggle
 * - Alert auto-dismiss
 * - Dropdown menu handling
 */

(function() {
    'use strict';

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        setupCSRFToken();
        initUserMenu();
        initMobileMenu();
        initAlertAutoDismiss();
        initDropdowns();
    });

    /**
     * Setup CSRF Token for AJAX Requests
     * Adds CSRF token to all AJAX requests automatically
     */
    function setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        
        if (token) {
            window.csrfToken = token.getAttribute('content');
            
            // Setup for fetch API
            window.fetchWithCSRF = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = window.csrfToken;
                options.headers['Accept'] = 'application/json';
                options.headers['Content-Type'] = 'application/json';
                
                return fetch(url, options);
            };
        }
    }

    /**
     * User Menu Toggle
     * Shows/hides user dropdown menu
     */
    function initUserMenu() {
        const userToggle = document.getElementById('userMenuToggle');
        const userMenu = document.getElementById('userMenu');

        if (!userToggle || !userMenu) return;

        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }

    /**
     * Mobile Menu Toggle
     * Shows/hides navigation menu on mobile devices
     */
    function initMobileMenu() {
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const navbarMenu = document.querySelector('.navbar-menu');

        if (!mobileToggle || !navbarMenu) return;

        mobileToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('show');
            this.classList.toggle('active');
        });
    }

    /**
     * Alert Auto Dismiss
     * Automatically dismisses alert messages after 5 seconds
     */
    function initAlertAutoDismiss() {
        const alerts = document.querySelectorAll('.alert');

        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease-out';
                
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    }

    /**
     * Dropdown Menu Handling
     * Handles dropdown menu interactions
     */
    function initDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');

        dropdowns.forEach(function(dropdown) {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');

            if (!toggle || !menu) return;

            // Show on hover (desktop)
            dropdown.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768) {
                    menu.style.display = 'block';
                }
            });

            dropdown.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768) {
                    menu.style.display = 'none';
                }
            });

            // Toggle on click (mobile)
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const isVisible = menu.style.display === 'block';
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(function(m) {
                        m.style.display = 'none';
                    });
                    
                    menu.style.display = isVisible ? 'none' : 'block';
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.style.display = 'none';
                });
            }
        });
    }

})();
