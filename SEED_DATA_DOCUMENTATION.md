# BizDir Seed Data Documentation

## 📋 Overview

The BizDir platform includes comprehensive seed data sets designed for different use cases. Each seed data file contains carefully crafted sample data to support development, testing, production deployment, and demonstration scenarios.

## 🗂️ Available Seed Data Files

### 1. Development Data (`seed-data-dev.sql`)
**Purpose**: Comprehensive development environment with realistic sample data

**Contents**:
- ✅ 10 realistic Indian local businesses with complete information
- ✅ 7 main business categories (Restaurants, Health, Education, etc.)
- ✅ 25 detailed sub-categories
- ✅ 3 sample users (admin, business owner, customer)
- ✅ 7+ customer reviews with varied ratings (3-5 stars)
- ✅ Complete business metadata (phone, address, hours, ratings)
- ✅ Proper category relationships and taxonomy

**Sample Businesses Include**:
- Sharma Ji Ka Dhaba (Restaurant & Street Food)
- Fresh Bowl Kitchen (Cloud Kitchen)
- TechnoGym Fitness Center (Health & Fitness)
- Bright Minds Tuition Center (Education)
- Home Decor Paradise (Interior Design)
- Suresh Sabzi Wala (Local Vendor)
- Quick Car Service (Automotive)
- Glamour Beauty Salon (Personal Care)

**Best For**: Daily development work, feature testing, local environment setup

---

### 2. Production Data (`seed-data-production.sql`)
**Purpose**: Clean production deployment with essential structure

**Contents**:
- ✅ 10 main business categories with Indian focus
- ✅ 25 sub-categories for detailed classification
- ✅ Essential pages (Business Directory, About, Contact)
- ✅ 1 sample business owner user account (change credentials)
- ✅ Clean category structure ready for real businesses
- ❌ No sample businesses (clean slate)
- ❌ No sample reviews (fresh start)

**Categories Include**:
- Restaurants & Food (Fine Dining, Fast Food, Cloud Kitchen, etc.)
- Health & Fitness (Gym, Yoga, Medical Clinic, etc.)
- Education & Training (Tuition, Computer Training, etc.)
- Professional Services (Electrician, Plumber, Carpenter, etc.)
- Shopping & Retail, Automotive, Beauty & Personal Care, etc.

**Best For**: Production deployment, live websites, fresh installations

---

### 3. Testing Data (`seed-data-testing.sql`)
**Purpose**: Comprehensive test scenarios and edge cases

**Contents**:
- ✅ 5 test users with different roles and scenarios
- ✅ 7 test business listings covering various scenarios:
  - Complete data business (full features testing)
  - Minimal data business (basic info only)
  - Special characters/edge cases business
  - Premium business (monetization features)
  - 3x bulk operation businesses
- ✅ 5 test reviews with different ratings
- ✅ Edge case scenarios (special characters, long text, etc.)
- ✅ Premium feature testing data
- ✅ Bulk operation test data

**Test Scenarios Covered**:
- 🧪 Complete business information display
- 🧪 Minimal data handling
- 🧪 Special characters and edge cases (àáâãäåæçèéêë)
- 🧪 Premium features and monetization
- 🧪 Bulk operations and management
- 🧪 Review and rating system validation
- 🧪 Search and filter functionality
- 🧪 User role management

**Best For**: UAT testing, regression testing, QA validation, automated testing

---

### 4. Demo Data (`seed-data-demo.sql`)
**Purpose**: Attractive sample businesses for demonstrations

**Contents**:
- ✅ 5 realistic business owners with authentic profiles
- ✅ 5 attractive demo businesses with excellent reviews:
  - **Spicy Garden Restaurant** (Authentic Indian cuisine, 4.8★)
  - **Glamour Beauty Studio** (Premium salon services, 4.9★)
  - **TechFix Solutions** (Computer repair services, 4.7★)
  - **FitZone Gym & Wellness** (Modern fitness center, 4.6★)
  - **QuickFix Electrician** (24/7 emergency services, 4.5★)
- ✅ 8 positive customer reviews (4-5 star ratings)
- ✅ Professional business descriptions
- ✅ Complete contact information and service details
- ✅ Realistic pricing ranges and features

**Business Highlights**:
- Professional descriptions with unique selling points
- Complete service offerings and specialties
- Awards, certifications, and testimonials
- High-quality customer reviews
- Comprehensive contact and location information

**Best For**: Client demonstrations, sales presentations, showcasing platform capabilities

---

## 🚀 Seed Data Management

