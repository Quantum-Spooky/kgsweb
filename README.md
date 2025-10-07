# KGSweb Google Integration Plugin – Handoff Specification

---

## Spec: Root Integration File (`kgsweb-google-integration.php`)

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

1. File guards with `if (!defined('ABSPATH')) exit;`
2. Defines constants (paths, version, security hash)
3. Loads all class modules from `/includes/`:

   * `class-kgsweb-google-drive-docs.php`
   * `class-kgsweb-google-rest-api.php`
   * `class-kgsweb-google-integration.php` (Google API client + auth)
   * `class-kgsweb-google-admin.php`
   * `class-kgsweb-google-shortcodes.php`
   * `class-kgsweb-google-secure-upload.php`
   * `class-kgsweb-google-ticker.php`
   * `class-kgsweb-google-upcoming-events.php`
   * `class-kgsweb-google-helpers.php`
   * `class-kgsweb-google-display.php`
   * `class-kgsweb-google-slides.php`
   * `class-kgsweb-google-sheets.php`
4. Loads Composer autoloader (`vendor/autoload.php`)
5. Hooks anonymous function on `plugins_loaded` to initialize:

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

* Serves as dependency loader and system entry point.
* Delegates all logic to modular class files.
* Establishes uniform namespace `KGSweb_Google_*` for maintainability.
* Ensures all subsystems (shortcodes, REST endpoints, admin, caching) register after WordPress is ready.

**Dependencies**

* WordPress core hooks (`plugins_loaded`)
* Composer libraries (Google Client)
* `/includes/` PHP modules
* `/vendor/autoload.php`

**Notes for Future Developers**

* Do not add feature logic directly here.
* Only define constants and include or initialize classes.
* When adding new subsystems, create `class-kgsweb-google-<feature>.php` and add a `::init()` call here.
* Use consistent naming and directory structure for clarity.

---

## Spec: Google Integration Core (`includes/class-kgsweb-google-integration.php`)

**Purpose**
Creates and manages authenticated Google API client instances shared by all plugin modules. Handles enqueueing of shared front-end assets and sets basic plugin parameters.

**Primary Class**
`KGSweb_Google_Integration`

**Key Responsibilities**

* Initialize singleton instance of Google API client
* Provide access to Drive, Docs, Calendar, Sheets, and Slides services
* Register and enqueue shared CSS/JS assets
* Maintain global plugin paths and configuration
* Enforce upload lockout parameters (used by secure upload system)

### Class Structure

**Properties**

```php
private static ?self $instance     // Singleton handle
private ?Client $client            // Google\Client instance
private ?KGSweb_Google_Drive_Docs $drive
private ?Docs $docsService
private ?Calendar $calendar
private ?Sheets $sheets
private ?Slides $slides
private string $plugin_url
private string $plugin_path
private int $lockout_time = 86400  // 24 hours
private int $max_attempts = 50
```

**Initialization**

```php
public static function init(): self
    → Ensures single instance
    → Calls register_hooks()
```

**Constructor**

* Stores plugin path and URL for later reference.

### WordPress Hook Integration

`register_hooks()` attaches:

```php
add_action('init', [$this, 'register_assets'])
add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend'])
```

`register_assets()`

* Registers plugin-wide JS and CSS bundles for use by front-end modules.

`enqueue_frontend()`

* Enqueues scripts/styles globally when needed for dynamic Drive displays, ticker, and calendar components.

### Google API Handling

* `get_client()` → Creates and caches a configured `Google\Client` using a service account JSON file.
* `get_drive()`, `get_docs_service()`, `get_calendar()`, `get_sheets()`, `get_slides()` → Lazy-load and cache Google API service objects.

Typical scopes:

```text
Drive::DRIVE_READONLY
Calendar::CALENDAR_READONLY
Sheets::SPREADSHEETS_READONLY
Slides::PRESENTATIONS_READONLY
Docs::DOCUMENTS_READONLY
```

### Configuration and Security

* `lockout_time` and `max_attempts` define how long upload logins remain locked after repeated failures (used by `Secure_Upload` module).
* The hash constant `KGSWEB_UPLOAD_PASS_HASH` from the root file provides the baseline authentication gate.

### Dependencies

* PHP Google Client Library (`use Google\Client;`)
* Internal helper class: `KGSweb_Google_Drive_Docs`
* WordPress hooks: `init`, `wp_enqueue_scripts`

### Data Flow

