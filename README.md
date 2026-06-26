# Search Analytics & Insights

Track WordPress site searches and provide detailed analytics for administrators. Understanding what your users are searching for is key to optimizing your content, finding missing pages, and improving your overall user experience.

| Metadata | Details |
| :--- | :--- |
| **Version** | 1.0.0 |
| **Requires WordPress** | 6.0 or higher |
| **Requires PHP** | 8.0 or higher |
| **Author** | Vivek Prajapati |
| **License** | GPLv2 or later |
| **License URI** | [GNU GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) |
| **Text Domain** | `search-analytics-insights` |
| **Domain Path** | `/languages` |

---

## Features

Search Analytics & Insights comes pre-packaged with features built for speed, utility, and user privacy:

*   **WordPress Search Tracking**: Seamlessly intercepts and logs every search query executed via the default WordPress search.
*   **AJAX Search Tracking**: Automatically tracks live queries performed using the plugin's AJAX live search form.
*   **Search Analytics Dashboard**: A dedicated, premium administration dashboard with summaries, filter logs, and pagination.
*   **Top Searched Terms**: Displays the most frequently searched keywords, giving you immediate insight into popular content.
*   **Recent Searches**: Monitors search behavior in real-time by listing the latest user queries.
*   **Search History**: Full historical logging of search terms, counts, and origin locations.
*   **Search Result Count Tracking**: Logs the number of matches returned for every search term, helping you identify searches yielding empty results.
*   **Page Title Tracking**: Dynamically captures and resolves the title of the WordPress page where the search form was used.
*   **Page URL Tracking**: Logs the exact web address where searches originated.
*   **Page Type Tracking**: Identifies the template style or content type of the originating page (e.g. single post, archive page, etc.).
*   **Referrer Tracking**: Captures the HTTP referrer header when a search query is submitted.
*   **Search Filters**: Granular reporting filters (filter by keyword, post type, date range, page title, URL, username, or no-results-only).
*   **Date Range Filtering**: Focus your analysis on specific date intervals with datepicker selectors.
*   **Search Term Filtering**: Easily search through your transaction logs for specific words.
*   **Page Type Filtering**: Filter metrics by specific WordPress post types or archive pages.
*   **CSV Export**: Securely exports database logs into a structured CSV file with formulas escaped to prevent CSV injection.
*   **Responsive Admin Interface**: A clean, modern cards layout in the WordPress administration panels that renders beautifully across all viewports.
*   **Gutenberg Search Widget Block**: Embed an interactive search trigger button and popup anywhere in your block layouts.
*   **WordPress Widget Support**: Classic widget support for legacy widget sidebars and layout areas.
*   **Shortcode Support**: Flexible shortcodes to display search forms, trending searches, and popular terms.
*   **Configurable AJAX Search**: Fine-tune character limits, debounce rates, maximum results, and display styles.
*   **Searchable Post Types**: Restrict or permit searches to specific public content types (such as custom post types, posts, pages, or products).
*   **Privacy-First Architecture**: Strictly stores data locally in your WordPress database with zero IP logging.
*   **Lightweight**: Minimal performance footprint, optimized indexes, and efficient asset queuing.
*   **No External Tracking Services**: Absolute independence from third-party metrics platforms or external APIs.
*   **WordPress Coding Standards**: Built using clean, secure code that aligns with WordPress development standards.

---

## Installation

### Method 1: Installing via the WordPress Admin Dashboard
1. Download the plugin `.zip` archive.
2. Log in to your WordPress admin area and navigate to **Plugins > Add New**.
3. Click the **Upload Plugin** button at the top of the page.
4. Select the downloaded `.zip` file from your device and click **Install Now**.
5. Once the installation is complete, click **Activate Plugin**.