### Using the Load Script
The easiest way to manage seed data is using the provided script:

```bash
./load-seed-data.sh
```

**Script Features**:
- ✅ Interactive menu for data selection
- ✅ Automatic Docker container status checking
- ✅ Optional database backup before loading
- ✅ WordPress cache clearing (if Redis available)
- ✅ Database statistics after loading
- ✅ Network access information

### Manual Loading
You can also load seed data manually:

```bash
# Load development data
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-dev.sql

# Load production data
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-production.sql

# Load testing data
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-testing.sql

# Load demo data
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-demo.sql
```

### Creating Database Backups
Before loading new seed data, create a backup:

```bash
# Create timestamped backup
timestamp=$(date +"%Y%m%d-%H%M%S")
docker compose -f docker-compose.dev.yml exec -T db mysqldump -u bizdir -pbizdir123 bizdir_dev > "backup-${timestamp}.sql"
```

---

## 📊 Data Statistics

| File Type | Businesses | Users | Reviews | Categories | Size |
|-----------|------------|-------|---------|------------|------|
| **Development** | 10 | 3 | 7+ | 25 | ~565 lines |
| **Production** | 0 | 1 | 0 | 25 | ~200 lines |
| **Testing** | 7 | 5 | 5 | 25 | ~400 lines |
| **Demo** | 5 | 5 | 8 | 25 | ~500 lines |

---

## 🎯 Use Case Recommendations

### 🔧 Development Phase
**Use**: `seed-data-dev.sql`
- Rich sample data for feature development
- Variety of business types and scenarios
- Realistic user interactions and reviews
- Complete metadata for testing all features

### 🚀 Production Deployment
**Use**: `seed-data-production.sql`
- Clean slate for real business data
- Essential category structure pre-configured
- Basic pages ready for customization
- No dummy data to clean up later

### 🧪 Testing & QA
**Use**: `seed-data-testing.sql`
- Comprehensive test scenarios
- Edge cases and special characters
- Premium feature testing
- Bulk operation validation
- User role testing

### 🎯 Client Demonstrations
**Use**: `seed-data-demo.sql`
- Professional, attractive business listings
- Excellent reviews and ratings
- Complete service descriptions
- Realistic contact information
- Showcase-ready presentation data

---

## 🔒 Security Considerations

### Production Notes
- **Change default passwords** in production environment
- **Update contact information** in Contact page
- **Remove demo user accounts** before going live
- **Verify email configurations** are production-ready
- **Configure payment gateways** if using premium features

### Development Notes
- Demo passwords are intentionally simple (`demo123`, `test123`)
- All email addresses use `.demo` or `.test` domains
- Phone numbers are formatted for Indian context
- Addresses are sample locations in Gurgaon/NCR area

---

## 📁 File Locations

```
/biz-dir/
├── seed-data-dev.sql         # Development data
├── seed-data-production.sql  # Production data  
├── seed-data-testing.sql     # Testing data
├── seed-data-demo.sql        # Demo data
└── load-seed-data.sh         # Management script
```

---

## 🛠️ Technical Details

### Database Tables Affected
- `wp_posts` - Business listings and pages
- `wp_postmeta` - Business metadata (phone, address, etc.)
- `wp_terms` - Categories and tags
- `wp_term_taxonomy` - Category relationships
- `wp_term_relationships` - Business-category assignments
- `wp_users` - User accounts
- `wp_usermeta` - User metadata
- `wp_comments` - Reviews and ratings
- `wp_commentmeta` - Review metadata (ratings)

### ID Ranges Used
- **Development**: Posts 1001-1010, Users 2-10, Comments 2001-3000
- **Production**: Posts 10-12, Users 10, Comments none
- **Testing**: Posts 2001-2007, Users 20-24, Comments 3001-3005
- **Demo**: Posts 3001-3005, Users 30-34, Comments 4001-4008

### Character Encoding
All files use UTF-8 encoding with `SET NAMES utf8mb4` to support:
- Indian language characters
- Special symbols and emojis
- International business names
- Unicode content throughout

---

## 🔧 Maintenance

### Adding New Seed Data
1. Follow the existing file structure and naming conventions
2. Use appropriate ID ranges to avoid conflicts
3. Include comprehensive metadata for businesses
4. Test the seed data thoroughly before committing
5. Update this documentation with new additions

### Updating Existing Data
1. Maintain backward compatibility
2. Test with existing applications
3. Update version information in file headers
4. Document changes in commit messages

---

## 📚 Legacy Content (Original Development Data Details)