```text
Shortcode/REST request
→ Calls KGSweb_Google_Integration::init()
→ get_client() ensures valid token
→ Feature class (e.g. Display, Events, Sheets) requests the appropriate service
→ Service executes API call → returns structured data
→ Cached via WordPress transients or helper methods
```

### Developer Notes

* Always access Google services through this class.
* When adding new Google service types, extend this file with a `get_<service>()` method.
* Asset registration should remain centralized here for predictable enqueue behavior.
* If credentials are relocated, update `get_client()` accordingly.
* Avoid adding feature logic—limit this file to shared service setup and base utilities.

---

# Handoff Spec: `class-kgsweb-google-drive-docs.php`

## Purpose

`KGSweb_Google_Drive_Docs` provides an interface between WordPress and Google Drive for:

1. Retrieving folder contents (files and subfolders).
2. Parsing Google Drive API responses into structured arrays.
3. Caching results for performance.
4. Generating download links for Google Docs/Sheets/Slides.
5. Supporting shortcodes for front-end display.

## Dependencies

* Google API PHP Client (`Google\Client`, `Google\Service\Drive`).
* WordPress caching (`set_transient`, `get_transient`) and utility functions.
* Plugin admin settings: cache expiration, folder whitelist/blacklist, default sort.

## Class Skeleton

```php
class KGSweb_Google_Drive_Docs {

    protected $client; // Google Client
    protected $service; // Google Drive Service

    public function __construct(Google\Client $client = null) {}

    public function get_folder_contents($folder_id, $options = []) {}

    protected function parse_drive_items($items) {}

    protected function is_folder($item) {}

    protected function is_google_doc($item) {}

    public function get_download_link($item, $format = 'pdf') {}

    protected function cache_get($key) {}
    protected function cache_set($key, $data, $expiration = 3600) {}

    public function parse_folder_tree($folder_id, $depth = 0, $options = []) {}

    protected function filter_items($items, $filters = []) {}
    protected function sort_items($items, $sort_order = 'name_asc') {}

    // Shortcode handler
    public function shortcode_handler($atts) {}
}
```

### Shortcode Instructions

**Syntax**

```text
[kgsweb_drive_folder id="FOLDER_ID" view="list|grid" sort="name_asc|modified_desc" filter="pdf,docx,folder" max_depth="1"]
```

**Attributes**

| Attribute   | Type   | Default    | Description                            |
| ----------- | ------ | ---------- | -------------------------------------- |
| `id`        | string | *required* | Google Drive folder ID                 |
| `view`      | string | `list`     | `list` (vertical) or `grid` (cards)    |
| `sort`      | string | `name_asc` | Sorting order                          |
| `filter`    | string | `all`      | Comma-separated MIME types or `folder` |
| `max_depth` | int    | 1          | Recursive folder depth                 |

**Examples**

```text
[kgsweb_drive_folder id="1AbCDefGhIJklmnopQRsT"]
[kgsweb_drive_folder id="1AbCDefGhIJklmnopQRsT" view="grid" filter="pdf,docx" sort="modified_desc"]
[kgsweb_drive_folder id="1AbCDefGhIJklmnopQRsT" max_depth="2" view="list"]
```

**Developer Notes**

* Register shortcode via:

```php
add_shortcode('kgsweb_drive_folder', [$instance, 'shortcode_handler']);
```

* `shortcode_handler` calls `parse_folder_tree` and formats output.
* Caching is automatic; front-end rendering does not require additional caching.

---

# KGSweb Google Plugin – Quick Reference

