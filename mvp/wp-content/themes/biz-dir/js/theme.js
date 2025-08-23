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
        initBusinessDirectory();
        initLoginPrompts();
        initShareFeatures();
    });
    
    /**
     * Initialize business directory specific features
     */
    function initBusinessDirectory() {
        initBusinessSearch();
        initCategoryFilters();
        initBusinessRatings();
        initContactActions();
    }
    
    /**
     * Initialize business search functionality
     */
    function initBusinessSearch() {
        const $searchForm = $('#businessSearchForm');
        const $searchResults = $('#searchResults');
        const $resultsGrid = $('#searchResultsGrid');
        
        if (!$searchForm.length) return;
        
        $searchForm.on('submit', function(e) {
            e.preventDefault();
            performBusinessSearch();
        });
        
        // Real-time search with debounce
        $('#businessQuery').on('input', debounce(function() {
            const query = $(this).val().trim();
            if (query.length >= 2) {
                performBusinessSearch();
            } else if (query.length === 0) {
                clearSearchResults();
            }
        }, 500));
        
        // Category filter change
        $('#businessCategory').on('change', function() {
            performBusinessSearch();
        });
        
        function performBusinessSearch() {
            const query = $('#businessQuery').val().trim();
            const category = $('#businessCategory').val();
            
            if (!query && !category) {
                clearSearchResults();
                return;
            }
            
            // Show loading state
            showSearchLoading();
            
            // AJAX search request
            $.ajax({
                url: bizDirTheme.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biz_dir_search',
                    nonce: bizDirTheme.nonce,
                    query: query,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        displaySearchResults(response.data);
                    } else {
                        showSearchError();
                    }
                },
                error: function() {
                    showSearchError();
                }
            });
        }
        
        function showSearchLoading() {
            $searchResults.show();
            $resultsGrid.html('<div class="search-loading">🔍 ' + bizDirTheme.strings.loading + '</div>');
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $searchResults.offset().top - 100
            }, 500);
        }
        
        function displaySearchResults(businesses) {
            if (!businesses || businesses.length === 0) {
                $resultsGrid.html('<div class="no-search-results">' +
                    '<h3>No businesses found</h3>' +
                    '<p>Try adjusting your search terms or browse our categories.</p>' +
                    '</div>');
                return;
            }
            
            let html = '';
            businesses.forEach(function(business) {
                html += createBusinessCardHTML(business);
            });
            
            $resultsGrid.html(html);
            
            // Announce to screen readers
            if (window.announceToScreenReader) {
                window.announceToScreenReader(businesses.length + ' businesses found');
            }
        }
        
        function showSearchError() {
            $resultsGrid.html('<div class="search-error">' +
                '<h3>Search Error</h3>' +
                '<p>' + bizDirTheme.strings.error + '</p>' +
                '</div>');
        }
        
        function clearSearchResults() {
            $searchResults.hide();
            $resultsGrid.empty();
        }
        
        function createBusinessCardHTML(business) {
            const contactInfo = business.contact_info ? JSON.parse(business.contact_info) : {};
            const rating = parseFloat(business.rating) || 0;
            
            return `
                <div class="business-card" data-business-id="${business.id}">
                    <div class="business-content">
                        <div class="business-header">
                            <h3 class="business-title">
                                <a href="/business/${business.slug}/">${business.name}</a>
                            </h3>
                            <span class="business-category">${business.category}</span>
                        </div>
                        <div class="business-excerpt">
                            ${business.description ? business.description.substring(0, 100) + '...' : ''}
                        </div>
                        <div class="business-meta">
                            ${rating > 0 ? `<div class="business-rating-small">${createStarRating(rating)}</div>` : ''}
                        </div>
                        <div class="business-contact-preview">
                            ${contactInfo.phone ? `
                                <div class="contact-item">
                                    <span class="contact-icon">📞</span>
                                    <a href="tel:${contactInfo.phone}">${contactInfo.phone}</a>
                                </div>
                            ` : ''}
                            ${contactInfo.address ? `
                                <div class="contact-item">
                                    <span class="contact-icon">📍</span>
                                    <span>${contactInfo.address.substring(0, 50)}...</span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="business-actions">
                            <a href="/business/${business.slug}/" class="btn btn-primary btn-small">View Details</a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function createStarRating(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            
            let html = '<div class="stars">';
            
            for (let i = 0; i < fullStars; i++) {
                html += '<span class="star">★</span>';
            }
            
            if (hasHalfStar) {
                html += '<span class="star half">★</span>';
            }
            
            for (let i = 0; i < emptyStars; i++) {
                html += '<span class="star empty">☆</span>';
            }
            
            html += '</div>';
            html += `<span class="rating-text">${rating.toFixed(1)}</span>`;
            
            return html;
        }
    }
    
    /**
     * Initialize category filter functionality
     */
    function initCategoryFilters() {
        const $sortBy = $('#sortBy');
        const $filterRating = $('#filterRating');
        const $filterPrice = $('#filterPrice');
        const $applyFilters = $('#applyFilters');
        
        if ($applyFilters.length) {
            $applyFilters.on('click', function() {
                applyBusinessFilters();
            });
        }
        
        // Auto-apply filters on change
        $sortBy.add($filterRating).add($filterPrice).on('change', function() {
            applyBusinessFilters();
        });
        
        function applyBusinessFilters() {
            const sort = $sortBy.val();
            const rating = $filterRating.val();
            const price = $filterPrice.val();
            
            // Add loading state
            $('.businesses-grid').addClass('loading');
            
            // This would typically reload the page with new parameters
            // or make an AJAX request to filter results
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            url.searchParams.set('rating', rating);
            url.searchParams.set('price', price);
            
            // Remove empty parameters
            if (!sort) url.searchParams.delete('sort');
            if (!rating) url.searchParams.delete('rating');
            if (!price) url.searchParams.delete('price');
            
            window.location.href = url.toString();
        }
    }
    
    /**
     * Initialize business ratings display
     */
    function initBusinessRatings() {
        $('.business-rating').each(function() {
            const $rating = $(this);
            const rating = parseFloat($rating.data('rating')) || 0;
            
            // Animate star fill on page load
            setTimeout(function() {
                $rating.addClass('animated');
            }, 500);
        });
    }
    
    /**
     * Initialize contact action buttons
     */
    function initContactActions() {
        // Phone number click tracking
        $('body').on('click', 'a[href^="tel:"]', function() {
            const phoneNumber = $(this).attr('href').replace('tel:', '');
            
            // Track phone click (for analytics)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact', {
                    'method': 'phone',
                    'phone_number': phoneNumber
                });
            }
            
            // Show feedback to user
            showContactFeedback($(this), 'Phone number copied to clipboard');
        });
        
        // Email click tracking
        $('body').on('click', 'a[href^="mailto:"]', function() {
            const email = $(this).attr('href').replace('mailto:', '');
            
            // Track email click
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact', {
                    'method': 'email',
                    'email': email
                });
            }
        });
        
        function showContactFeedback($element, message) {
            const $feedback = $('<span class="contact-feedback">' + message + '</span>');
            
            $element.after($feedback);
            
            setTimeout(function() {
                $feedback.fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        }
    }
    
    /**
     * Initialize login prompts for anonymous users
     */
    function initLoginPrompts() {
        const $modal = $('#loginModal');
        
        // Handle login prompt buttons
        $('body').on('click', '.login-prompt', function(e) {
            e.preventDefault();
            
            const action = $(this).data('action');
            showLoginModal(action);
        });
        
        // Modal close functionality
        $modal.find('.modal-close').on('click', function() {
            hideLoginModal();
        });
        
        // Close modal on overlay click
        $modal.on('click', function(e) {
            if (e.target === this) {
                hideLoginModal();
            }
        });
        
        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                hideLoginModal();
            }
        });
        
        function showLoginModal(action) {
            let title = 'Login Required';
            let message = 'Please login or register to access this feature.';
            
            switch(action) {
                case 'review':
                    title = 'Login to Write Review';
                    message = 'Login to write reviews and help others discover great businesses.';
                    break;
                case 'save':
                    title = 'Login to Save Business';
                    message = 'Login to save businesses to your favorites and access them anytime.';
                    break;
                case 'contact':
                    title = 'Login to View Contact Details';
                    message = 'Login to view full contact information for this business.';
                    break;
            }
            
            $modal.find('.modal-header h3').text(title);
            $modal.find('.modal-body p').text(message);
            $modal.show().addClass('show');
            
            // Focus management for accessibility
            $modal.find('.btn-primary').focus();
        }
        
        function hideLoginModal() {
            $modal.hide().removeClass('show');
        }
    }
    
    /**
     * Initialize share features
     */
    function initShareFeatures() {
        // Copy link functionality
        $('body').on('click', '.copy-link', function(e) {
            e.preventDefault();
            
            const url = $(this).data('url') || window.location.href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showShareFeedback($(e.target), 'Link copied!');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                showShareFeedback($(e.target), 'Link copied!');
            }
        });
        
        // Track social shares
        $('body').on('click', '.share-btn', function() {
            const platform = $(this).hasClass('facebook') ? 'facebook' :
                           $(this).hasClass('twitter') ? 'twitter' :
                           $(this).hasClass('whatsapp') ? 'whatsapp' : 'other';
            
            if (typeof gtag !== 'undefined') {
                gtag('event', 'share', {
                    'method': platform,
                    'content_type': 'business'
                });
            }
        });
        
        function showShareFeedback($element, message) {
            const originalText = $element.text();
            
            $element.text(message);
            
            setTimeout(function() {
                $element.text(originalText);
            }, 1500);
        }
    }
    
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
        if ($('.search-filters, .category-filters').length) {
            const $filters = $('.search-filters, .category-filters');
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
