# Claude Orchestration File for Community Business Directory

## Objective
Coordinate the autonomous development of a modular, town-based business directory platform using AI tools. Each module is independently testable, reversible, and validated before progressing.

---

## Phase 1: Core Setup

### ✅ 01_Project_Spec.txt
- Define overall scope, goals, and architecture
- Output: High-level system blueprint

### ✅ 02_Database_Schema.txt
- Design normalized schema with support for towns, businesses, reviews, tags, and moderation
- Output: SQL or ORM models

---

## Phase 2: User System

### ✅ 03_User_Auth_and_Roles.txt
- Implement secure login, role-based access (contributor, moderator, admin)
- Output: Auth flow, role guards, profile management

---

## Phase 3: Business Listings

### ✅ 04_Business_Listing_Module.txt
- CRUD for listings, town mapping, sponsored flag
- Output: Listing form, town pages, SEO URLs

### ✅ 05_Review_and_Rating_System.txt
- Fractional star ratings, comments, schema.org markup
- Output: Review UI, average rating logic

### ✅ 06_Tag_Cloud_Engine.txt
- NLP-based tag extraction from reviews
- Output: Dynamic tag cloud per business

---

## Phase 4: Discovery & Moderation

### ✅ 07_Search_and_Filter.txt
- Semantic search, filters by town/category/rating/tags
- Output: Search bar, filter logic

### ✅ 08_Moderation_Workflow.txt
- Moderator dashboard, approval/rejection/escalation
- Output: Moderation queue, contributor notifications

---

## Phase 5: Monetization & SEO

### ✅ 09_Monetization_Module.txt
- Sponsored listings, ad slots, premium profiles
- Output: Payment logic, admin controls

### ✅ 10_Structured_Data_SEO.txt
- JSON-LD markup for rich results
- Output: Validated schema.org integration

---

## Phase 6: UX & Final Validation

### ✅ 11_UX_and_UI_Guidelines.txt
- Mobile-first design, accessibility, consistent UI
- Output: Responsive interface components

### ✅ 12_UAT_Checklist.txt
- Final acceptance testing
- Output: Checklist confirmation before launch

---

## Execution Notes

- Each prompt is standalone and can be executed in parallel
- Claude must document all decisions and assumptions
- Outputs must be modular, reversible, and independently testable
- Validation criteria must be met before moving to next phase

---

## Final Output

- Fully functional, modular business directory platform
- Ready for community onboarding, SEO indexing, and monetization