### Method 2: Installing manually via FTP
1. Extract the downloaded `.zip` archive on your local computer.
2. Open your FTP client and connect to your web server.
3. Navigate to the `/wp-content/plugins/` directory of your WordPress installation.
4. Upload the extracted `search-analytics-insights` folder to that directory.
5. Go to **Plugins > Installed Plugins** in your WordPress dashboard, locate **Search Analytics & Insights**, and click **Activate**.

---

## Getting Started

Follow these steps to configure your settings, add search triggers, and start tracking search behavior:

1.  **Configure the Plugin**: Navigate to **Search Analytics > Search Settings** to define your default search form settings, styling choices, and retention limits.
2.  **Configure Searchable Post Types**: Under the **Search Sources** section, toggle whether to automatically include all public post types or manually select checkboxes to filter searches to posts, pages, or specific custom post types.
3.  **Configure AJAX Search**: Under **AJAX Search**, check **Enable AJAX search**. You can customize:
    *   *Minimum characters* required to trigger a search.
    *   *Maximum results* displayed in the live drop-down menu.
    *   *Debounce time* in milliseconds to delay queries while users type.
4.  **Add the Shortcode**: Copy and paste `[search_insights_form]` into any WordPress post, page, or widget layout to insert the search bar.
5.  **Insert the Gutenberg Block**: In the block editor, click the **+** (Block Inserter), search for **Search Analytics Search**, and insert it. Use the settings block sidebar to adjust the toggle trigger label and opening display modes.
6.  **Add the Widget**: Go to **Appearance > Widgets**, drag the **Search Analytics Search Widget** into your sidebar, configure its header title, icon size, label displays, and save.
7.  **View Analytics**: Go to **Search Analytics > Dashboard** for a quick overview or **Search Analytics > Analytics & Insights** to filter reports by dates, page types, and users.
8.  **Export Reports**: Go to **Search Analytics > Tools** and click **Download CSV Export** to save a full backup of all tracked terms.

---

## Shortcodes

Use these shortcodes to embed search forms and search statistics within your layouts:

### 1. `[search_insights_form]`
Displays a search form that renders as an AJAX live-search input (if enabled globally) or falls back to a standard WordPress search form.

*   **Supported Attributes**:
    *   `placeholder` *(string)*: Overrides the default input field placeholder (e.g. `placeholder="Find something..."`).
    *   `button_text` *(string)*: Overrides the submit button label (e.g. `button_text="Go"`).
    *   `show_button` *(bool)*: Forces showing or hiding the submit button (`true` or `false`).
    *   `form_style` *(string)*: Changes style structure. Supports `rounded`, `rectangle`, or `underlined`.
*   **Example**:
    ```text
    [search_insights_form placeholder="Search our site..." form_style="rounded" show_button="true"]
    ```

### 2. `[search_insights_popular]`
Lists the most searched terms recorded in the analytics database.

*   **Supported Attributes**:
    *   `limit` *(int)*: Total popular terms to output. Range: `1` to `50` (default: `5`).
    *   `title` *(string)*: Heading text displayed above the list (default: "Popular Searches").
    *   `show_count` *(bool)*: Toggle to display the total search count next to each word (`true` or `false`, default: `false`).
*   **Example**:
    ```text
    [search_insights_popular limit="10" title="Hot Topics" show_count="true"]
    ```

### 3. `[search_insights_trending]`
Lists search terms that are trending within the last 7 days.

*   **Supported Attributes**:
    *   `limit` *(int)*: Maximum trending terms to show. Range: `1` to `50` (default: `5`).
    *   `title` *(string)*: Heading text displayed above the list (default: "Trending Searches").
*   **Example**:
    ```text
    [search_insights_trending limit="5" title="Trending This Week"]
    ```

> [!WARNING]
> The old shortcode `[search_insights_ajax_form]` is deprecated in version 1.0.0. Please replace it with `[search_insights_form]` in your pages. It will forward queries to the unified form for backwards compatibility.

---

## Gutenberg Block

The **Search Analytics Search** block (`search-analytics-insights/search-widget`) allows content creators to insert a modern search toggle icon and input menu directly into block-based headers, footers, or post layouts.

