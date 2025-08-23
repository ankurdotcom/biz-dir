# BizDir Wiki System
## Comprehensive Documentation & Executive Dashboard

A role-based documentation system with real-time project monitoring and executive dashboard.

## Features
- Role-based access control (Developer, QA, Operations, PM, PO, C-Level)
- Browser-based document editing (Confluence-like)
- Auto-sync with project changes
- Executive dashboard with KPIs
- Real-time notifications
- Dedicated wiki database

## Setup
1. Install dependencies: `composer install`
2. Configure database: `config/wiki_config.php`
3. Run migrations: `php setup.php`
4. Access: `http://localhost/wiki`

## Documentation Available
- **[Seed Data Documentation](seed-data-documentation.md)** - Comprehensive guide to all seed data files
- **Database Schema** - Complete database structure and relationships
- **API Documentation** - REST API endpoints and usage
- **Deployment Guides** - Docker and production deployment instructions

## Roles & Access Levels
- **Developers**: Technical docs, API, code architecture
- **QA**: Test plans, bug reports, quality metrics
- **Operations**: Deployment, monitoring, infrastructure
- **Project Managers**: Timelines, resources, progress
- **Product Owners**: Requirements, features, roadmap
- **C-Level**: Executive dashboard, KPIs, strategic overview
