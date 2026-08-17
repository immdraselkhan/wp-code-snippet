=== WP Code Snippet ===
Contributors: yourbrand
Tags: code snippets, functions.php, custom code, css, javascript
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.4.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add PHP, HTML, CSS and JS snippets with conditional logic, without editing functions.php or your theme files.

== Description ==

WP Code Snippet lets you manage custom code the safe way:

* **Multiple code types** — PHP, HTML, CSS, and JavaScript in one place.
* **Flexible locations** — run PHP everywhere (like functions.php), frontend-only, admin-only, or print HTML/CSS/JS in the header, footer, admin screens, login page, or via a shortcode.
* **Conditional logic** — target specific pages/post types, devices, URLs, or logged-in state and user role — with include or exclude scope.
* **Safe by design** — PHP is syntax-checked before it can go live, and Safe Mode auto-deactivates any snippet that throws a fatal error at runtime so your site never stays down.
* **Modern admin UI** — a clean, card-based interface built on a small reusable design system, so future plugins from the same author look and feel identical.

= Why a custom table instead of posts? =

Snippets are stored in a dedicated `wp_wcs_snippets` table rather than as a custom post type. This keeps the `wp_posts` table clean and queries fast even with hundreds of snippets.

== Installation ==

1. Upload the `wp-code-snippet` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Code Snippets** in the admin menu to add your first snippet.

== Frequently Asked Questions ==

= Will a broken PHP snippet crash my site? =

No. Every PHP snippet is linted (`php -l`, or a fallback checker if shell access is unavailable) before it's allowed to be activated. If a snippet still errors at runtime — for example, calling an undefined function added by a theme update — Safe Mode catches the fatal error and automatically deactivates that one snippet, logging the error message on the snippet's row.

= Do I need to add `<?php` tags in the code editor? =

No, for PHP snippets just write the PHP body — the tag is added automatically.

= Where do CSS/JS/HTML snippets get printed? =

Wherever you choose on the Location tab: site header (`wp_head`), site footer (`wp_footer`), admin header/footer, the login page, or as a `[shortcode]` you place manually.

== Changelog ==

= 1.4.0 =
* Rebuilt the admin interface with native WordPress Block Editor components.
* Added React-based AJAX navigation for list, editor and settings screens.
* Uses native Button, Panel, TextControl, TextareaControl, SelectControl, RadioControl, ToggleControl, CheckboxControl, Notice, Modal, Spinner and Snackbar components.
* Retained CodeMirror and live PHP/HTML/CSS/JavaScript validation.
* Simplified custom CSS to layout-only rules so WordPress controls the visual system.


= 1.3.1 =
* Combined Location and Conditional Logic into Placement & Logic.
* Added grouped advanced conditions and WooCommerce-aware rules.
* Added context-aware late evaluation for page/product PHP conditions.
* Replaced browser delete confirms with an in-plugin confirmation modal.
* Removed edit-screen execution statistics.
* Compact single-line toast and refined back button/location cards.


= 1.2.0 =
* Fixed AJAX saves/settings being intercepted by the no-JS admin_init fallback, which caused successful saves to return HTML with HTTP 200 instead of JSON.

= 1.0.0 =
* Initial release.

= 1.4.6 =
* Improved selected placement alignment and added checkmark status.
* Normalized condition value field sizing.
* Package folder restored to wp-code-snippet so WordPress recognizes updates as replacements.