### Block Settings
Configure these settings in the Block settings sidebar within the WordPress Block Editor:

| Setting | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| **Open Mode** | Dropdown | `dropdown` | Choose how the search form expands upon clicking the search icon. Options: `Dropdown`, `Modal`, `Slide Down`. |
| **Show Label** | Toggle (Boolean) | `true` | When toggled on, displays the text "Search" adjacent to the search toggle icon. When off, displays only the icon with proper screen-reader aria-labels. |

---

## Widget

The **Search Analytics Search Widget** provides classic sidebar support. Add it to any widget-ready container under **Appearance > Widgets**.

### Widget Options
*   **Widget Title**: The header title displayed inside the search panel wrapper (default: "Search").
*   **Icon Size**: The pixel diameter size of the search icon. Supports range `16` to `72` (default: `24`px).
*   **Open Mode**: Dictates opening animation behavior:
    *   `Dropdown`: Appears directly below the button.
    *   `Modal Popup`: Opens as a full-screen overlay with a close button.
    *   `Slide Down`: Expands downward from the top of the viewport.
*   **Show Label**: Checkbox to toggle showing the text "Search" next to the icon.

---

## Analytics Dashboard

The dashboard consists of two major segments built directly inside the WordPress administration interface:

### 1. Main Dashboard (`Dashboard`)
Accessible under **Search Analytics**. Offers quick health stats and recent snapshots:
*   **Overview Summary**: Display cards showing *Total Searches*, *Unique Searches*, and *No Results* counts across all tracked periods.
*   **Quick Actions**: Quick buttons to jump to the Analytics page, manage settings, read help files, or run a CSV log export.
*   **Top Searches (Overall)**: A table sorting the most frequent search terms across all history.
*   **Recent Search Activity**: A tabular live stream of the 5 most recent searches, showing search terms, dates, result counts, and origin templates. Includes a **Details** button that expands inline to reveal the exact URL, referrer page, page title, username, and source.

### 2. Analytics & Insights Dashboard (`Analytics & Insights`)
Accessible under **Search Analytics > Analytics & Insights**. Provides full-page filtering and reporting:
*   **Search Filters**: Expandable panel allowing queries by:
    *   Date limits (*From* / *To*).
    *   Search terms (*Keyword matching*).
    *   Post Types (*Posts, Pages, custom post types*).
    *   Page Titles and Page URLs.
    *   Usernames.
    *   *No Results Only* filter.
    *   *Per Page* limit selector (`10`, `20`, `50`, or `100` items).
*   **Overview Summary Cards**: Recalculates total searches, unique searches, and empty searches matching the active filter.
*   **Searches Per Day**: Displays daily search volumes to track search trends over time.
*   **Top Search Terms**: The 10 most popular keywords within the active filter scope.
*   **No Result Searches**: Displays the most requested terms that returned zero results, highlighting content gaps.
*   **Top Pages Where Users Search**: Lists page titles that generated the most search activity.
*   **Most Active Search Pages**: Lists page URLs where users execute searches most frequently.
*   **Searches by Post Type**: Insights into which post types (e.g. post, page, product) are searched.
*   **Aggregated Search Activity**: An grouped table aggregating identical search queries by term and user, showing query count, result counts, origin details, and dates.
*   **Recent Search Activity (Logs)**: Shows the latest 20 raw search transactions matching your filter.

---

## Search Settings

Settings are divided into five logical sections under **Search Analytics > Search Settings**:

### 1. Search Form
Manage default styles and copy for search inputs:
*   **Placeholder Text**: Default text displayed inside the search input before typing (default: `Search posts and pages...`).
*   **Button Text**: Label text shown on the submit button (default: `Search`).
*   **Show Button**: Toggle to show or hide the submit button (automatically hidden if AJAX search is enabled).
*   **Form Style**: Select default styling border behavior (`Rounded`, `Rectangle`, or `Underlined`).

