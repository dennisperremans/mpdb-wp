=== Nuclia search for WP ===
Contributors: Kalyx
Tags: Search, Nuclia, relevant search, search highlight, faceted search, ajax search, better search, custom search
Requires at least: 5.6
Tested up to: 6.2.2
Requires PHP: 7.2
Stable tag: 1.1.1
License: GNU General Public License v2.0, MIT License

Optimize your WordPress search with Nuclia's AI powered search API.

== Description ==

Improve search on your site.

= Features =
* Push your content to NucliaDB for indexing
* Nuclia searchbox widget
* Nuclia searchbox shortcode

This plugin requires a Nuclia account. You can sign up for free at [Nuclia](https://nuclia.com) for a trial.

Only published posts, not private, are indexed. If this status change, the post will be unindexed.

= About Nuclia =

Nuclia is an easy-to-use & low-code API enabling developers to build AI-powered search engines for any data and any data-source in minutes — while not having to worry about scalability, data indexing and the high learning curves and implementation of complex search systems.

= Links =
* [Nuclia](https://nuclia.com)
* [Development] (https://github.com/kalyx/wp-nucliadb)

== Installation ==


From your WordPress dashboard:

1. **Visit** Plugins > Add New
2. **Search** for "Nuclia search for WP"
3. **Activate** Nuclia search for WP from your Plugins page
4. **Click** on the new menu item "Nuclia Search" and enter your API keys and select the post types you want to index
5. You have now buttons to index your post types


= What are the minimum requirements? =

* Requires WordPress 5.6+
* PHP version 7.2 or greater (PHP 7.4 is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)
* cURL PHP extension
* mbstring PHP extension
* OpenSSL greater than 1.0.1


== Frequently Asked Questions ==

= Can I put more than one Nuclia searchbox in the same page =

Yes, you can, but only the first one in the DOM will work.

== Screenshots ==

1. Plugin settings page.
2. Widget settings.
3. Searchbox in content with shortcode.

== Changelog ==

= 1.1.1 =
* Fix : Error notices when saving credentials

= 1.1.0 =
* Enhancement: Added a button for each post types in the settings page to index all unindexed posts ( or attachment )

= 1.0.0 =
* Initial release.
