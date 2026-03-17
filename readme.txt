=== WooCommerce Hook Manager ===
Contributors: prem
Tags: woocommerce, hooks, developer-tools, actions, filters
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage WooCommerce hooks visually with a premium, modern interface. Inspect callbacks, change priorities, add HTML wrappers, and inject custom content directly from your WordPress admin.

== Description ==

**WooCommerce Hook Manager** is a powerful developer tool designed to simplify the customization of WooCommerce stores. Instead of digging through template files or writing custom PHP to modify hook priorities and outputs, this plugin provides a clean, visual interface to manage everything.

Built with a premium, responsive design, it allows you to:

*   **Hook Inspector**: View every callback registered to major WooCommerce hooks, including their priority, arguments, and source file/plugin.
*   **Change Priority**: Easily reorder existing callbacks by changing their priority.
*   **Disable Callbacks**: Remove unwanted WooCommerce or third-party callbacks from any hook.
*   **Add HTML Wrappers**: Wrap existing hook outputs with custom HTML tags, classes, and attributes.
*   **Insert Custom Content**: Inject your own HTML or text into any WooCommerce hook location.
*   **Insert Shortcodes**: Easily place shortcodes into specific hook locations.
*   **Debug Overlay**: Enable a visual layout on your storefront that shows exactly where each WooCommerce hook is firing.
*   **Export/Import**: Generate PHP configuration code for your rules to move them between environments.

== Installation ==

1. Upload the `woocommerce-hook-manager` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the manager via the 'Woo Hook Manager' menu in your admin sidebar.

== Frequently Asked Questions ==

= Does this require WooCommerce? =
Yes, this plugin is specifically designed to work with WooCommerce hooks and will display a notice if WooCommerce is inactive.

= Is it safe to use on production? =
Yes. While it is a developer tool, all changes are saved as options and applied safely via standard WordPress hook filters.

== Screenshots ==

1. The Hook Manager dashboard showing active rules.
2. The Hook Inspector viewing registered callbacks.
3. Adding a new rule with the premium card selector.

== Changelog ==

= 1.0.0 =
* Initial release.
* Premium Blue UI theme.
* Hook Inspector, Manager, and Shortcode support.