### 2. AJAX Search
Enable and adjust the live search query handler:
*   **Enable AJAX Search**: Turn on the live dropdown query experience on the frontend.
*   **Minimum Characters**: The minimum length of input required before launching a live query (range: `1` to `10`, default: `2`).
*   **Maximum Results**: Limit the maximum results shown in the live search dropdown (range: `1` to `20`, default: `10`).
*   **Debounce Time**: Delay in milliseconds before firing the AJAX request to limit database server loads (range: `50` to `2000`, default: `300`).

### 3. Search Results
Manage result items inside the AJAX live dropdown:
*   **Show Featured Images**: Check to display post thumbnails next to search results.
*   **Show Post Type Label**: Display post type badges (e.g. *Post*, *Page*) beneath the result titles.
*   **No Results Message**: Message shown inside the dropdown when no matches are found (default: `No results found.`).

### 4. Search Sources
Select searchable content locations:
*   **Automatically load all public post types**: Set the indexer to scan all public post types globally.
*   **Allow selecting searchable post types**: Uncheck the automatic option to manually select specific public post types (e.g. posts, pages, custom post types) using checkboxes.

### 5. Analytics
Manage search tracking and retention parameters:
*   **Track logged-in users**: Capture search queries submitted by registered, signed-in users.
*   **Track guests**: Capture search queries submitted by anonymous visitors.
*   **Search retention period**: Automatic database maintenance window in days. Search logs older than this value are automatically pruned (range: `1` to `3650` days, default: `30`).

---

## CSV Export

The export tool generates a clean, downloadable spreadsheet containing all recorded tracking database rows. To ensure security, all fields starting with characters like `=`, `+`, `-`, or `@` are prefixed with a single quote to prevent spreadsheet formula injection.

The CSV includes the following 11 columns:

| Column Header | Source DB Field | Description |
| :--- | :--- | :--- |
| **ID** | `id` | Unique auto-increment primary key of the logged search event. |
| **Search Term** | `search_term` | The text query entered by the user. |
| **Searched At** | `searched_at` | Date and time when the search occurred (UTC format). |
| **Source** | `source` | Context of search origination (e.g., standard search, AJAX search, widget, block). |
| **Matched Post Types** | `matched_post_types` | Comma-separated list of post types that matched the query at that time. |
| **Result Count** | `result_count` | Total matches returned by the search engine. |
| **Username** | `user_id` / `WP_User` | Displays user name if logged-in user tracking was active; otherwise outputs "Guest". |
| **Page Title** | `page_title` | Resolved WordPress page title where the search was executed. |
| **Page URL** | `page_url` | The complete URL link where the search query started. |
| **Referrer** | `referrer` | HTTP Referrer header string recorded during the search. |
| **Page Type** | `page_type` | Internal WordPress page type context (e.g. home, single post, archive page). |

---

## Privacy

Search Analytics & Insights is designed with a strict, **privacy-first approach**:

*   **100% Self-Hosted**: All analytics, search queries, result numbers, page locations, and user mappings are stored locally in your website's WordPress database table.
*   **No Third-Party APIs**: The plugin does not call external tracking APIs, pixel hosts, or servers. Your site data belongs exclusively to you.
*   **Zero IP Address Storage**: Raw IP addresses are never logged or stored anywhere in the database tables, maintaining user anonymity.
*   **GDPR Compliant**: Keeps metrics internal without transferring user search history across borders or to advertising entities.

---

## Frequently Asked Questions

### 1. Does this plugin track personal user data or IP addresses?
No. The plugin is designed to be privacy-friendly. It does not collect or store raw IP addresses. It uses anonymous session tokens for page-flow analysis and can optionally record logged-in user IDs (if enabled) without transmitting data outside your server.

### 2. How do I configure search sources and post types?
Navigate to **Search Analytics > Search Settings** and click on the **Search Sources** section. You can check individual boxes for public post types (like posts, pages, or custom post types like products/events) or enable "Automatically load all public post types" to query all content automatically.