| **Category** | **Feature / Type**  | **Shortcode Example**                           | **Admin Setting / Folder Mapping** | **Description / Notes**                                      |
| ------------ | ------------------- | ----------------------------------------------- | ---------------------------------- | ------------------------------------------------------------ |
| **Display**  | **Breakfast Menus** | `[kgsweb_display type="breakfast" view="list"]` | *Breakfast Menu Folder ID*         | Displays Drive PDFs/images for breakfast menus in list form. |
|              | **Lunch Menus**     | `[                                              |                                    |                                                              |


kgsweb_display type="lunch" view="grid"]`                      | *Lunch Menu Folder ID*             | Same as breakfast, shown in grid view.                          | |              | **Calendars**                   |`[kgsweb_display type="calendar" filter="pdf"]`                  | *Calendar Folder ID*               | Lists school calendars (PDFs).                                  | |              | **Feature Images**              |`[kgsweb_display type="feature_image" view="grid" max_items="3"]`| *Feature Image Folder ID*          | Displays featured images visually.                              | | **Ticker**   | **News / Announcements Ticker** |`[kgsweb_google_ticker folder="abc123"]`                         | *Ticker Folder ID*                 | Rotates Drive content in a horizontal ticker. Not Sheets-based. | | **Events**   | **Upcoming Events**             |`[kgsweb_upcoming_events folder="xyz456"]`                       | *Events Folder ID*                 | Lists or grids upcoming events from Drive.                      | | **Sites**    | **Google Sites Embed**          |`[kgsweb_google_site url="[https://sites.google.com/](https://sites.google.com/)..."]`        | *Allowed Sites (Admin Setting)*    | Embeds a Google Sites page via iframe.                          | | **Drive**    | **Drive Folder Display**        |`[kgsweb_drive_folder id="folder_id_here"]`                      | *N/A*                              | Lists contents of any Drive folder. Supports caching.           | | **Slides**   | **Google Slides Embed**         |`[kgsweb_slides id="presentation_id"]`                           | *N/A*                              | Embeds a Google Slides presentation in a post or page.          | | **Sheets**   | **Google Sheets Display**       |`[kgsweb_sheets id="sheet_id" range="A1:E10"]`                    | *N/A*                              | Displays a sheet as an HTML table.                              |
| **Uploads**  | **Secure Upload**               | *Handled internally / via admin UI*                               | *Upload Target Folder*             | Validates and uploads user files securely to Drive.             |
| **API**      | **REST Endpoints**              | *No shortcode*                                                    | *N/A*                              | Provides JSON endpoints for menus, events, Drive data.          |
| **Helpers**  | **Utilities / PDF Tools**       | *No shortcode*                                                    | *N/A*                              | Shared utilities for MIME types, PDF→PNG, date formatting.      |

### Admin Summary

| **Setting Name**                | **Purpose**                   | **Used By**         |
| ------------------------------- | ----------------------------- | ------------------- |
| `kgsweb_drive_breakfast_folder` | Folder for breakfast menus    | Display             |
| `kgsweb_drive_lunch_folder`     | Folder for lunch menus        | Display             |
| `kgsweb_drive_calendar_folder`  | Folder for calendars          | Display             |
| `kgsweb_drive_feature_folder`   | Folder for feature images     | Display             |
| `kgsweb_drive_ticker_folder`    | Folder for ticker items       | Ticker              |
| `kgsweb_drive_events_folder`    | Folder for upcoming events    | Upcoming Events     |
| `kgsweb_allowed_sites`          | Whitelisted Google Sites URLs | Google Sites Embed  |
| `kgsweb_cache_duration`         | Cache lifetime for Drive data | Drive Docs, Display |

### Core Classes at a Glance

| **Class**                       | **Responsibility**                                  |
| ------------------------------- | --------------------------------------------------- |
| `KGSweb_Google_Integration`     | Initializes all modules, sets up Google Client      |
| `KGSweb_Google_Admin`           | Admin menus, settings page, folder mappings         |
| `KGSweb_Google_Shortcodes`      | Registers all shortcodes and dispatches handlers    |
| `KGSweb_Google_Drive_Docs`      | Fetches, parses, and caches Drive folder contents   |
| `KGSweb_Google_Secure_Upload`   | Handles secure uploads to Drive                     |
| `KGSweb_Google_Ticker`          | Displays scrolling/rotating content                 |
| `KGSweb_Google_Display`         | Handles menus, calendars, and feature image display |
| `KGSweb_Google_Upcoming_Events` | Displays upcoming events from Drive                 |
| `KGSweb_Google_Slides`          | Embeds Google Slides                                |
| `KGSweb_Google_Sheets`          | Displays Google Sheets data                         |
| `KGSweb_Google_REST_API`        | Provides REST endpoints for plugin data             |
| `KGSweb_Google_Helpers`         | Shared utilities (PDFs, filenames, MIME, dates)     |

### Developer Flow Overview

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

## Spec: Ticker (`includes/class-kgsweb-google-ticker.php`)

**Purpose**  
Displays scrolling or rotating Drive content (news, announcements) in a front-end ticker.

**Primary Class**  
`KGSweb_Google_Ticker`

**Key Responsibilities**

* Fetch folder contents from Drive (via `KGSweb_Google_Drive_Docs`)
* Render a horizontal or vertical ticker
* Support shortcode attributes for folder ID, speed, and max items
* Caching via transients to minimize API calls

**Shortcode Syntax**

