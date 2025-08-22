/**
 * Business Directory Theme JavaScript
 * Mobile-first, responsive functionality
 */

(function($) {
    'use strict';
    
    // Initialize theme functionality
    $(document).ready(function() {
        initMobileMenu();
        initUserDropdown();
        initSearchEnhancements();
        initBusinessCards();
        initFilterToggle();
        initScrollToTop();
        initAccessibility();
    });
    
    /**
     * Initialize mobile menu toggle
     */
    function initMobileMenu() {
        const $menuToggle = $('.menu-toggle');
        const $menuContainer = $('.menu-container');
        
        $menuToggle.on('click', function() {
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            $menuContainer.toggleClass('active');
            
            // Animate menu icon
            $(this).toggleClass('active');
        });
        
        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.main-navigation').length) {
                $menuToggle.attr('aria-expanded', 'false');
                $menuContainer.removeClass('active');
                $menuToggle.removeClass('active');
            }
        });
        
        // Close menu on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $menuToggle.attr('aria-expanded', 'false');
                $menuContainer.removeClass('active');
                $menuToggle.removeClass('active');
            }
        });
    }
    
    /**
     * Initialize user dropdown menu
     */
    function initUserDropdown() {
        const $userMenu = $('.user-menu');
        
        // Handle click for mobile
        $userMenu.find('.user-menu-toggle').on('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const $dropdown = $(this).siblings('.user-dropdown');
                $dropdown.toggleClass('show');
            }
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.user-menu').length) {
                $('.user-dropdown').removeClass('show');
            }
        });
    }
    
    /**
     * Enhance search functionality
     */
    function initSearchEnhancements() {
        const $searchField = $('.search-field');
        
        // Add search suggestions (basic implementation)
        $searchField.on('input', debounce(function() {
            const query = $(this).val();
            if (query.length >= 3) {
                // This would typically fetch suggestions via AJAX
                // showSearchSuggestions(query);
            }
        }, 300));
        
        // Clear search on escape
        $searchField.on('keydown', function(e) {
            if (e.key === 'Escape') {
                $(this).val('').blur();
            }
        });
        
        // Focus search field on Ctrl+K or Cmd+K
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                $searchField.focus();
            }
        });
    }
    
    /**
     * Initialize business card interactions
     */
    function initBusinessCards() {
        // Add loading states for business cards
        $('.business-card').on('click', 'a', function() {
            const $card = $(this).closest('.business-card');
            $card.addClass('loading');
        });
        
        // Lazy load business images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const $img = $(entry.target);
                        const src = $img.data('src');
                        
                        if (src) {
                            $img.attr('src', src).removeAttr('data-src');
                            imageObserver.unobserve(entry.target);
                        }
                    }
                });
            });
            
            $('.business-image[data-src]').each(function() {
                imageObserver.observe(this);
            });
        }
        
        // Add touch feedback for mobile
        $('.business-card').on('touchstart', function() {
            $(this).addClass('touching');
        }).on('touchend touchcancel', function() {
            $(this).removeClass('touching');
        });
    }
    
    /**
     * Initialize filter toggle for mobile
     */
    function initFilterToggle() {
        // Create mobile filter toggle if filters exist
        if ($('.search-filters').length) {
            const $filters = $('.search-filters');
            const $toggleBtn = $('<button class="filter-toggle mobile-only">' +
                '<span>Filters</span>' +
                '<svg width="16" height="16" viewBox="0 0 16 16">' +
                '<path d="M3 6h10M3 10h10M3 14h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
                '</svg>' +
                '</button>');
            
            $filters.before($toggleBtn);
            
            $toggleBtn.on('click', function() {
                $filters.toggleClass('mobile-expanded');
                $(this).toggleClass('active');
            });
        }
    }
    
    /**
     * Initialize scroll to top functionality
     */
    function initScrollToTop() {
        // Create scroll to top button
        const $scrollBtn = $('<button class="scroll-to-top" aria-label="Scroll to top">' +
            '<svg width="20" height="20" viewBox="0 0 20 20">' +
            '<path d="M10 3l7 7-1.41 1.41L10 5.83l-5.59 5.58L3 10l7-7z" fill="currentColor"/>' +
            '</svg>' +
            '</button>');
        
        $('body').append($scrollBtn);
        
        // Show/hide based on scroll position
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 300) {
                $scrollBtn.addClass('visible');
            } else {
                $scrollBtn.removeClass('visible');
            }
        });
        
        // Smooth scroll to top
        $scrollBtn.on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 500);
        });
    }
    
    /**
     * Initialize accessibility features
     */
    function initAccessibility() {
        // Add focus indicators for keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.key === 'Tab') {
                $('body').addClass('using-keyboard');
            }
        });
        
        $(document).on('mousedown', function() {
            $('body').removeClass('using-keyboard');
        });
        
        // Skip links functionality
        $('.skip-link').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            $(target).focus();
        });
        
        // Announce dynamic content changes to screen readers
        function announceToScreenReader(message) {
            const $announcement = $('<div>', {
                'aria-live': 'polite',
                'aria-atomic': 'true',
                'class': 'screen-reader-text',
                'text': message
            });
            
            $('body').append($announcement);
            
            setTimeout(function() {
                $announcement.remove();
            }, 1000);
        }
        
        // Make announcements available globally
        window.announceToScreenReader = announceToScreenReader;
    }
    
    /**
     * Debounce function for performance
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Throttle function for scroll events
     */
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * Show search suggestions (placeholder)
     */
    function showSearchSuggestions(query) {
        // This would typically make an AJAX request to get suggestions
        // and display them in a dropdown below the search field
        console.log('Searching for:', query);
    }
    
    /**
     * Form validation helpers
     */
    function validateForm($form) {
        let isValid = true;
        
        $form.find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                markFieldAsInvalid($field, 'This field is required');
                isValid = false;
            } else {
                markFieldAsValid($field);
            }
        });
        
        return isValid;
    }
    
    function markFieldAsInvalid($field, message) {
        $field.addClass('invalid');
        
        let $error = $field.next('.field-error');
        if (!$error.length) {
            $error = $('<span class="field-error"></span>');
            $field.after($error);
        }
        
        $error.text(message);
    }
    
    function markFieldAsValid($field) {
        $field.removeClass('invalid');
        $field.next('.field-error').remove();
    }
    
    // Make validation functions available globally
    window.validateForm = validateForm;
    window.markFieldAsInvalid = markFieldAsInvalid;
    window.markFieldAsValid = markFieldAsValid;
    
})(jQuery);

// Add CSS for additional functionality
const additionalCSS = `
<style>
/* Mobile filter toggle */
.filter-toggle.mobile-only {
    display: none;
    width: 100%;
    padding: 15px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    margin-bottom: 15px;
    cursor: pointer;
}

.filter-toggle.mobile-only svg {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.filter-toggle.mobile-only.active svg {
    transform: rotate(180deg);
}

/* Scroll to top button */
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.scroll-to-top:hover {
    background: #005a87;
    transform: translateY(-2px);
}

/* Loading states */
.business-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.business-card.touching {
    transform: scale(0.98);
}

/* Focus indicators for accessibility */
.using-keyboard *:focus {
    outline: 2px solid #007cba;
    outline-offset: 2px;
}

/* Form validation styles */
.invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.field-error {
    display: block;
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .filter-toggle.mobile-only {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .search-filters {
        display: none;
    }
    
    .search-filters.mobile-expanded {
        display: block;
    }
    
    .scroll-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
    }
    
    .user-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
}

@media (max-width: 480px) {
    .scroll-to-top {
        bottom: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
    }
}
</style>
`;

// Inject additional CSS
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalCSS);
}
