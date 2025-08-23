<?php
/**
 * Create Demo User Script
 * Creates a demo user with specified credentials
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Creating Demo User ===\n";

$username = 'demouser';
$password = 'demouser@123456:)';
$email = 'demo@bizdir.local';

// Check if user already exists
$user_id = username_exists($username);

if ($user_id) {
    echo "User '$username' already exists with ID: $user_id\n";
    echo "Updating password...\n";
    
    // Update password
    wp_set_password($password, $user_id);
    echo "✅ Password updated successfully!\n";
    
} else {
    echo "Creating new user '$username'...\n";
    
    // Create new user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        echo "❌ Error creating user: " . $user_id->get_error_message() . "\n";
    } else {
        echo "✅ User created successfully with ID: $user_id\n";
        
        // Set user role to administrator for demo purposes
        $user = new WP_User($user_id);
        $user->set_role('administrator');
        echo "✅ User role set to administrator\n";
    }
}

// Display user info
$user_info = get_userdata($user_id);
echo "\n=== User Information ===\n";
echo "Username: " . $user_info->user_login . "\n";
echo "Email: " . $user_info->user_email . "\n";
echo "Role: " . implode(', ', $user_info->roles) . "\n";
echo "Login URL: http://localhost:8888/wp-admin/\n";
echo "\n=== Login Credentials ===\n";
echo "Username: $username\n";
echo "Password: $password\n";

?>
