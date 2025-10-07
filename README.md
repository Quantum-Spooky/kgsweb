# KGSweb Google Integration Plugin – Handoff Documentation

---

## Table of Contents

1. [Root Integration File](#root-integration-file-kgsweb-google-integrationphp)  
2. [Google Integration Core](#google-integration-core-includesclass-kgsweb-google-integrationphp)  
3. [Drive Docs](#drive-docs-class-kgsweb-google-drive-docsphp)  
4. [Ticker](#ticker-includesclass-kgsweb-google-tickerphp)  
5. [Display](#display-includesclass-kgsweb-google-displayphp)  
6. [Upcoming Events](#upcoming-events-includesclass-kgsweb-google-upcoming-eventsphp)  
7. [Slides](#slides-includesclass-kgsweb-google-slidesphp)  
8. [Sheets](#sheets-includesclass-kgsweb-google-sheetsphp)  
9. [Secure Upload](#secure-upload-includesclass-kgsweb-google-secure-uploadphp)  
10. [REST API](#rest-api-includesclass-kgsweb-google-rest-apiphp)  
11. [Helpers](#helpers-includesclass-kgsweb-google-helpersphp)  
12. [Quick Reference Table](#kgsweb-google-plugin-quick-reference)  
13. [Admin Summary](#admin-summary)  
14. [Core Classes at a Glance](#core-classes-at-a-glance)  
15. [Developer Flow Overview](#developer-flow-overview)

---

## Root Integration File (`kgsweb-google-integration.php`)

**Purpose**  
Central bootstrap for all KGSWeb Google Integration plugin features. Defines constants, loads dependencies, and initializes feature classes.

**Key Constants**

```text
KGSWEB_PLUGIN_VERSION      // version string  
KGSWEB_PLUGIN_FILE         // main plugin file path  
KGSWEB_PLUGIN_DIR          // plugin directory  
KGSWEB_PLUGIN_URL          // plugin URL  
KGSWEB_SETTINGS_OPTION     // admin settings key  
KGSWEB_UPLOAD_PASS_HASH    // pre-hashed upload gate password
````

**Initialization Flow**

1. File guard: `if (!defined('ABSPATH')) exit;`
2. Define constants (paths, version, security hash)
3. Load all `/includes/` modules
4. Load Composer autoloader: `vendor/autoload.php`
5. Hook `plugins_loaded` → initialize classes:

```php
KGSweb_Google_Integration::init();
KGSweb_Google_Admin::init();
KGSweb_Google_REST_API::init();
KGSweb_Google_Shortcodes::init();
KGSweb_Google_Secure_Upload::init();
KGSweb_Google_Ticker::init();
KGSweb_Google_Upcoming_Events::init();
```

**Responsibilities**

* Dependency loader and system entry point
* Delegates logic to modular class files
* Establishes uniform namespace `KGSweb_Google_*`
* Ensures subsystems register after WordPress is ready

**Developer Notes**

* Avoid adding feature logic here
* New subsystems → create `class-kgsweb-google-<feature>.php` + `::init()`
* Maintain consistent naming and directory structure

---

## Spec: Google Integration Core (`includes/class-kgsweb-google-integration.php`)

**Purpose**
Creates and manages authenticated Google API client instances shared by all plugin modules. Handles enqueueing of shared front-end assets.

**Class:** `KGSweb_Google_Integration`

**Key Responsibilities**

* Singleton Google API client instance
* Access to Drive, Docs, Calendar, Sheets, Slides
* Register/enqueue shared JS/CSS
* Maintain plugin paths and configuration
* Upload lockout for Secure Upload

**Properties**

```php
private static ?self $instance
private ?Client $client
private ?KGSweb_Google_Drive_Docs $drive
private ?Docs $docsService
private ?Calendar $calendar
private ?Sheets $sheets
private ?Slides $slides
private string $plugin_url
private string $plugin_path
private int $lockout_time = 86400
private int $max_attempts = 50
```

**Hooks**

```php
add_action('init', [$this, 'register_assets']);
add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
```

**Google API Services**

* `get_client()`
* `get_drive()`
* `get_docs_service()`
* `get_calendar()`
* `get_sheets()`
* `get_slides()`

Scopes include:

```text
Drive::DRIVE_READONLY
Calendar::CALENDAR_READONLY
Sheets::SPREADSHEETS_READONLY
Slides::PRESENTATIONS_READONLY
Docs::DOCUMENTS_READONLY
```

---

## Handoff Spec: class-kgsweb-google-drive-docs.php

**Purpose:** Interface between WordPress and Google Drive for:

* Retrieving folder contents
* Parsing API responses
* Caching results
* Generating download links
* Supporting front-end shortcodes

**Shortcode Syntax**

```text
[kgsweb_drive_folder id="FOLDER_ID" view="list|grid" sort="name_asc|modified_desc" filter="pdf,docx,folder" max_depth="1"]
```

**Example:**

```text
[kgsweb_drive_folder id="1AbCDefGhIJklmnopQRsT" view="grid" filter="pdf,docx" sort="modified_desc"]
```

---

## Spec: Ticker (`includes/class-kgsweb-google-ticker.php`)

**Shortcode Syntax**

```text
[kgsweb_google_ticker folder="FOLDER_ID" max_items="5" speed="normal"]
```

---

## Spec: Display (`includes/class-kgsweb-google-display.php`)

**Shortcode Syntax**

```text
[kgsweb_display type="breakfast|lunch|calendar|feature_image" view="list|grid" max_items="N" filter="pdf,docx"]
```

---

## Spec: Upcoming Events (`includes/class-kgsweb-google-upcoming-events.php`)

**Shortcode Syntax**

```text
[kgsweb_upcoming_events folder="FOLDER_ID" limit="5" view="list"]
```

---

## Spec: Slides (`includes/class-kgsweb-google-slides.php`)

**Shortcode Syntax**

```text
[kgsweb_slides id="PRESENTATION_ID" width="800" height="600"]
```

---

## Spec: Sheets (`includes/class-kgsweb-google-sheets.php`)

**Shortcode Syntax**

```text
[kgsweb_sheets id="SHEET_ID" range="A1:E10"]
```

---

## Spec: Secure Upload (`includes/class-kgsweb-google-secure-upload.php`)

* Validates file type, size, MIME
* Enforces lockout (`lockout_time`, `max_attempts`)
* Uploads to Drive folder

---

## Spec: REST API (`includes/class-kgsweb-google-rest-api.php`)

* Provides JSON endpoints for menus, events, Drive folder data
* Registers routes via WordPress REST API

---

## Spec: Helpers (`includes/class-kgsweb-google-helpers.php`)

* MIME type mapping
* PDF → PNG conversion
* Date formatting
* Cache key generation
* Shared validation utilities

---

## KGSweb Google Plugin – Quick Reference

| Category | Feature / Type        | Shortcode Example                                         | Admin Folder / Setting   | Notes                     |
| -------- | --------------------- | --------------------------------------------------------- | ------------------------ | ------------------------- |
| Display  | Breakfast Menus       | `[kgsweb_display type="breakfast" view="list"]`           | Breakfast Menu Folder ID | PDF/image menus           |
|          | Lunch Menus           | `[kgsweb_display type="lunch" view="grid"]`               | Lunch Menu Folder ID     | Grid display              |
|          | Calendars             | `[kgsweb_display type="calendar" filter="pdf"]`           | Calendar Folder ID       | PDF calendars             |
|          | Feature Images        | `[kgsweb_display type="feature_image" view="grid"]`       | Feature Image Folder ID  | Visual featured images    |
| Ticker   | News / Announcements  | `[kgsweb_google_ticker folder="abc123"]`                  | Ticker Folder ID         | Rotating content          |
| Events   | Upcoming Events       | `[kgsweb_upcoming_events folder="xyz456"]`                | Events Folder ID         | List/grid upcoming events |
| Sites    | Google Sites Embed    | `[kgsweb_google_site url="https://sites.google.com/..."]` | Allowed Sites Setting    | Iframe embed              |
| Drive    | Drive Folder Display  | `[kgsweb_drive_folder id="folder_id_here"]`               | N/A                      | Cached listing            |
| Slides   | Google Slides Embed   | `[kgsweb_slides id="presentation_id"]`                    | N/A                      | Embeds slides             |
| Sheets   | Google Sheets Display | `[kgsweb_sheets id="sheet_id" range="A1:E10"]`            | N/A                      | Table display             |
| Uploads  | Secure Upload         | Handled internally                                        | Upload Target Folder     | Secure Drive upload       |
| API      | REST Endpoints        | N/A                                                       | N/A                      | JSON endpoints            |
| Helpers  | Utilities / PDF Tools | N/A                                                       | N/A                      | Shared utilities          |

---

## Admin Summary

| Setting Name


| Purpose                         | Used By             |
| -------------------------------| --------------------------------| ------------------ |
| `kgsweb_drive_breakfast_folder` | Breakfast menus folder          | Display             |
| `kgsweb_drive_lunch_folder`     | Lunch menus folder              | Display             |
| `kgsweb_drive_calendar_folder`  | Calendar folder                 | Display             |
| `kgsweb_drive_feature_folder`   | Feature images                  | Display             |
| `kgsweb_drive_ticker_folder`    | Ticker items                    | Ticker              |
| `kgsweb_drive_events_folder`    | Upcoming events                 | Upcoming Events     |
| `kgsweb_allowed_sites`          | Allowed Sites URLs              | Google Sites Embed  |
| `kgsweb_cache_duration`         | Cache lifetime (seconds)        | Drive Docs, Display |

---

## Core Classes at a Glance

| Class                           | Responsibility                                |
| ------------------------------- | --------------------------------------------- |
| `KGSweb_Google_Integration`     | Initializes modules, sets up Google Client    |
| `KGSweb_Google_Admin`           | Admin menus, folder mappings                  |
| `KGSweb_Google_Shortcodes`      | Registers all shortcodes, dispatches handlers |
| `KGSweb_Google_Drive_Docs`      | Fetches, parses, caches Drive folder contents |
| `KGSweb_Google_Secure_Upload`   | Secure Drive uploads                          |
| `KGSweb_Google_Ticker`          | Rotating content display                      |
| `KGSweb_Google_Display`         | Menus, calendars, feature images              |
| `KGSweb_Google_Upcoming_Events` | Upcoming events display                       |
| `KGSweb_Google_Slides`          | Embed Google Slides                           |
| `KGSweb_Google_Sheets`          | Display Google Sheets                         |
| `KGSweb_Google_REST_API`        | Provides JSON endpoints                       |
| `KGSweb_Google_Helpers`         | Shared utilities (PDFs, MIME, dates)          |

---

## Developer Flow Overview

```text
[shortcode invoked] 
     ↓
KGSweb_Google_Shortcodes → target class
     ↓
Drive data fetched via KGSweb_Google_Drive_Docs
     ↓
Filtered / sorted / formatted
     ↓
Rendered (list, grid, ticker, embed)
     ↓
Displayed on front-end
```

---

### End of README.md