```text
[kgsweb_google_ticker folder="FOLDER_ID" max_items="5" speed="normal"]
````

**Attributes**

| Attribute   | Type   | Default  | Description                      |
| ----------- | ------ | -------- | -------------------------------- |
| `folder`    | string | required | Google Drive folder ID           |
| `max_items` | int    | 5        | Max items to display             |
| `speed`     | string | normal   | scroll speed: slow, normal, fast |

---

## Spec: Display (`includes/class-kgsweb-google-display.php`)

**Purpose**
Handles front-end display of menus, calendars, and feature images pulled from Drive.

**Primary Class**
`KGSweb_Google_Display`

**Key Responsibilities**

* Fetch folder contents via Drive Docs class
* Format and render in **list** or **grid** layouts
* Filter by type (`breakfast`, `lunch`, `calendar`, `feature_image`)
* Support shortcode for flexible front-end display

**Shortcode Syntax**

```text
[kgsweb_display type="TYPE" view="list|grid" max_items="N" filter="pdf,docx"]
```

**Attributes**

| Attribute   | Type   | Default  | Description                               |
| ----------- | ------ | -------- | ----------------------------------------- |
| `type`      | string | required | breakfast, lunch, calendar, feature_image |
| `view`      | string | list     | list or grid                              |
| `max_items` | int    | all      | Maximum number of items to show           |
| `filter`    | string | all      | MIME type filter                          |

---

## Spec: Upcoming Events (`includes/class-kgsweb-google-upcoming-events.php`)

**Purpose**
Displays upcoming events from a Drive folder (PDFs, calendar exports).

**Primary Class**
`KGSweb_Google_Upcoming_Events`

**Key Responsibilities**

* Fetch and filter future events
* Render as list or grid
* Shortcode support with optional limit and date filter

**Shortcode Syntax**

```text
[kgsweb_upcoming_events folder="FOLDER_ID" limit="5" view="list"]
```

**Attributes**

| Attribute | Type   | Default  | Description            |
| --------- | ------ | -------- | ---------------------- |
| `folder`  | string | required | Google Drive folder ID |
| `limit`   | int    | 5        | Max events to display  |
| `view`    | string | list     | list or grid           |

---

## Spec: Slides (`includes/class-kgsweb-google-slides.php`)

**Purpose**
Embeds Google Slides presentations in posts or pages.

**Primary Class**
`KGSweb_Google_Slides`

**Shortcode Syntax**

```text
[kgsweb_slides id="PRESENTATION_ID" width="800" height="600"]
```

**Attributes**

| Attribute | Type   | Default  | Description                   |
| --------- | ------ | -------- | ----------------------------- |
| `id`      | string | required | Google Slides presentation ID |
| `width`   | int    | 800      | Width in pixels               |
| `height`  | int    | 600      | Height in pixels              |

---

## Spec: Sheets (`includes/class-kgsweb-google-sheets.php`)

**Purpose**
Displays Google Sheets data as HTML tables in posts or pages.

**Primary Class**
`KGSweb_Google_Sheets`

**Shortcode Syntax**

```text
[kgsweb_sheets id="SHEET_ID" range="A1:E10"]
```

**Attributes**

| Attribute | Type   | Default  | Description              |
| --------- | ------ | -------- | ------------------------ |
| `id`      | string | required | Google Sheet ID          |
| `range`   | string | all      | Cell range (A1 notation) |

---

## Spec: Secure Upload (`includes/class-kgsweb-google-secure-upload.php`)

**Purpose**
Handles secure, authenticated user uploads to Drive.

**Primary Class**
`KGSweb_Google_Secure_Upload`

**Key Responsibilities**

* Validate file type, size, and MIME
* Enforce lockout policy using `lockout_time` and `max_attempts`
* Upload to configured Drive folder
* Return status messages or error codes

---

## Spec: REST API (`includes/class-kgsweb-google-rest-api.php`)

**Purpose**
Provides REST endpoints for menus, events, and Drive folder data.

**Primary Class**
`KGSweb_Google_REST_API`

**Key Responsibilities**

* Register endpoints with WordPress REST API
* Fetch cached Drive data
* Respond with JSON for front-end AJAX or external apps
* Maintain consistent API versioning

---

## Spec: Helpers (`includes/class-kgsweb-google-helpers.php`)

**Purpose**
Utility class with shared functions.

**Primary Class**
`KGSweb_Google_Helpers`

**Responsibilities**

* MIME type mapping
* PDF → PNG conversion
* Date formatting helpers
* Cache key generation
* Shared validation utilities

---

### End of README.md
