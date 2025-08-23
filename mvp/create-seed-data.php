<?php
/**
 * BizDir Seed Data Creation Script
 * Creates realistic Indian business directory data through WordPress functions
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

// Disable WordPress cron during import
define('DOING_CRON', true);

echo "🌱 Starting BizDir Seed Data Creation...\n\n";

// ==============================================
// 1. CREATE BUSINESS CATEGORIES
// ==============================================

echo "📂 Creating business categories...\n";

$categories = [
    ['name' => 'Restaurants & Food', 'description' => 'Restaurants, cloud kitchens, food delivery, catering services'],
    ['name' => 'Health & Fitness', 'description' => 'Gyms, yoga centers, healthcare, medical services'],
    ['name' => 'Education & Training', 'description' => 'Tuition teachers, coaching centers, skill training'],
    ['name' => 'Home Services', 'description' => 'Electricians, carpenters, plumbers, cleaning services'],
    ['name' => 'Shopping & Retail', 'description' => 'Furniture, home decor, electronics, grocery stores'],
    ['name' => 'Professional Services', 'description' => 'Lawyers, accountants, consultants, tech services'],
    ['name' => 'Automotive', 'description' => 'Drivers, mechanics, car dealers, auto services'],
    ['name' => 'Beauty & Wellness', 'description' => 'Salons, spas, beauty treatments, wellness centers'],
    ['name' => 'Travel & Tourism', 'description' => 'Travel agencies, hotels, tour operators, transport'],
    ['name' => 'Real Estate', 'description' => 'Property dealers, builders, architects, interior designers']
];

$category_ids = [];
foreach ($categories as $category) {
    $result = wp_insert_term($category['name'], 'category', [
        'description' => $category['description'],
        'slug' => sanitize_title($category['name'])
    ]);
    
    if (!is_wp_error($result)) {
        $category_ids[$category['name']] = $result['term_id'];
        echo "   ✅ Created: {$category['name']}\n";
    } else {
        echo "   ❌ Error creating {$category['name']}: " . $result->get_error_message() . "\n";
    }
}

// ==============================================
// 2. CREATE SUB-CATEGORIES
// ==============================================

echo "\n📁 Creating sub-categories...\n";

$sub_categories = [
    // Restaurant sub-categories
    ['name' => 'Cloud Kitchen', 'parent' => 'Restaurants & Food'],
    ['name' => 'Street Food', 'parent' => 'Restaurants & Food'],
    ['name' => 'Fine Dining', 'parent' => 'Restaurants & Food'],
    
    // Home Services sub-categories
    ['name' => 'Electrician', 'parent' => 'Home Services'],
    ['name' => 'Carpenter', 'parent' => 'Home Services'],
    ['name' => 'Plumber', 'parent' => 'Home Services'],
    ['name' => 'Kabadi Wala (Scrap Dealer)', 'parent' => 'Home Services'],
    
    // Education sub-categories
    ['name' => 'Tuition Teacher', 'parent' => 'Education & Training'],
    ['name' => 'Coaching Center', 'parent' => 'Education & Training'],
    
    // Shopping sub-categories
    ['name' => 'Furniture Store', 'parent' => 'Shopping & Retail'],
    ['name' => 'Home Decor', 'parent' => 'Shopping & Retail'],
    ['name' => 'Sabzi Wala (Vegetable Vendor)', 'parent' => 'Shopping & Retail'],
    
    // Health & Fitness sub-categories
    ['name' => 'Gym & Fitness', 'parent' => 'Health & Fitness'],
    ['name' => 'Yoga Center', 'parent' => 'Health & Fitness'],
    ['name' => 'Medical Clinic', 'parent' => 'Health & Fitness']
];

foreach ($sub_categories as $sub_cat) {
    $parent_id = isset($category_ids[$sub_cat['parent']]) ? $category_ids[$sub_cat['parent']] : 0;
    
    $result = wp_insert_term($sub_cat['name'], 'category', [
        'parent' => $parent_id,
        'slug' => sanitize_title($sub_cat['name'])
    ]);
    
    if (!is_wp_error($result)) {
        echo "   ✅ Created: {$sub_cat['name']} (under {$sub_cat['parent']})\n";
    } else {
        echo "   ❌ Error creating {$sub_cat['name']}: " . $result->get_error_message() . "\n";
    }
}

// ==============================================
// 3. CREATE BUSINESS LISTINGS
// ==============================================

echo "\n🏪 Creating business listings...\n";

$businesses = [
    [
        'title' => 'Sharma Ji Ka Dhaba',
        'content' => 'Delicious North Indian cuisine with home-style cooking. We specialize in fresh rotis, dal makhani, and authentic Punjabi dishes. Family-friendly atmosphere with quick service.

<strong>Specialties:</strong>
• Fresh Rotis & Naans
• Dal Makhani & Paneer dishes
• Biryani & Pulao
• Fresh Salads & Raita

<strong>Timings:</strong> 11:00 AM - 10:00 PM
<strong>Contact:</strong> +91-9876543210',
        'excerpt' => 'Authentic North Indian home-style food with fresh ingredients and family recipes',
        'category' => 'Restaurants & Food',
        'sub_category' => 'Street Food',
        'meta' => [
            '_business_phone' => '+91-9876543210',
            '_business_address' => 'Shop 12, Main Market, Sector 21, Gurgaon, Haryana 122016',
            '_business_email' => 'sharmajikad@example.com',
            '_business_hours' => 'Mon-Sun: 11:00 AM - 10:00 PM',
            '_business_rating' => '4.2',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Dine-in, Takeaway, Family-friendly'
        ]
    ],
    [
        'title' => 'Fresh Bowl Kitchen',
        'content' => 'Modern cloud kitchen specializing in healthy meals, salads, and continental cuisine. All dishes prepared fresh with quality ingredients.

<strong>Menu Highlights:</strong>
• Healthy Salad Bowls
• Grilled Sandwiches & Wraps
• Pasta & Continental dishes
• Fresh Fruit Juices

<strong>Services:</strong> Delivery Only
<strong>Order:</strong> Zomato, Swiggy, Direct Call',
        'excerpt' => 'Healthy cloud kitchen with fresh salads and continental cuisine',
        'category' => 'Restaurants & Food',
        'sub_category' => 'Cloud Kitchen',
        'meta' => [
            '_business_phone' => '+91-9876543211',
            '_business_address' => 'Cloud Kitchen, Sector 18, Gurgaon, Haryana',
            '_business_email' => 'orders@freshbowl.com',
            '_business_website' => 'www.freshbowlkitchen.com',
            '_business_hours' => 'Mon-Sun: 10:00 AM - 11:00 PM',
            '_business_rating' => '4.0',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Delivery only, Online ordering, Healthy options'
        ]
    ],
    [
        'title' => 'Rajesh Electrical Works',
        'content' => 'Professional electrical services for homes and offices. Licensed electrician with 10+ years experience. Available for emergency repairs, new installations, and maintenance work.

<strong>Services:</strong>
• House Wiring & Rewiring
• Fan & Light Installation
• AC Point Installation
• Emergency Repairs (24/7)
• Electrical Safety Checks

<strong>Experience:</strong> 10+ Years
<strong>License:</strong> Certified Electrician',
        'excerpt' => 'Professional electrical services for homes and offices with 24/7 emergency support',
        'category' => 'Home Services',
        'sub_category' => 'Electrician',
        'meta' => [
            '_business_phone' => '+91-9876543212',
            '_business_address' => 'Service Area: Gurgaon, Delhi NCR',
            '_business_email' => 'rajesh.electrical@example.com',
            '_business_hours' => '24/7 Emergency Service Available',
            '_business_rating' => '4.5',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Licensed, 24/7 Emergency, Home visits'
        ]
    ],
    [
        'title' => 'Modern Wood Craft',
        'content' => 'Expert carpenter for custom furniture, repairs, and woodwork. Specializing in modern designs and space-saving solutions for homes and offices.

<strong>Specialties:</strong>
• Custom Furniture Design
• Kitchen Cabinets & Wardrobes
• Furniture Repair & Polish
• Modular Furniture
• Office Furniture

<strong>Experience:</strong> 15+ Years
<strong>Free Consultation</strong>',
        'excerpt' => 'Custom furniture and woodwork solutions with modern designs',
        'category' => 'Home Services',
        'sub_category' => 'Carpenter',
        'meta' => [
            '_business_phone' => '+91-9876543213',
            '_business_address' => 'Workshop: Industrial Area, Gurgaon',
            '_business_email' => 'modernwoodcraft@example.com',
            '_business_hours' => 'Mon-Sat: 9:00 AM - 6:00 PM',
            '_business_rating' => '4.3',
            '_business_price_range' => '₹₹₹',
            '_business_features' => 'Custom design, Free consultation, Quality materials'
        ]
    ],
    [
        'title' => 'Priya Maths Tuition',
        'content' => 'Experienced mathematics teacher offering home tuition for classes 6-12. Specialized in CBSE, ICSE, and competitive exam preparation.

<strong>Subjects:</strong>
• Mathematics (Class 6-12)
• Physics (Class 11-12)
• JEE/NEET Preparation
• Board Exam Preparation

<strong>Experience:</strong> 12 Years Teaching
<strong>Qualification:</strong> M.Sc Mathematics, B.Ed',
        'excerpt' => 'Expert mathematics tuition for classes 6-12 with competitive exam preparation',
        'category' => 'Education & Training',
        'sub_category' => 'Tuition Teacher',
        'meta' => [
            '_business_phone' => '+91-9876543214',
            '_business_address' => 'Home tuition across Gurgaon',
            '_business_email' => 'priya.mathtutor@example.com',
            '_business_hours' => 'Mon-Sat: 4:00 PM - 8:00 PM',
            '_business_rating' => '4.8',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Home visits, Proven results, Exam preparation'
        ]
    ],
    [
        'title' => 'FitZone Gym & Fitness',
        'content' => 'Modern fitness center with latest equipment, certified trainers, and flexible membership plans. Focus on strength training, cardio, and functional fitness.

<strong>Facilities:</strong>
• Latest Gym Equipment
• Certified Personal Trainers
• Group Fitness Classes
• Steam & Sauna
• Nutrition Counseling

<strong>Timings:</strong> 5:00 AM - 11:00 PM
<strong>Membership:</strong> Monthly/Quarterly/Yearly plans',
        'excerpt' => 'Modern fitness center with certified trainers and comprehensive facilities',
        'category' => 'Health & Fitness',
        'sub_category' => 'Gym & Fitness',
        'meta' => [
            '_business_phone' => '+91-9876543215',
            '_business_address' => '2nd Floor, City Mall, Sector 29, Gurgaon',
            '_business_email' => 'info@fitzonegym.com',
            '_business_website' => 'www.fitzonegym.com',
            '_business_hours' => 'Mon-Sun: 5:00 AM - 11:00 PM',
            '_business_rating' => '4.1',
            '_business_price_range' => '₹₹₹',
            '_business_features' => 'Modern equipment, Certified trainers, Group classes'
        ]
    ],
    [
        'title' => 'Suresh Sabzi Wala',
        'content' => 'Daily fresh vegetables and fruits delivered to your doorstep. Quality produce sourced directly from farms. Regular customers get special discounts.

<strong>Products:</strong>
• Fresh Vegetables (Daily)
• Seasonal Fruits
• Leafy Greens
• Organic Options Available

<strong>Services:</strong>
• Home Delivery
• WhatsApp Ordering
• Weekly/Monthly subscriptions',
        'excerpt' => 'Fresh vegetables and fruits with home delivery service',
        'category' => 'Shopping & Retail',
        'sub_category' => 'Sabzi Wala (Vegetable Vendor)',
        'meta' => [
            '_business_phone' => '+91-9876543216',
            '_business_address' => 'Local Market, Sector 21, Gurgaon',
            '_business_hours' => 'Mon-Sun: 6:00 AM - 8:00 PM',
            '_business_rating' => '4.0',
            '_business_price_range' => '₹',
            '_business_features' => 'Fresh daily, Home delivery, WhatsApp orders'
        ]
    ],
    [
        'title' => 'Reliable Driver Service - Amit',
        'content' => 'Reliable and experienced driver available for local and outstation trips. Clean vehicle, safe driving, and punctual service.

<strong>Services:</strong>
• Daily Office Drops
• Airport Transfers
• Outstation Trips
• Family Occasions
• Emergency Transportation

<strong>Vehicle:</strong> Swift Dzire (AC)
<strong>Experience:</strong> 8+ Years',
        'excerpt' => 'Professional driver service for local and outstation trips',
        'category' => 'Automotive',
        'sub_category' => '',
        'meta' => [
            '_business_phone' => '+91-9876543217',
            '_business_address' => 'Service Area: Delhi NCR',
            '_business_email' => 'amit.driver@example.com',
            '_business_hours' => 'Available 24/7',
            '_business_rating' => '4.4',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Clean vehicle, Safe driving, Punctual'
        ]
    ],
    [
        'title' => 'Dream Destinations Travel',
        'content' => 'Complete travel solutions for domestic and international trips. Hotel bookings, flight tickets, tour packages, and visa assistance.

<strong>Services:</strong>
• Domestic & International Tours
• Hotel & Flight Bookings
• Visa Assistance
• Travel Insurance
• Corporate Travel Solutions

<strong>Specialties:</strong> Himachal, Kerala, Dubai, Singapore
<strong>Office:</strong> City Center, Gurgaon',
        'excerpt' => 'Complete travel solutions with domestic and international tour packages',
        'category' => 'Travel & Tourism',
        'sub_category' => '',
        'meta' => [
            '_business_phone' => '+91-9876543218',
            '_business_address' => 'Office 205, City Center, Gurgaon',
            '_business_email' => 'info@dreamdestinations.com',
            '_business_website' => 'www.dreamdestinations.com',
            '_business_hours' => 'Mon-Sat: 10:00 AM - 7:00 PM',
            '_business_rating' => '4.2',
            '_business_price_range' => '₹₹₹',
            '_business_features' => 'IATA certified, Visa assistance, 20+ years experience'
        ]
    ],
    [
        'title' => 'Glamour Beauty Salon',
        'content' => 'Unisex salon with professional stylists and beauty experts. Complete range of hair, skin, and beauty treatments using premium products.

<strong>Services:</strong>
• Hair Cut & Styling
• Hair Color & Treatments
• Facial & Skin Care
• Manicure & Pedicure
• Bridal Makeup

<strong>Brands:</strong> L\'Oreal, Matrix, Lakme
<strong>Timings:</strong> 10:00 AM - 8:00 PM',
        'excerpt' => 'Professional unisex salon with complete beauty and hair treatments',
        'category' => 'Beauty & Wellness',
        'sub_category' => '',
        'meta' => [
            '_business_phone' => '+91-9876543219',
            '_business_address' => 'Shop 8, DLF Phase 1, Gurgaon',
            '_business_email' => 'glamourbeauty@example.com',
            '_business_hours' => 'Mon-Sun: 10:00 AM - 8:00 PM',
            '_business_rating' => '4.3',
            '_business_price_range' => '₹₹',
            '_business_features' => 'Professional stylists, Premium products, Bridal makeup'
        ]
    ]
];

$created_posts = [];

foreach ($businesses as $business) {
    // Create the post
    $post_data = [
        'post_title' => $business['title'],
        'post_content' => $business['content'],
        'post_excerpt' => $business['excerpt'],
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => 1,
        'comment_status' => 'open'
    ];
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        $created_posts[] = $post_id;
        echo "   ✅ Created: {$business['title']} (ID: $post_id)\n";
        
        // Add metadata
        foreach ($business['meta'] as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Assign to category
        if (isset($category_ids[$business['category']])) {
            wp_set_post_categories($post_id, [$category_ids[$business['category']]]);
        }
        
        // Assign to sub-category if specified
        if (!empty($business['sub_category'])) {
            $sub_cat = get_term_by('name', $business['sub_category'], 'category');
            if ($sub_cat) {
                wp_set_post_categories($post_id, [$category_ids[$business['category']], $sub_cat->term_id]);
            }
        }
        
    } else {
        echo "   ❌ Error creating {$business['title']}: " . (is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error') . "\n";
    }
}

// ==============================================
// 4. CREATE SAMPLE REVIEWS
// ==============================================

echo "\n⭐ Creating sample reviews...\n";

$reviews = [
    [
        'post_title' => 'Sharma Ji Ka Dhaba',
        'author' => 'Anita Sharma',
        'email' => 'anita@example.com',
        'content' => 'Excellent food quality and taste! The dal makhani is amazing and rotis are always fresh. Great value for money. Highly recommended for family dining.',
        'rating' => 5
    ],
    [
        'post_title' => 'Sharma Ji Ka Dhaba',
        'author' => 'Rohit Kumar',
        'email' => 'rohit@example.com',
        'content' => 'Been coming here for 2 years. Consistent quality and the owner is very friendly. The chicken curry is my favorite. Keep up the good work!',
        'rating' => 4
    ],
    [
        'post_title' => 'Fresh Bowl Kitchen',
        'author' => 'Priya Singh',
        'email' => 'priya@example.com',
        'content' => 'Love their healthy options! The Mediterranean salad bowl is fresh and delicious. Quick delivery and good packaging.',
        'rating' => 4
    ],
    [
        'post_title' => 'Rajesh Electrical Works',
        'author' => 'Sunil Gupta',
        'email' => 'sunil@example.com',
        'content' => 'Very professional service! Rajesh fixed our entire house wiring issue in one day. Fair pricing and quality work.',
        'rating' => 5
    ],
    [
        'post_title' => 'Priya Maths Tuition',
        'author' => 'Meera Agarwal',
        'email' => 'meera@example.com',
        'content' => 'My daughter\'s math grades improved significantly after joining Priya ma\'am. She explains concepts very clearly and is very patient.',
        'rating' => 5
    ]
];

foreach ($reviews as $review) {
    // Find the post ID by title
    $post = get_page_by_title($review['post_title'], OBJECT, 'post');
    if ($post) {
        $comment_data = [
            'comment_post_ID' => $post->ID,
            'comment_author' => $review['author'],
            'comment_author_email' => $review['email'],
            'comment_content' => $review['content'],
            'comment_approved' => 1,
            'comment_type' => 'review'
        ];
        
        $comment_id = wp_insert_comment($comment_data);
        if ($comment_id) {
            add_comment_meta($comment_id, '_review_rating', $review['rating']);
            echo "   ✅ Added review for {$review['post_title']} by {$review['author']} (Rating: {$review['rating']}/5)\n";
        }
    }
}

// ==============================================
// 5. CREATE ADDITIONAL USERS
// ==============================================

echo "\n👥 Creating additional users...\n";

$users = [
    [
        'username' => 'business_owner',
        'password' => 'business123',
        'email' => 'business@bizdir.local',
        'first_name' => 'Ravi',
        'last_name' => 'Sharma',
        'role' => 'subscriber'
    ],
    [
        'username' => 'customer_user',
        'password' => 'customer123',
        'email' => 'customer@bizdir.local',
        'first_name' => 'Priya',
        'last_name' => 'Gupta',
        'role' => 'subscriber'
    ]
];

foreach ($users as $user_data) {
    $user_id = wp_create_user($user_data['username'], $user_data['password'], $user_data['email']);
    if (!is_wp_error($user_id)) {
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'role' => $user_data['role']
        ]);
        echo "   ✅ Created user: {$user_data['username']} ({$user_data['first_name']} {$user_data['last_name']})\n";
    } else {
        echo "   ❌ Error creating user {$user_data['username']}: " . $user_id->get_error_message() . "\n";
    }
}

// ==============================================
// COMPLETION SUMMARY
// ==============================================

echo "\n🎉 Seed Data Creation Complete!\n\n";
echo "📊 Summary:\n";
echo "   • " . count($categories) . " main business categories created\n";
echo "   • " . count($sub_categories) . " sub-categories created\n";
echo "   • " . count($created_posts) . " business listings created\n";
echo "   • " . count($reviews) . " sample reviews added\n";
echo "   • " . count($users) . " additional users created\n\n";

echo "🌐 Your BizDir development environment is now populated with realistic Indian business data!\n";
echo "📱 Visit http://localhost:8888 to see your business directory in action.\n\n";

echo "✨ Categories include: restaurants, gym, tuition, cloud-kitchen, home-decor, \n";
echo "   electrician, carpenter, driver, sabzi-wala, travel-agency, and more!\n\n";

// Clear cache if any caching plugins are active
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

?>