### 3. What is the difference between `[search_insights_form]` and the deprecated `[search_insights_ajax_form]`?
`[search_insights_form]` is a unified, smart shortcode. If AJAX live search is enabled in your settings, it renders as a live AJAX search input. If AJAX search is disabled, it automatically falls back to a standard WordPress search form. The old `[search_insights_ajax_form]` is deprecated but remains supported as an alias for compatibility.

### 4. How do I add a search form to a post, page, or sidebar widget area?
You can copy and paste the shortcode `[search_insights_form]` in any page text editor, search for the Gutenberg block named **Search Analytics Search** in the block library, or go to **Appearance > Widgets** to place the classic **Search Analytics Search Widget** in a widget container.

### 5. Does the AJAX live search display post featured images?
Yes. You can toggle this behavior by checking the box next to **Show featured images** under **Search Analytics > Search Settings > Search Results**.

### 6. Can I export search log records?
Yes. Navigate to **Search Analytics > Tools** and click the **Download CSV Export** button to download a spreadsheet containing all database tracking entries.

### 7. How does date range filtering work on the analytics dashboard?
Under **Search Analytics > Analytics & Insights**, you can expand the filter block, select "From" and "To" dates via calendar inputs, and click **Filter**. The overview summary metrics, daily charts, top keyword reports, and logs will recalculate to show only actions occurring inside that date range.

### 8. What happens when the Search Retention Period is reached?
The plugin performs automatic database cleanup and deletes search records older than the number of days configured under **Search Analytics > Search Settings > Analytics > Search retention period** (default is 30 days).

### 9. Does the plugin connect to external metrics servers?
No. This is a fully self-hosted plugin. All tracking routines execute within your WordPress hosting site and store metrics directly in your local WordPress MySQL/MariaDB database. No telemetry is collected by the author.

### 10. How can I empty all search log history?
Go to **Search Analytics > Tools**. In the **Clear Search Logs** card, click the **Permanently Delete Logs** button. This action is permanent and empty the database table immediately. It will not affect your search settings.

---

## Screenshots

1. **Dashboard Overview**: A summary of searches, top keywords, and recent search logs.
   *`[Placeholder: Dashboard Overview - screenshot-1.png]`*
2. **Analytics & Insights**: Complete deep-dive reporting panel with date range, keyword, post type, and page title filters.
   *`[Placeholder: Analytics & Insights - screenshot-2.png]`*
3. **Search Settings**: Centralized configuration tabs controlling form styles, AJAX limits, searchable sources, and retention policies.
   *`[Placeholder: Search Settings - screenshot-3.png]`*
4. **Search Widget**: The classic WordPress widget configuration menu under Appearance.
   *`[Placeholder: Search Widget Settings - screenshot-4.png]`*
5. **Gutenberg Block**: Inserting the custom search block trigger in the WordPress visual editor block layout.
   *`[Placeholder: Gutenberg Search Block - screenshot-5.png]`*
6. **CSV Export Page**: Tools section presenting CSV logs download, database clear controls, and factory setting resets.
   *`[Placeholder: CSV Export Tools Page - screenshot-6.png]`*

---

## Changelog

### 1.0.0
*   **New**: Added page tracking capabilities (logs page title, page URL, referrer, and template page types).
*   **New**: Added three new dashboard analytics widgets (Top Pages Where Users Search, Most Active Search Pages, and Searches by Post Type).
*   **New**: Integrated advanced filters on the search logs page (filter by page title, URL, username, and page type).
*   **Improvement**: Enhanced CSV export tool to output resolved page titles, page URLs, referrers, and page type headers with formula injection protection.
*   **Improvement**: Deprecated `[search_insights_ajax_form]` shortcode and introduced unified `[search_insights_form]` shortcode.

---

## License

This plugin is licensed under the GPLv2 or later.

Copyright (c) 2026 Vivek Prajapati.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
