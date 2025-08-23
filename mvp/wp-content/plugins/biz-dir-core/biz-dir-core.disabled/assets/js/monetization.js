/**
 * Monetization Frontend JavaScript
 * Handles payment forms, subscription selection, and ad placement
 */

(function($) {
    'use strict';
    
    // Initialize monetization features
    $(document).ready(function() {
        initializePaymentForm();
        initializeSubscriptionPlans();
        initializeAdPlacementForm();
        initializeAdTracking();
    });
    
    /**
     * Initialize payment form
     */
    function initializePaymentForm() {
        $('#biz-dir-payment-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const businessId = $form.data('business-id');
            const plan = $form.data('plan');
            const gateway = $form.find('input[name="gateway"]:checked').val();
            
            if (!gateway) {
                alert('Please select a payment gateway');
                return;
            }
            
            // Disable submit button
            const $submitBtn = $form.find('.payment-submit-btn');
            $submitBtn.prop('disabled', true).text('Processing...');
            
            // Create payment intent
            $.ajax({
                url: bizDirMonetization.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biz_dir_create_payment_intent',
                    business_id: businessId,
                    plan: plan,
                    gateway: gateway,
                    nonce: bizDirMonetization.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to payment gateway
                        initiatePayment(gateway, response.data);
                    } else {
                        alert('Error: ' + response.data.message);
                        $submitBtn.prop('disabled', false).text('Pay ₹' + response.data.amount);
                    }
                },
                error: function() {
                    alert('Payment processing failed. Please try again.');
                    $submitBtn.prop('disabled', false).text('Pay ₹' + $form.data('amount'));
                }
            });
        });
    }
    
    /**
     * Initialize subscription plans
     */
    function initializeSubscriptionPlans() {
        $('.select-plan-btn').on('click', function(e) {
            e.preventDefault();
            
            const plan = $(this).data('plan');
            const businessId = getBusinessIdFromContext();
            
            if (!businessId) {
                alert('Please select a business first');
                return;
            }
            
            // Show payment form for selected plan
            showPaymentForm(businessId, plan);
        });
    }
    
    /**
     * Initialize ad placement form
     */
    function initializeAdPlacementForm() {
        const $form = $('#biz-dir-ad-form');
        
        // Calculate cost when dates or slot changes
        $form.find('#ad_slot, #start_date, #end_date').on('change', function() {
            calculateAdCost();
        });
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'biz_dir_create_ad_placement');
            formData.append('nonce', bizDirMonetization.nonce);
            
            $.ajax({
                url: bizDirMonetization.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Ad placement created successfully! Total cost: ₹' + response.data.total_cost);
                        $form[0].reset();
                        $('#total-cost').text('₹0');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to create ad placement. Please try again.');
                }
            });
        });
    }
    
    /**
     * Initialize ad click tracking
     */
    function initializeAdTracking() {
        // Track ad clicks
        $(document).on('click', '.biz-dir-ad .ad-cta', function() {
            const $ad = $(this).closest('.biz-dir-ad');
            const placementId = $ad.data('placement-id');
            
            if (placementId) {
                $.post(bizDirMonetization.ajaxUrl, {
                    action: 'biz_dir_track_ad_click',
                    placement_id: placementId
                });
            }
        });
        
        // Track ad impressions (simplified - when ad enters viewport)
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const $ad = $(entry.target);
                        const placementId = $ad.data('placement-id');
                        
                        if (placementId && !$ad.data('impression-tracked')) {
                            $ad.data('impression-tracked', true);
                            
                            $.post(bizDirMonetization.ajaxUrl, {
                                action: 'biz_dir_track_ad_impression',
                                placement_id: placementId
                            });
                        }
                    }
                });
            }, { threshold: 0.5 });
            
            $('.biz-dir-ad').each(function() {
                observer.observe(this);
            });
        }
    }
    
    /**
     * Calculate ad placement cost
     */
    function calculateAdCost() {
        const $slotSelect = $('#ad_slot');
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        if (!$slotSelect.val() || !startDate || !endDate) {
            $('#total-cost').text('₹0');
            return;
        }
        
        const pricePerMonth = parseFloat($slotSelect.find(':selected').data('price')) || 0;
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end <= start) {
            $('#total-cost').text('₹0');
            return;
        }
        
        // Calculate months difference
        const months = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24 * 30)));
        const totalCost = pricePerMonth * months;
        
        $('#total-cost').text('₹' + totalCost.toLocaleString());
    }
    
    /**
     * Initiate payment with selected gateway
     */
    function initiatePayment(gateway, paymentData) {
        switch(gateway) {
            case 'razorpay':
                initiateRazorpayPayment(paymentData);
                break;
            case 'payu':
                initiatePayUPayment(paymentData);
                break;
            case 'stripe':
                initiateStripePayment(paymentData);
                break;
            default:
                alert('Payment gateway not supported');
        }
    }
    
    /**
     * Initiate Razorpay payment
     */
    function initiateRazorpayPayment(paymentData) {
        // This would integrate with Razorpay's JavaScript SDK
        // For demo purposes, we'll simulate the payment
        
        const options = {
            key: 'rzp_test_1234567890', // Replace with actual Razorpay key
            amount: paymentData.amount * 100, // Amount in paise
            currency: paymentData.currency,
            name: 'Business Directory',
            description: 'Sponsored Listing',
            order_id: paymentData.payment_id,
            handler: function(response) {
                // Verify payment on server
                verifyPayment('razorpay', {
                    payment_id: response.razorpay_payment_id,
                    order_id: response.razorpay_order_id,
                    signature: response.razorpay_signature
                }, paymentData.payment_id);
            },
            modal: {
                ondismiss: function() {
                    // Re-enable submit button
                    $('.payment-submit-btn').prop('disabled', false).text('Pay ₹' + paymentData.amount);
                }
            }
        };
        
        // In a real implementation, you would load Razorpay script and use it
        // const rzp = new Razorpay(options);
        // rzp.open();
        
        // For demo, simulate successful payment
        setTimeout(function() {
            verifyPayment('razorpay', {
                payment_id: 'pay_demo_' + Date.now(),
                order_id: paymentData.payment_id,
                signature: 'demo_signature'
            }, paymentData.payment_id);
        }, 2000);
    }
    
    /**
     * Initiate PayU payment
     */
    function initiatePayUPayment(paymentData) {
        // PayU integration would go here
        // For demo, simulate payment
        setTimeout(function() {
            verifyPayment('payu', {
                status: 'success',
                txnid: paymentData.payment_id,
                amount: paymentData.amount,
                hash: 'demo_hash'
            }, paymentData.payment_id);
        }, 2000);
    }
    
    /**
     * Initiate Stripe payment
     */
    function initiateStripePayment(paymentData) {
        // Stripe integration would go here
        // For demo, simulate payment
        setTimeout(function() {
            verifyPayment('stripe', {
                payment_intent_id: 'pi_demo_' + Date.now()
            }, paymentData.payment_id);
        }, 2000);
    }
    
    /**
     * Verify payment on server
     */
    function verifyPayment(gateway, transactionData, paymentId) {
        $.ajax({
            url: bizDirMonetization.ajaxUrl,
            type: 'POST',
            data: {
                action: 'biz_dir_process_payment',
                payment_id: paymentId,
                gateway: gateway,
                transaction_data: transactionData,
                nonce: bizDirMonetization.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showPaymentSuccess();
                } else {
                    alert('Payment verification failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('Payment verification failed. Please contact support.');
            },
            complete: function() {
                // Re-enable submit button
                $('.payment-submit-btn').prop('disabled', false);
            }
        });
    }
    
    /**
     * Show payment success message
     */
    function showPaymentSuccess() {
        const successHtml = `
            <div class="payment-success">
                <h3>Payment Successful!</h3>
                <p>Your business has been sponsored successfully. It may take a few minutes to reflect in search results.</p>
                <button type="button" onclick="location.reload()">Continue</button>
            </div>
        `;
        
        $('.biz-dir-payment-form').html(successHtml);
    }
    
    /**
     * Show payment form for selected plan
     */
    function showPaymentForm(businessId, plan) {
        // This would typically redirect to a dedicated payment page
        // or show a modal with the payment form
        const url = `/business-payment/?business_id=${businessId}&plan=${plan}`;
        window.location.href = url;
    }
    
    /**
     * Get business ID from current context
     */
    function getBusinessIdFromContext() {
        // This would extract business ID from URL, data attributes, or global variables
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('business_id') || 
               $('body').data('business-id') || 
               $('#current-business-id').val();
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return bizDirMonetization.currencySymbol + amount.toLocaleString();
    }
    
    /**
     * Utility function to show loading state
     */
    function showLoading($element, text = 'Loading...') {
        $element.prop('disabled', true).text(text);
    }
    
    /**
     * Utility function to hide loading state
     */
    function hideLoading($element, originalText) {
        $element.prop('disabled', false).text(originalText);
    }
    
})(jQuery);
