/**
 * User Management JavaScript
 */
(function($) {
    'use strict';

    const BizDirUserManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#biz-dir-login-form').on('submit', this.handleLogin);
            $('#biz-dir-register-form').on('submit', this.handleRegistration);
            $('#biz-dir-forgot-password-form').on('submit', this.handleForgotPassword);
            $('#biz-dir-profile-form').on('submit', this.handleProfileUpdate);
        },

        handleLogin: function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: bizDirUser.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biz_dir_login',
                    nonce: form.find('[name="nonce"]').val(),
                    username: form.find('[name="username"]').val(),
                    password: form.find('[name="password"]').val(),
                    remember: form.find('[name="remember"]').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        form.find('.error-message').text(response.data).show();
                    }
                }
            });
        },

        handleRegistration: function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: bizDirUser.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biz_dir_register',
                    nonce: form.find('[name="nonce"]').val(),
                    username: form.find('[name="username"]').val(),
                    email: form.find('[name="email"]').val(),
                    password: form.find('[name="password"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        form.find('.success-message')
                            .text(response.data.message)
                            .show();
                        form.find('.error-message').hide();
                    } else {
                        form.find('.error-message')
                            .text(response.data)
                            .show();
                        form.find('.success-message').hide();
                    }
                }
            });
        },

        handleForgotPassword: function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: bizDirUser.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'biz_dir_forgot_password',
                    nonce: form.find('[name="nonce"]').val(),
                    email: form.find('[name="email"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        form.find('.success-message')
                            .text(response.data.message)
                            .show();
                        form.find('.error-message').hide();
                    } else {
                        form.find('.error-message')
                            .text(response.data)
                            .show();
                        form.find('.success-message').hide();
                    }
                }
            });
        },

        handleProfileUpdate: function(e) {
            e.preventDefault();
            const form = $(this);

            $.ajax({
                url: bizDirUser.restUrl + '/user/profile',
                type: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', bizDirUser.nonce);
                },
                data: {
                    display_name: form.find('[name="display_name"]').val(),
                    notifications: {
                        review_replies: form.find('[name="notify_review_replies"]').is(':checked'),
                        business_updates: form.find('[name="notify_business_updates"]').is(':checked'),
                        moderation_status: form.find('[name="notify_moderation_status"]').is(':checked')
                    }
                },
                success: function(response) {
                    form.find('.success-message')
                        .text(response.message)
                        .show()
                        .delay(3000)
                        .fadeOut();
                }
            });
        }
    };

    $(document).ready(function() {
        BizDirUserManager.init();
    });

})(jQuery);
