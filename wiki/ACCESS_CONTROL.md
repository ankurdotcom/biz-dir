---
title: Wiki Access Control Guide
description: User access levels and permissions
published: true
date: $(date -Iseconds)
tags: [access-control, permissions, admin]
editor: markdown
---

# Wiki Access Control Guide

## 👥 User Roles and Access Levels

### 🌐 Public Users
**Access**: Read-only access to public documentation
- Project overview and setup guides
- General project information
- Getting started tutorials

### 👨‍💻 Developers
**Access**: Read/Write access to development documentation
- All public documentation
- Technical configuration guides
- Development environment setup
- API documentation

### 🧪 QA Team
**Access**: Read/Write access to testing documentation
- All public documentation
- Testing procedures and guidelines
- UAT documentation
- Test reports and results

### 🏗 Operations Team
**Access**: Read/Write access to infrastructure documentation
- All public documentation
- Infrastructure setup and management
- Deployment procedures
- System monitoring and maintenance

### 👑 Administrators
**Access**: Full access to all documentation
- All documentation categories
- User management
- System administration
- Executive reports and summaries

## 🔐 Setting Up Access Control

### Creating User Groups
1. Go to **Administration** → **Groups**
2. Create groups: `developers`, `qa`, `operations`, `admins`
3. Assign appropriate permissions to each group

### Page Permissions
Use page rules to restrict access:
- **Public pages**: No restrictions
- **Developer pages**: Require `developers` or `admins` group
- **QA pages**: Require `qa` or `admins` group  
- **Operations pages**: Require `operations` or `admins` group
- **Admin pages**: Require `admins` group only

### User Assignment
1. Create user accounts for team members
2. Assign users to appropriate groups
3. Test access to ensure proper restrictions

---
*Configure these access controls in your Wiki.js administration panel*
