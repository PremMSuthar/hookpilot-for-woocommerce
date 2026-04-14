# Hookpilot for WooCommerce

**Hookpilot for WooCommerce** is an advanced developer utility that empowers you to visually map, manage, and attach custom functionalities to WooCommerce hooks directly from the WordPress front-end and admin panel.

No more guessing hook names or digging through WooCommerce templates! Simply enable the Debug Overlay, click on a visual hook marker anywhere on your site, and instantly attach your custom callbacks, shortcodes, or wrapper HTML.

---

## 🌟 Key Features

* **Visual Hook Inspector (Frontend Debug Overlay):** See every available WooCommerce hook right where it executes on your live site. Hooks are clearly displayed as dashed boxes in areas (like the cart or checkout) and as inline pills for single items (like product cards).
* **Click-to-Add Rules:** Click any hook marker in the debug overlay to instantly open a modal and create a new rule for that specific location.
* **Flexible Rule Types:**
  * **Custom Content:** Insert HTML, text, or PHP output directly into the hook.
  * **Shortcodes:** Execute WordPress shortcodes at any hook location.
  * **Wrapper Elements:** Wrap hook contents in custom HTML tags (e.g., `div`, `span`, `section`) with custom CSS classes and attributes.
  * **Prioritize / Reorder:** Change the priority of existing hook callbacks to move elements around without writing code.
  * **Disable Adjustments:** Prevent specific hook callbacks from running altogether.
* **Comprehensive Hook Manager:** A central admin dashboard (`Woo Hooks > Hook Manager`) to review, edit, toggle, or delete all your custom rules in a beautiful, organized table.
* **Import/Export Engine:** Easily migrate your hook configurations between staging and production environments gracefully.

---

## 🚀 How to Use

### 1. The Frontend Debug Overlay
To start visualizing hooks, toggle the **"WHM Debug"** switch located in your WordPress admin bar while viewing the frontend of your site.
* **Area Hooks** (e.g., `woocommerce_before_main_content`) appear as prominent horizontal dashed boxes.
* **Inline Hooks** (e.g., `woocommerce_before_shop_loop_item`) appear as sleek borders inside specific loops or grid items.
* **The Sidebar Panel:** Opening the inspector also revealing a floating sidebar that catalogs all active hooks mapped on the current page for quick reference.

### 2. Creating a Rule
1. Turn on the Debug Overlay.
2. Find the hook you want to modify (e.g., `woocommerce_after_single_product_summary`).
3. Click the hook's marker.
4. In the modal, configure your action:
   * **Rule Title:** A friendly name for your reference.
   * **Action Type:** Choose Custom Content, Shortcode, Priority Change, Disable, or Wrapper.
   * **Priority:** Set the execution priority (lower numbers run earlier).
5. Click **Save Rule**.



## ⚙️ Administration & Settings

Access the plugin settings via the WordPress Admin Sidebar under **Woo Hooks**.

* **Inspector:** Central toggle for the Frontend Debug Overlay.
* **Hook Manager:** A tabular view of all active and disabled rules. Use this screen to quickly edit, toggle statuses, or delete rules.
* **Import / Export:** Easily backup your custom hook configurations by copying the JSON output, or migrate rules from another site by pasting JSON into the Import textarea.
* **Settings:** Control deep plugin behaviors, including uninstall cleanup options.

---

## 🛠 Developer Notes

### Page Detection
The Frontend Debug Overlay uses strict page detection (`is_woocommerce()`, CSS body classes like `.woocommerce-page`) to ensure it only activates on relevant WooCommerce shop, product, cart, and checkout templates—preventing layout pollution on standard blog posts or pages.

### Under the Hood
This plugin does not override WooCommerce templates. It uses standard WordPress core functions (`add_action()`, `remove_action()`) dynamically mapping your saved settings into the WordPress lifecycle. This ensures total compatibility with WooCommerce updates, heavily customized themes, and performance-caching mechanisms.

---

## 📦 Requirements
* WordPress 6.9+
* WooCommerce 7.0+
* PHP 7.4+

---

## 🔮 Future Scope
* **Shortcode Generator (`[whm_hook]`)**: A future update will allow you to generate shortcodes to manually drop specific WooCommerce hooks (like the Add to Cart button or product summaries) into page builders (Elementor, Divi) or Gutenberg blocks on any page or post. This will include special `product_id` context mocking to safely render WooCommerce data outside of product pages!

---

*Built with ❤️ for WooCommerce Developers and Store Owners.*
