=== Search Analytics & Insights ===
Contributors: Vivek Prajapati
Tags: search, analytics, insights, dashboard, statistics
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search Analytics & Insights tracks WordPress site searches and gives administrators a clear view of what visitors are searching for.

== Description ==

Search Analytics & Insights helps you understand search behavior on your WordPress site without collecting raw IP addresses or other unnecessary personal data.

The plugin provides:

* Search tracking for every WordPress search query.
* Top searches to show the most frequently searched terms.
* No result searches to help identify missing content.
* An analytics dashboard in wp-admin with search summaries and filters.
* A [search_insights_popular] shortcode for displaying popular search terms.
* A [search_insights_trending] shortcode for showing trending searches from the last 7 days.

It stores search term data, result counts, timestamps, optional logged-in user IDs, and anonymous session identifiers for analytics purposes.

== Installation ==

1. Upload the `search-analytics-insights` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the Search Analytics menu in the WordPress admin area.
4. Add shortcodes to pages, posts, or widgets as needed.

== Frequently Asked Questions ==

= Does this track personal information? =

The plugin is designed to be privacy-friendly and does not collect raw IP addresses. It may store a logged-in user ID when available and an anonymous session identifier for analytics grouping.

= Does it store IP addresses? =

No. Raw IP addresses are not stored.

= Does it work with custom themes? =

Yes. The search form shortcode uses standard WordPress search behavior and should work with most themes.

== Screenshots ==

1. Search analytics dashboard showing totals, top terms, and daily search activity.
2. Popular searches shortcode displayed on a front-end page.
3. Trending searches shortcode showing searches from the last 7 days.
4. Search analytics table with filters and pagination.

== Changelog ==

= 0.5.0 =
* Initial Release

== Upgrade Notice ==

= 0.5.0 =
Initial release of Search Analytics & Insights.