### Business Categories (25+ categories)
- **Food & Dining**: Restaurants, Cloud Kitchen, Fine Dining, Fast Food, Street Food
- **Fitness & Health**: Gyms & Fitness, Beauty Parlours, Medical Stores, Yoga Centers, Dental Care
- **Education**: Tuition Teachers, English Speaking Institute, Computer Training, Music & Dance
- **Home Services**: Electricians, Carpenters, Plumbers, Home Decor, Furniture, AC Repair
- **Professional Services**: Security Services, Cleaning Services, Interior Design
- **Shopping & Retail**: Electronics, Sabzi Wala, Kabadi Wala
- **Transportation**: Travel Agency, Driver Services, Car Repair
- **Technology**: Computer Repair, Mobile Services, IT Support

### Sample Business Listings (Development Data)
1. **Sharma Ji Ka Dhaba** - Traditional restaurant with authentic Indian food
2. **Fresh Bowl Kitchen** - Modern cloud kitchen with healthy options
3. **TechnoGym Fitness Center** - Well-equipped gym with professional trainers
4. **Bright Minds Tuition Center** - Academic coaching for students
5. **Home Decor Paradise** - Interior design and home furnishing
6. **Raj Furniture House** - Quality furniture for homes and offices
7. **Quick Car Service** - Automotive repair and maintenance
8. **Suresh Sabzi Wala** - Fresh vegetables and grocery vendor
9. **SecureGuard Services** - Professional security services
10. **Glamour Beauty Salon** - Premium beauty and personal care services

### User Accounts (Development Data)
- **Admin User**: Main administrator account
- **Business Owner**: Sample business owner with listing permissions
- **Customer User**: Regular customer account for reviews and interactions

### Reviews & Ratings
- Authentic customer reviews with ratings from 3.5 to 5.0 stars
- Varied review content reflecting real customer experiences
- Balanced mix of positive feedback and constructive criticism
- Reviews include specific details about services and experiences

---

*Last Updated: August 23, 2025*  
*Version: 1.1.0*  
*Total Seed Data Files: 4*  
*Management Scripts: 1*
- **Transportation**: Drivers, Auto Repair
- **Daily Services**: Sabji Wala, Kabadi Wala, Grocery Stores, Tailors
- **Business Services**: Travel Agency, Security Services, Mobile Repair

### Sample Business Listings (10 businesses)
- Complete business information (name, description, contact details)
- Realistic addresses in Delhi NCR region
- Business hours, ratings, and price ranges
- Featured business flags for testing premium features

### User Accounts (4 accounts)
- **Admin**: `demouser` - Full administrative access
- **Business Owners**: `businessowner1`, `businessowner2` - Can manage their listings
- **Customer**: `customer1` - Can browse and review businesses

### Reviews & Ratings (5 reviews)
- Authentic customer reviews in English
- Star ratings from 4-5 stars
- Associated with different businesses for testing

## 🚀 Quick Setup

### Method 1: Automated Setup (Recommended)
```bash
# Make sure Docker Compose is running
docker compose -f docker-compose.dev.yml up -d

# Run the automated setup
./setup-seed-data.sh
```

### Method 2: Manual SQL Import
```bash
# Import directly to database
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev < seed-data-dev.sql
```

### Method 3: Database Client
1. Open phpMyAdmin or MySQL Workbench
2. Connect to `localhost:3306` with credentials `bizdir/bizdir123`
3. Select `bizdir_dev` database
4. Import `seed-data-dev.sql` file

## 🔧 Configuration

### Database Connection
- **Host**: localhost:3306
- **Database**: bizdir_dev
- **Username**: bizdir
- **Password**: bizdir123

### WordPress Site
- **URL**: http://localhost:8888
- **Admin URL**: http://localhost:8888/wp-admin/

## 👥 Demo Accounts

| Role | Username | Password | Purpose |
|------|----------|----------|---------|
| Admin | `demouser` | `demouser@123456:)` | Full site management |
| Business Owner | `businessowner1` | `demo123` | Business listing management |
| Business Owner | `businessowner2` | `demo123` | Business listing management |
| Customer | `customer1` | `demo123` | Browse and review businesses |

## 🧪 Testing Scenarios

### Basic Functionality
- [ ] Browse business directory homepage
- [ ] View business categories
- [ ] Search businesses by name/keyword
- [ ] Filter businesses by category
- [ ] View individual business pages
- [ ] Read customer reviews and ratings

### User Management
- [ ] Login with different user roles
- [ ] Test role-based access control
- [ ] Business owner dashboard access
- [ ] Customer review submission

