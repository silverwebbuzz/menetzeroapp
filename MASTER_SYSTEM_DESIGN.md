# MenetZero - Master System Design Document
## Complete Enhancement Specification & Implementation Guide

**Version**: 2.0  
**Status**: Finalized & Consolidated  
**Date**: 2025-01-XX

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture Decisions](#2-architecture-decisions)
3. [URL Structure](#3-url-structure)
4. [Subscription Plans](#4-subscription-plans)
5. [Staff & Access Management](#5-staff--access-management)
6. [Database Design](#6-database-design)
7. [Data Structure & Separation](#7-data-structure--separation)
8. [Performance & Scalability](#8-performance--scalability)
9. [Implementation Roadmap](#9-implementation-roadmap)
10. [Key Services & Models](#10-key-services--models)

---

## 1. System Overview

### Two Separate Entities

#### 1. Clients (MSME - Direct)
- Direct customers of MenetZero
- Manage their own carbon emissions
- Independent accounts and subscriptions
- No relationship with Partners in the system
- **Data Structure**: `companies` → `locations` → `measurements` → `measurement_data`

#### 2. Partners (CA/Consultants)
- Separate customer segment
- **Manage external clients** through Partner panel (add, edit, delete)
- External clients are **NOT MenetZero accounts** - they have no login access
- Partners use MenetZero for their own operations/analytics
- No relationship with MenetZero Clients in the system
- **Data Structure**: `partner_external_clients` → `partner_external_client_locations` → `partner_external_client_measurements`

### Key Principles

1. **Complete Separation**: No cross-entity relationships between MenetZero Clients and Partners
2. **External Client Management**: Partners manage external clients (outside MenetZero) through their panel
3. **No External Client Access**: External clients managed by partners have **NO access** to MenetZero system
4. **Independent Revenue**: Each entity has separate features, plans, and permissions
5. **Single Users Table**: All staff (client, partner, external auditors) in ONE table
6. **Multi-Account Access**: Staff can access multiple companies with different roles per company

---

## 2. Architecture Decisions

### Panel Architecture: Hybrid Approach ✅

**Shared Components:**
- Measurements (view, create, edit)
- Locations (view, create, edit)
- Documents (upload, view, approve)
- Reports (generate, view)

**Entity-Specific Dashboards:**
- **Client Dashboard**: Personal metrics, quick actions
- **Partner Dashboard**: Analytics, external client management (add/edit/delete), usage tracking

**Benefits:**
- Code reusability for shared features
- Full customization for entity-specific features
- Easy to maintain and extend

### Data Architecture: Separate Tables ✅

**Direct Clients:**
- Use existing tables: `companies`, `locations`, `measurements`, `measurement_data`, `document_uploads`, `reports`

**Partner External Clients:**
- Use separate tables: `partner_external_clients`, `partner_external_client_locations`, `partner_external_client_measurements`, etc.

**Benefits:**
- Clean separation
- Fast queries (no complex joins)
- Scalable (handles 10K+ external clients easily)
- Easy to maintain

### Staff Architecture: Single Table + Access Linking ✅

**Single Users Table:**
- All staff in ONE table (client staff, partner staff, external auditors)
- No separate `client_staff` or `partner_staff` tables

**Access Management:**
- `user_company_access` table links users to companies
- Same user can have different roles in different companies
- Workspace switcher for multi-company access

---

## 3. URL Structure

### Base URL
```
https://app.menetzero.com
```

### Client URLs
```
https://app.menetzero.com/dashboard
https://app.menetzero.com/measurements
https://app.menetzero.com/measurements/{id}
https://app.menetzero.com/locations
https://app.menetzero.com/locations/{id}
https://app.menetzero.com/document-uploads
https://app.menetzero.com/reports
https://app.menetzero.com/profile
https://app.menetzero.com/settings
https://app.menetzero.com/staff
https://app.menetzero.com/roles
```

### Partner URLs
```
https://app.menetzero.com/partner/dashboard
https://app.menetzero.com/partner/clients                    → External Client Management
https://app.menetzero.com/partner/clients/create             → Add New External Client
https://app.menetzero.com/partner/clients/{id}               → View/Edit External Client
https://app.menetzero.com/partner/clients/{id}/locations      → Client's Locations
https://app.menetzero.com/partner/clients/{id}/measurements  → Client's Measurements
https://app.menetzero.com/partner/clients/{id}/documents     → Client's Documents
https://app.menetzero.com/partner/clients/{id}/reports       → Client's Reports
https://app.menetzero.com/partner/analytics
https://app.menetzero.com/partner/usage
https://app.menetzero.com/partner/branding
https://app.menetzero.com/partner/settings
https://app.menetzero.com/partner/staff
https://app.menetzero.com/partner/roles
```

### Account Switcher (Multi-Account Access)
```
https://app.menetzero.com/account/selector                   → Account Selection Page
https://app.menetzero.com/account/switch?company_id={id}     → Switch Active Account
```

### Route Implementation
```php
// routes/web.php

// Account Switcher (for multi-company staff)
Route::middleware(['auth'])->group(function () {
    Route::get('/account/selector', [AccountSelectorController::class, 'index'])->name('account.selector');
    Route::post('/account/switch', [AccountSelectorController::class, 'switch'])->name('account.switch');
});

// Client Routes
Route::middleware(['auth', 'client', 'setActiveCompany'])->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::resource('measurements', MeasurementController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('document-uploads', DocumentUploadController::class);
    Route::get('/profile', [ProfileController::class, 'index'])->name('client.profile');
    Route::get('/settings', [ClientSettingsController::class, 'index'])->name('client.settings');
    Route::resource('staff', ClientStaffController::class);
    Route::resource('roles', ClientRoleController::class);
});

// Partner Routes
Route::prefix('partner')->middleware(['auth', 'partner', 'setActiveCompany'])->group(function () {
    Route::get('/dashboard', [PartnerDashboardController::class, 'index'])->name('partner.dashboard');
    
    // External Client Management
    Route::resource('clients', Partner\ExternalClientController::class);
    Route::get('/clients/{client}/locations', [Partner\ExternalClientLocationController::class, 'index'])->name('partner.clients.locations.index');
    Route::post('/clients/{client}/locations', [Partner\ExternalClientLocationController::class, 'store'])->name('partner.clients.locations.store');
    Route::get('/clients/{client}/locations/{location}/measurements', [Partner\ExternalClientMeasurementController::class, 'index'])->name('partner.clients.measurements.index');
    Route::post('/clients/{client}/locations/{location}/measurements', [Partner\ExternalClientMeasurementController::class, 'store'])->name('partner.clients.measurements.store');
    Route::get('/clients/{client}/documents', [Partner\ExternalClientDocumentController::class, 'index'])->name('partner.clients.documents.index');
    Route::get('/clients/{client}/reports', [Partner\ExternalClientReportController::class, 'index'])->name('partner.clients.reports.index');
    
    Route::get('/analytics', [PartnerAnalyticsController::class, 'index'])->name('partner.analytics');
    Route::get('/usage', [PartnerUsageController::class, 'index'])->name('partner.usage');
    Route::get('/branding', [PartnerBrandingController::class, 'index'])->name('partner.branding');
    Route::get('/settings', [PartnerSettingsController::class, 'index'])->name('partner.settings');
    Route::resource('staff', PartnerStaffController::class);
    Route::resource('roles', PartnerRoleController::class);
});
```

---

## 4. Subscription Plans

### Client Plans (MSME)

#### Free Plan
- **Price**: AED 0/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - 1 Location
  - 1 User
  - Basic measurements
  - Limited reports

#### Starter Plan
- **Price**: AED 1,199/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - 1 Location
  - 2 Staff Users
  - Basic Reporting
  - Manual Data Entry

#### Growth Plan
- **Price**: AED 2,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - Multi-Location (up to 5)
  - 3 Staff Users
  - Advanced Analytics
  - Basic Reporting

#### Pro Plan
- **Price**: AED 5,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - Unlimited Locations
  - 5 Staff Users
  - OCR/AI Upload
  - Advanced Analytics
  - Data Archive/Export
  - Unlimited Reports

### Partner Plans (CA/Consultants)

#### Free Plan
- **Price**: AED 0/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **2 External Clients** (add/edit/delete)
  - Basic analytics
  - Limited usage

#### Partner Plan
- **Price**: AED 9,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **10 External Clients** (add/edit/delete)
  - Client Management (External)
  - Analytics Dashboard
  - Co-Branded Reports
  - 5 Staff Users
  - Usage Tracking

#### Enterprise Plan
- **Price**: AED 29,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **Unlimited External Clients** (add/edit/delete)
  - Unlimited Staff Users
  - White-Label Solution
  - Full API Access
  - Custom Integrations
  - Priority Support
  - Advanced Analytics

### Feature Configuration by Superadmin

**Key Principle**: Superadmin can configure which features belong to which plan through admin panel.

**Implementation:**
- Features stored as JSON in `subscription_plans` table
- Admin UI to enable/disable features per plan
- Dynamic feature checking in application

---

## 5. Staff & Access Management

### Staff Model: Single Users Table ✅

**Key Principle:** ONE users table for ALL staff - client staff, partner staff, external auditors.

**No separate tables needed!** The `user_company_access` table handles the linking.

### Staff Types

#### 1. Single Company Staff
- Staff belongs to ONE company (client or partner)
- `users.company_id` is set
- Role from `users.role` or `users.custom_role_id`
- Standard access, no workspace switcher

#### 2. Multi-Company Staff
- Staff can access MULTIPLE companies
- `users.company_id` is NULL (or set to one, but has additional access)
- Roles from `user_company_access` table (different role per company)
- Workspace switcher shown after login

### Role Assignment

**Single Company Staff:**
- Role assigned by company owner
- Stored in `users.role` or `users.custom_role_id`
- One role for the company

**Multi-Company Staff:**
- Different roles in different companies
- Each company owner assigns role independently
- Stored in `user_company_access.role_id` or `user_company_access.custom_role_id`
- Same user can have:
  - Client 1: Auditor (view only)
  - Client 2: Manager (can edit) - **Different role!**
  - Partner 1: Partner Staff (view)
  - Partner 2: Partner Manager (full access) - **Different role!**

### Default Role Templates

#### Client Default Roles
1. **Owner** - Full access
2. **Manager** - Operational management
3. **Data Entry** - Data input only
4. **Auditor** - View only

#### Partner Default Roles
1. **Partner Admin** - Full partner access
2. **Partner Manager** - Operational management
3. **Partner Staff** - Limited access

### Custom Role Creation

**For Clients:**
- Client admin can create custom roles
- Assign custom permissions
- Use custom terminology (Supervisor, Associate, etc.)
- Roles are company-specific

**For Partners:**
- Partner admin can create custom roles
- Assign custom permissions
- Use custom terminology
- Roles are partner-specific

### Permission System

**Using Spatie Laravel Permission:**
- Roles and Permissions system
- Flexible permission assignment
- Easy to add new permissions for new features
- Caching built-in

**Permission Examples:**
- `measurements.view`
- `measurements.create`
- `measurements.edit`
- `measurements.delete`
- `documents.upload`
- `documents.approve`
- `reports.generate`
- `locations.manage`
- `users.manage`
- `settings.manage`

**Adding New Permissions (Future Features):**
```php
// Example: Adding AI Recommendations feature
Permission::create(['name' => 'ai_recommendations.view']);
Permission::create(['name' => 'ai_recommendations.use']);

// Assign to roles
$owner->givePermissionTo('ai_recommendations.*');
$manager->givePermissionTo(['ai_recommendations.view', 'ai_recommendations.use']);
```

### Workspace Switcher (Multi-Account Access)

**When Shown:**
- User has multiple company access (count > 1 in `user_company_access`)
- Shown after login
- Dropdown in header for quick switching

**How It Works:**
1. User logs in
2. System checks: `user_company_access` count
3. If count > 1: Show account selector
4. User selects account
5. System sets `user_active_context`
6. All queries filtered by active company

---

## 6. Database Design

### Core Tables

#### 1. Companies (Enhanced)
```sql
ALTER TABLE `companies`
ADD COLUMN `company_type` enum('client', 'partner') NOT NULL DEFAULT 'client' AFTER `is_active`,
ADD COLUMN `is_direct_client` tinyint(1) DEFAULT 1 AFTER `company_type`,
ADD INDEX `company_type` (`company_type`);
```

#### 2. Users (Enhanced - Single Table for All Staff)
```sql
ALTER TABLE `users`
ADD COLUMN `user_type` enum('client', 'partner') DEFAULT 'client' AFTER `role`, -- For single-company staff
ADD COLUMN `custom_role_id` bigint(20) UNSIGNED NULL AFTER `user_type`,
ADD COLUMN `external_company_name` varchar(255) NULL AFTER `custom_role_id`, -- Optional: e.g., "Bhavik Certification Company"
ADD COLUMN `notes` text NULL AFTER `external_company_name`,
ADD INDEX `user_type` (`user_type`),
ADD INDEX `custom_role_id` (`custom_role_id`);

-- Key Points:
-- - company_id set = single company staff
-- - company_id NULL + user_company_access records = multi-company staff
-- - No internal/external enum - check access count instead
```

#### 3. Subscription Plans
```sql
CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` varchar(50) NOT NULL UNIQUE,
  `plan_name` varchar(255) NOT NULL,
  `plan_category` enum('client', 'partner') NOT NULL,
  `price_annual` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'AED',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `description` text NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`),
  KEY `plan_category` (`plan_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Client Subscriptions
```sql
CREATE TABLE `client_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `company_id` (`company_id`),
  KEY `subscription_plan_id` (`subscription_plan_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. Partner Subscriptions
```sql
CREATE TABLE `partner_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `company_id` (`company_id`),
  KEY `subscription_plan_id` (`subscription_plan_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. User Company Access (Multi-Account Access)
```sql
CREATE TABLE `user_company_access` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL, -- Spatie role ID
  `custom_role_id` bigint(20) UNSIGNED NULL, -- Reference to company_custom_roles
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `status` enum('active', 'suspended', 'revoked') DEFAULT 'active',
  `invited_by` bigint(20) UNSIGNED NULL,
  `invited_at` datetime NOT NULL,
  `last_accessed_at` datetime NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_company_unique` (`user_id`, `company_id`),
  KEY `user_id` (`user_id`),
  KEY `company_id` (`company_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. User Active Context (Current Account)
```sql
CREATE TABLE `user_active_context` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `active_company_id` bigint(20) UNSIGNED NULL,
  `active_company_type` enum('client', 'partner') NULL,
  `last_switched_at` datetime NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`active_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. Company Invitations
```sql
CREATE TABLE `company_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL,
  `custom_role_id` bigint(20) UNSIGNED NULL,
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `token` varchar(255) NOT NULL UNIQUE,
  `status` enum('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `invited_at` datetime NOT NULL,
  `expires_at` datetime NULL,
  `accepted_at` datetime NULL,
  `accepted_by_user_id` bigint(20) UNSIGNED NULL,
  `notes` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_email_pending` (`company_id`, `email`, `status`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `status` (`status`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 9. Role Templates (Default Roles)
```sql
CREATE TABLE `role_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) NOT NULL UNIQUE,
  `template_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON array
  `category` enum('client', 'partner', 'both') DEFAULT 'client',
  `is_system_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_code` (`template_code`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 10. Company Custom Roles
```sql
CREATE TABLE `company_custom_roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON array
  `based_on_template` varchar(50) NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 11. Feature Flags
```sql
CREATE TABLE `feature_flags` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `feature_code` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `enabled_at` datetime NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_feature` (`company_id`, `feature_code`),
  KEY `company_id` (`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 12. Usage Tracking
```sql
CREATE TABLE `usage_tracking` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `resource_type` enum('location', 'user', 'document', 'report', 'api_call', 'measurement') NOT NULL,
  `resource_id` bigint(20) UNSIGNED NULL,
  `action` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `period` enum('daily', 'monthly', 'yearly') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `resource_type` (`resource_type`),
  KEY `period_start` (`period_start`),
  KEY `company_period` (`company_id`, `period`, `period_start`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 7. Data Structure & Separation

### Direct Clients Data Structure

```
companies (1 company = 1 MenetZero client)
  ├── locations (1 company has many locations)
  │     └── measurements (1 location has many measurements)
  │           └── measurement_data (1 measurement has many data entries)
  ├── document_uploads (1 company has many documents)
  └── reports (1 company has many reports)
```

**Tables Used:**
- `companies` (existing)
- `locations` (existing)
- `measurements` (existing)
- `measurement_data` (existing)
- `document_uploads` (existing)
- `reports` (existing)

### Partner External Clients Data Structure

```
partner_company (1 partner)
  └── partner_external_clients (1 partner has MANY external clients)
        ├── Client 1
        │     ├── partner_external_client_locations (Client 1 has many locations)
        │     │     └── partner_external_client_measurements (Each location has many measurements)
        │     │           └── partner_external_client_measurement_data (Each measurement has many data entries)
        │     ├── partner_external_client_documents (Client 1 has many documents)
        │     ├── partner_external_client_reports (Client 1 has many reports)
        │     └── partner_external_client_emission_boundaries (Client 1 has emission boundaries)
        │
        ├── Client 2
        │     └── (Complete separate data structure)
        │
        └── Client N (Each client has completely separate data)
```

**Tables Used:**
- `partner_external_clients` (NEW)
- `partner_external_client_locations` (NEW)
- `partner_external_client_measurements` (NEW)
- `partner_external_client_measurement_data` (NEW)
- `partner_external_client_documents` (NEW)
- `partner_external_client_reports` (NEW)
- `partner_external_client_emission_boundaries` (NEW)

### Key Relationship Hierarchy

**Direct Clients:**
```
Company → Locations → Measurements → Measurement Data
```

**Partner External Clients:**
```
Partner → External Clients → Locations → Measurements → Measurement Data
```

### Complete Data Flow Example

**Partner ABC with 10 External Clients:**
```
Partner ABC (partner_company_id = 5)
│
├── External Client 1 (partner_external_client_id = 1)
│   ├── Location 1: Head Office (location_id = 1)
│   │   ├── Measurement 1: Jan 2025 (measurement_id = 1)
│   │   │   ├── Data Entry 1: Emission Source 1, quantity = 1000
│   │   │   ├── Data Entry 2: Emission Source 2, quantity = 500
│   │   │   └── Data Entry 3: Emission Source 3, quantity = 200
│   │   ├── Measurement 2: Feb 2025
│   │   └── Measurement 3: Mar 2025
│   ├── Location 2: Warehouse
│   │   └── Measurements...
│   ├── Document 1: DEWA_Bill_Jan.pdf
│   ├── Document 2: Fuel_Receipt_Feb.pdf
│   └── Report 1: Annual_Report_2025.pdf
│
├── External Client 2 (partner_external_client_id = 2)
│   └── (Complete separate data structure)
│
└── ... External Client 10
      └── (Complete separate data structure)
```

### Database Tables for Partner External Clients

#### 1. Partner External Clients
```sql
CREATE TABLE `partner_external_clients` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_company_id` bigint(20) UNSIGNED NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NULL,
  `email` varchar(255) NULL,
  `phone` varchar(255) NULL,
  `industry` varchar(255) NULL,
  `sector` varchar(255) NULL,
  `address` text NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `status` enum('active', 'inactive', 'archived') DEFAULT 'active',
  `notes` text NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_company_id` (`partner_company_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`partner_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. Partner External Client Locations
```sql
CREATE TABLE `partner_external_client_locations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `location_type` varchar(255) NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `fiscal_year_start` varchar(16) DEFAULT 'January',
  `is_head_office` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `receives_utility_bills` tinyint(1) DEFAULT 0,
  `pays_electricity_proportion` tinyint(1) DEFAULT 0,
  `shared_building_services` tinyint(1) DEFAULT 0,
  `reporting_period` int(11) NULL,
  `measurement_frequency` enum('Annually','Half Yearly','Quarterly','Monthly') DEFAULT 'Annually',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. Partner External Client Measurements
```sql
CREATE TABLE `partner_external_client_measurements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `frequency` enum('monthly','quarterly','half_yearly','annually') NOT NULL,
  `status` enum('draft','submitted','under_review','not_verified','verified') DEFAULT 'draft',
  `fiscal_year` int(11) NOT NULL,
  `fiscal_year_start_month` varchar(16) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `total_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_1_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_2_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_3_co2e` decimal(15,6) DEFAULT 0.000000,
  `co2e_calculated_at` timestamp NULL,
  `emission_source_co2e` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_location_id` (`partner_external_client_location_id`),
  KEY `status` (`status`),
  KEY `fiscal_year` (`fiscal_year`),
  FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Partner External Client Measurement Data
```sql
CREATE TABLE `partner_external_client_measurement_data` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text NULL,
  `field_type` varchar(50) DEFAULT 'text',
  `created_by` bigint(20) UNSIGNED NULL,
  `updated_by` bigint(20) UNSIGNED NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_external_client_measurement_id` (`partner_external_client_measurement_id`),
  KEY `emission_source_id` (`emission_source_id`),
  FOREIGN KEY (`partner_external_client_measurement_id`) REFERENCES `partner_external_client_measurements` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources_master` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. Partner External Client Documents
```sql
CREATE TABLE `partner_external_client_documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('pdf','jpg','jpeg','png') NOT NULL,
  `file_size` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `source_type` enum('dewa','electricity','fuel','waste','water','transport','other') NOT NULL,
  `document_category` enum('bill','receipt','invoice','statement','contract','other') DEFAULT 'bill',
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `processed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ocr_confidence` decimal(5,2) NULL,
  `ocr_processed_at` timestamp NULL,
  `ocr_attempts` int(11) DEFAULT 0,
  `ocr_error_message` text NULL,
  `status` enum('pending','processing','extracted','reviewed','approved','rejected','integrated','failed') DEFAULT 'pending',
  `approved_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `approved_by` bigint(20) UNSIGNED NULL,
  `approved_at` timestamp NULL,
  `rejection_reason` text NULL,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NULL,
  `integration_status` enum('pending','integrated','failed') DEFAULT 'pending',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. Partner External Client Reports
```sql
CREATE TABLE `partner_external_client_reports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `file_path` varchar(500) NULL,
  `generated_at` datetime NULL,
  `generated_by` bigint(20) UNSIGNED NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `report_type` (`report_type`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. Partner External Client Emission Boundaries
```sql
CREATE TABLE `partner_external_client_emission_boundaries` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('Scope 1','Scope 2','Scope 3') NOT NULL,
  `selected_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_location_id` (`partner_external_client_location_id`),
  FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 8. Performance & Scalability

### Scale Analysis

**Your Scenario:**
- 100 Partners
- Each Partner has 100 External Clients
- Total: **10,000 External Clients**
- Each Client has: Multiple Locations, Measurements, Documents

### Database Size Estimate

```
partner_external_clients: 10,000 records × ~500 bytes = ~5 MB
partner_external_client_locations: 50,000 records × ~1 KB = ~50 MB
partner_external_client_measurements: 500,000 records × ~2 KB = ~1 GB
partner_external_client_measurement_data: 5,000,000 records × ~500 bytes = ~2.5 GB
partner_external_client_documents: 100,000 records × ~1 KB = ~100 MB
partner_external_client_reports: 20,000 records × ~500 bytes = ~10 MB

Total: ~3.7 GB (Very manageable!)
```

### Query Performance with Proper Indexing

**Critical Indexes:**
```sql
-- Partner external clients
CREATE INDEX idx_partner_clients_partner ON partner_external_clients(partner_company_id, status);

-- Partner external client locations
CREATE INDEX idx_partner_locations_client ON partner_external_client_locations(partner_external_client_id, is_active);

-- Partner external client measurements
CREATE INDEX idx_partner_measurements_location ON partner_external_client_measurements(partner_external_client_location_id, period_start, period_end);
CREATE INDEX idx_partner_measurements_status ON partner_external_client_measurements(status, fiscal_year);

-- Partner external client documents
CREATE INDEX idx_partner_documents_client ON partner_external_client_documents(partner_external_client_id, status);
```

**Query Performance:**
- Get partner's clients: **1-5ms** ✅
- Get client's locations: **1-5ms** ✅
- Get client's measurements: **10-50ms** ✅
- Get client's documents: **5-20ms** ✅

### Why Single Table Approach is Best

**Performance Benchmarks:**
| Records | Query Time | Status |
|---------|------------|--------|
| 10,000 | 1-5ms | ⚡ Excellent |
| 100,000 | 5-20ms | ✅ Very Good |
| 1,000,000 | 20-100ms | ✅ Good |

**Your Scale:** 10,000 clients = **1-5ms query time** ✅

**Benefits:**
- ✅ Fast queries with proper indexing
- ✅ Simple code structure
- ✅ Easy maintenance
- ✅ Scalable to 1M+ records
- ✅ Industry standard approach

**When to Reconsider:**
- Only if you reach 10+ million records per table
- Only if query time consistently > 500ms

---

## 9. Implementation Roadmap

### Phase 1: Database Foundation (Week 1-2)
- [ ] Create `subscription_plans` table
- [ ] Create `client_subscriptions` table
- [ ] Create `partner_subscriptions` table
- [ ] Create `partner_external_clients` table
- [ ] Create `partner_external_client_locations` table
- [ ] Create `partner_external_client_measurements` table
- [ ] Create `partner_external_client_measurement_data` table
- [ ] Create `partner_external_client_documents` table
- [ ] Create `partner_external_client_reports` table
- [ ] Create `partner_external_client_emission_boundaries` table
- [ ] Create `user_company_access` table
- [ ] Create `user_active_context` table
- [ ] Create `company_invitations` table
- [ ] Create `role_templates` table
- [ ] Create `company_custom_roles` table
- [ ] Create `feature_flags` table
- [ ] Create `usage_tracking` table
- [ ] Modify `companies` table
- [ ] Modify `users` table
- [ ] Add critical indexes
- [ ] Seed default subscription plans
- [ ] Seed default role templates

### Phase 2: Subscription Management (Week 3-4)
- [ ] Create SubscriptionPlan model
- [ ] Create ClientSubscription model
- [ ] Create PartnerSubscription model
- [ ] Create SubscriptionService
- [ ] Create FeatureFlagService
- [ ] Implement plan selection
- [ ] Implement subscription activation
- [ ] Implement feature checking middleware
- [ ] Build subscription management UI

### Phase 3: Role Management (Week 5-6)
- [ ] Create RoleTemplate model
- [ ] Create CompanyCustomRole model
- [ ] Create RoleManagementService
- [ ] Build role management UI (Client)
- [ ] Build role management UI (Partner)
- [ ] Implement custom role creation
- [ ] Implement permission assignment
- [ ] Integrate with Spatie Permission

### Phase 4: Multi-Account Access (Week 7-8)
- [ ] Create UserCompanyAccess model
- [ ] Create UserActiveContext model
- [ ] Create CompanyInvitation model
- [ ] Create AccountSelectorController
- [ ] Create CompanyInvitationService
- [ ] Build invitation system
- [ ] Build workspace switcher UI
- [ ] Implement account switching logic
- [ ] Update middleware for context switching

### Phase 5: Panel Separation (Week 9-10)
- [ ] Create ClientDashboardController
- [ ] Create PartnerDashboardController
- [ ] Create Partner\ExternalClientController
- [ ] Create Partner\ExternalClientLocationController
- [ ] Create Partner\ExternalClientMeasurementController
- [ ] Create Partner\ExternalClientDocumentController
- [ ] Create Partner\ExternalClientReportController
- [ ] Build client dashboard UI
- [ ] Build partner dashboard UI
- [ ] Build external client management UI
- [ ] Implement route separation
- [ ] Implement middleware for entity types
- [ ] Update shared components

### Phase 6: Usage Tracking (Week 11)
- [ ] Create UsageTracking model
- [ ] Create UsageTrackingService
- [ ] Implement usage tracking for all resources
- [ ] Build usage analytics UI
- [ ] Implement limit checking

### Phase 7: Testing & Optimization (Week 12-13)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Performance testing
- [ ] Database optimization
- [ ] Caching implementation
- [ ] Query optimization

### Phase 8: Documentation & Deployment (Week 14)
- [ ] API documentation
- [ ] User guides
- [ ] Admin documentation
- [ ] Deployment plan
- [ ] Migration scripts

---

## 10. Key Services & Models

### Models

#### Subscription Models
```php
// app/Models/SubscriptionPlan.php
// app/Models/ClientSubscription.php
// app/Models/PartnerSubscription.php
```

#### Partner External Client Models
```php
// app/Models/PartnerExternalClient.php
// app/Models/PartnerExternalClientLocation.php
// app/Models/PartnerExternalClientMeasurement.php
// app/Models/PartnerExternalClientMeasurementData.php
// app/Models/PartnerExternalClientDocument.php
// app/Models/PartnerExternalClientReport.php
// app/Models/PartnerExternalClientEmissionBoundary.php
```

#### Access Management Models
```php
// app/Models/UserCompanyAccess.php
// app/Models/UserActiveContext.php
// app/Models/CompanyInvitation.php
// app/Models/RoleTemplate.php
// app/Models/CompanyCustomRole.php
```

#### Existing Models (Enhanced)
```php
// app/Models/User.php (enhanced with multi-account methods)
// app/Models/Company.php (enhanced with company_type)
// app/Models/Location.php (existing)
// app/Models/Measurement.php (existing)
// app/Models/DocumentUpload.php (existing)
```

### Services

#### SubscriptionService
```php
class SubscriptionService
{
    public function subscribeClient($companyId, $planId);
    public function subscribePartner($companyId, $planId);
    public function getActiveSubscription($companyId, $type);
    public function checkFeatureAccess($companyId, $featureCode);
    public function getPlanLimits($companyId);
    public function canAddMoreClients($partnerId);
    public function getClientLimit($partnerId);
}
```

#### FeatureFlagService
```php
class FeatureFlagService
{
    public function enableFeature($companyId, $featureCode);
    public function disableFeature($companyId, $featureCode);
    public function isFeatureEnabled($companyId, $featureCode);
    public function getEnabledFeatures($companyId);
}
```

#### RoleManagementService
```php
class RoleManagementService
{
    public function getAvailableTemplates($companyType);
    public function createCustomRole($companyId, $roleName, $permissions);
    public function updateCustomRole($roleId, $data);
    public function deleteCustomRole($roleId);
    public function assignRoleToUser($user, $role, $companyId = null);
}
```

#### PartnerExternalClientService
```php
class PartnerExternalClientService
{
    public function addExternalClient($partnerId, $clientData);
    public function updateExternalClient($clientId, $clientData);
    public function deleteExternalClient($clientId);
    public function getExternalClients($partnerId);
    public function canAddMoreClients($partnerId);
    public function getClientLimit($partnerId);
    public function getClientCount($partnerId);
}
```

#### CompanyInvitationService
```php
class CompanyInvitationService
{
    public function inviteUser($companyId, $email, $roleId, $invitedBy);
    public function acceptInvitation($token, $userId = null);
    public function revokeAccess($userCompanyAccessId);
    public function updateAccessRole($userCompanyAccessId, $roleId);
}
```

#### UsageTrackingService
```php
class UsageTrackingService
{
    public function trackUsage($companyId, $resourceType, $action, $quantity = 1);
    public function getUsage($companyId, $period = 'monthly');
    public function checkLimit($companyId, $resourceType);
    public function getRemainingQuota($companyId, $resourceType);
}
```

---

## Complete System Flow

### Direct Client Flow
```
1. Client registers → Creates company account
2. Client subscribes → Selects plan (Free/Starter/Growth/Pro)
3. Client adds staff → Assigns roles
4. Staff adds locations → Creates measurements
5. Staff uploads documents → Processes data
6. System generates reports
```

### Partner External Client Flow
```
1. Partner registers → Creates partner account
2. Partner subscribes → Selects plan (Free/Partner/Enterprise)
3. Partner adds external clients → Up to limit (2/10/unlimited)
4. Partner staff selects client → Adds client data
5. Partner staff adds locations → For selected client
6. Partner staff adds measurements → For selected location
7. Partner staff uploads documents → For selected client
8. System generates reports → Per client
9. Each client has completely separate data
```

### Multi-Account Staff Flow
```
1. Company owner invites staff → Email invitation
2. Staff accepts invitation → Creates/links account
3. System creates user_company_access record
4. Staff logs in → Sees account selector (if multiple access)
5. Staff selects account → System sets active context
6. Staff works in selected account → All queries filtered
7. Staff can switch accounts → Via dropdown
```

---

## Summary

### Finalized Architecture ✅

1. **Two Separate Entities**: Clients and Partners (complete separation)
2. **URL Structure**: `/dashboard` for clients, `/partner/dashboard` for partners
3. **Subscription Plans**:
   - Clients: Free, Starter, Growth, Pro (configurable by superadmin)
   - Partners: Free (2 clients), Partner (10 clients), Enterprise (unlimited) - configurable by superadmin
4. **Data Separation**: Separate tables for direct clients vs partner external clients
5. **Single Users Table**: All staff in one table
6. **Multi-Account Access**: Staff can access multiple companies with different roles
7. **Performance**: Single table approach handles 10K+ external clients easily
8. **Scalability**: Designed to handle thousands of users

### Key Principles

- **Separation**: Complete separation between clients and partners
- **Flexibility**: Configurable plans, features, and roles
- **Scalability**: Designed to handle thousands of users
- **Extensibility**: Easy to add new features and permissions
- **Performance**: Optimized with proper indexing and caching

---

## Next Steps

1. ✅ Review this master document
2. ✅ Confirm all design decisions
3. ✅ Begin Phase 1 implementation (Database Foundation)
4. ✅ Proceed with development phase by phase

---

**Document Status**: Finalized & Consolidated ✅  
**Ready for Implementation**: Yes 🚀

