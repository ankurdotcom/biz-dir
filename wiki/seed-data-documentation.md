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

## 📚 Business Categories Reference

### Main Categories (10)
1. **Restaurants & Food** - Dining, takeaway, catering
2. **Health & Fitness** - Gyms, clinics, wellness centers
3. **Education & Training** - Coaching, skills, academic
4. **Home & Garden** - Interior, furniture, landscaping
5. **Professional Services** - Repair, maintenance, consulting
6. **Shopping & Retail** - Stores, markets, vendors
7. **Automotive** - Car services, repair, maintenance
8. **Beauty & Personal Care** - Salons, spas, grooming
9. **Technology & Electronics** - IT services, repairs
10. **Entertainment & Recreation** - Fun, sports, leisure

### Sub-Categories (25+)
- Fine Dining, Fast Food, Cloud Kitchen, Street Food
- Gym & Fitness, Yoga Centers, Medical Clinic, Pharmacy
- Tuition Classes, Computer Training, Language Classes
- Interior Design, Furniture Store, Home Decor
- Electrician, Plumber, Carpenter, AC Repair
- Electronics Shop, Grocery Store, Local Vendors
- And many more specialized categories

---

## 📋 Sample Business Data Examples

### Development Data Businesses
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

### Demo Data Businesses
1. **Spicy Garden Restaurant** - Authentic Indian cuisine (4.8★)
2. **Glamour Beauty Studio** - Premium salon services (4.9★)
3. **TechFix Solutions** - Computer repair services (4.7★)
4. **FitZone Gym & Wellness** - Modern fitness center (4.6★)
5. **QuickFix Electrician** - 24/7 emergency services (4.5★)

---

## 🎯 Quick Start Guide

1. **Choose Your Data Set**:
   - Development work → `seed-data-dev.sql`
   - Production launch → `seed-data-production.sql`
   - Testing/QA → `seed-data-testing.sql`
   - Demos/presentations → `seed-data-demo.sql`

2. **Load Data**:
   ```bash
   ./load-seed-data.sh
   ```

3. **Verify Installation**:
   - Check business listings are visible
   - Test search and filtering
   - Verify user accounts work
   - Confirm reviews display correctly

4. **Customize for Production**:
   - Update contact information
   - Change default passwords
   - Remove demo accounts
   - Configure email settings

---

*Last Updated: August 23, 2025*  
*Version: 1.1.0*  
*Total Seed Data Files: 4*  
*Management Scripts: 1*