### Business Management
- [ ] Add new business listing
- [ ] Edit existing business information
- [ ] Upload business images
- [ ] Manage business hours and contact info
- [ ] Handle business approval workflow

### Advanced Features
- [ ] Search and filter combinations
- [ ] Mobile responsive design
- [ ] SEO-friendly URLs
- [ ] Social media integration
- [ ] Map integration (if available)

## 🔄 Data Reset

### Clear All Seed Data
```sql
-- Remove demo businesses
DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'business' AND ID >= 1001);
DELETE FROM wp_posts WHERE post_type = 'business' AND ID >= 1001;

-- Remove demo categories
DELETE FROM wp_term_relationships WHERE term_taxonomy_id >= 601;
DELETE FROM wp_term_taxonomy WHERE term_id >= 601;
DELETE FROM wp_terms WHERE term_id >= 601;

-- Remove demo reviews
DELETE FROM wp_commentmeta WHERE comment_id >= 501;
DELETE FROM wp_comments WHERE comment_ID >= 501;

-- Remove demo users (optional)
DELETE FROM wp_usermeta WHERE user_id >= 101;
DELETE FROM wp_users WHERE ID >= 101;
```

### Re-import Fresh Data
```bash
# After clearing, re-run the setup
./setup-seed-data.sh
```

## 📊 Sample Data Details

### Business Listings Created
1. **Sharma Family Restaurant** - North Indian cuisine
2. **Dosa Corner** - South Indian breakfast
3. **Gujarati Thali House** - Vegetarian thali
4. **FitZone Gym** - Modern fitness center
5. **Ladies Fitness Studio** - Women-only fitness
6. **Gupta Maths Classes** - Mathematics tuition
7. **English Speaking Institute** - Language classes
8. **Kumar Electrical Works** - Electrical services
9. **Reliable Cab Service** - Transportation
10. **Fresh Sabji Wala** - Vegetable vendor

### Metadata Included
- Phone numbers (realistic Indian format)
- Email addresses
- Complete addresses (Delhi NCR)
- Business hours
- Ratings (4.0-5.0 scale)
- Price ranges (₹, ₹₹, ₹₹₹)
- Featured business flags

## 🐛 Troubleshooting

### Common Issues

**"Duplicate entry" errors**
- Clear existing data first using reset queries
- Check for conflicting IDs in your database

**"Access denied" database errors**
- Verify Docker containers are running
- Check database credentials in wp-config.php

**Missing categories after import**
- Verify wp_term_taxonomy entries
- Check category assignments in wp_term_relationships

**WordPress admin not accessible**
- Clear browser cache
- Check .htaccess file for rewrite rules
- Verify file permissions

### Verification Commands
```bash
# Check imported categories
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT name FROM wp_terms WHERE term_id >= 601;"

# Check imported businesses
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT post_title FROM wp_posts WHERE post_type='business';"

# Check reviews
docker compose -f docker-compose.dev.yml exec -T db mysql -u bizdir -pbizdir123 bizdir_dev -e "SELECT comment_author, comment_content FROM wp_comments WHERE comment_type='review';"
```

## 🔄 Updating Seed Data

### Adding New Categories
1. Edit `seed-data-dev.sql`
2. Add new entries to `wp_terms` and `wp_term_taxonomy`
3. Use term_id >= 700 for new categories
4. Update category count in taxonomy table

### Adding New Businesses
1. Add new entries to `wp_posts` with post_type='business'
2. Use ID >= 2000 for new businesses
3. Add corresponding metadata in `wp_postmeta`
4. Create category relationships in `wp_term_relationships`

### Modifying Existing Data
- Edit the SQL file directly
- Maintain ID ranges to avoid conflicts
- Update relationship tables accordingly

## 📈 Production Considerations

**⚠️ IMPORTANT**: This seed data is for development/demo only!

### Before Production:
- [ ] Remove all demo accounts
- [ ] Clear sample business listings
- [ ] Reset admin passwords
- [ ] Configure proper email settings
- [ ] Set up production database
- [ ] Enable security features
- [ ] Configure backup systems

### Security Notes:
- Demo passwords are intentionally weak
- User accounts have predictable credentials
- Business information is fictional
- Email addresses use example.com domain

## 📞 Support

For issues with seed data setup:
1. Check the troubleshooting section above
2. Review Docker container logs
3. Verify database connectivity
4. Consult the main project documentation

---

**Happy Development! 🚀**

*Last Updated: August 23, 2025*
