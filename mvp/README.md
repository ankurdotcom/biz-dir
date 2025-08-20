# Community Business Directory Platform

A modular, town-based business directory platform for Indian SMBs.

## Project Overview
This platform enables local businesses to create and manage their listings within a town-based structure, allowing for community-driven content and moderation.

## Features
- Town-based business listings
- User roles (Contributors, Moderators, Admins)
- Fractional 5-star review system
- NLP-based tag cloud generation
- Semantic search and filtering
- Sponsored listings and monetization
- SEO optimization with structured data

## Technical Stack
- WordPress Core
- Custom Theme (biz-dir)
- Custom Plugin (biz-dir-core)
- MySQL Database
- PHP 8.0+

## Development Setup
1. Install WordPress requirements:
   - PHP 8.0+
   - MySQL 5.7+
   - Apache/Nginx

2. Configure environment:
   ```bash
   # Copy environment file
   cp config/.env.example config/.env
   # Edit with your local settings
   ```

3. Install dependencies:
   ```bash
   composer install
   npm install
   ```

4. Database setup:
   ```bash
   # Import initial schema
   mysql -u [username] -p [database_name] < config/schema.sql
   ```

## Project Structure
```
mvp/
├── wp-content/
│   ├── themes/
│   │   └── biz-dir/          # Custom theme
│   └── plugins/
│       └── biz-dir-core/     # Core functionality plugin
└── config/                   # Configuration files
```

## Development Guidelines
- Follow WordPress coding standards
- Write unit tests for new features
- Document all hooks and filters
- Use semantic versioning

## Testing
```bash
# Run PHP unit tests
composer test

# Run JavaScript tests
npm test
```

## Contributing
Please read CONTRIBUTING.md for details on our code of conduct and the process for submitting pull requests.

## License
This project is licensed under the GPL v2 or later - see the LICENSE file for details.
