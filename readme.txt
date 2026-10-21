=== WooCommerce AI Agent ===
Contributors: miamen16
Tags: woocommerce, chatbot, customer support, demo
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later

A local WooCommerce assistant demo. No OpenAI API key is required.

== Features ==
* Arabic storefront chat via [wc_ai_agent] shortcode.
* Product search using published WooCommerce products.
* Local greeting handling, including "welcome", without triggering product search.
* Product price and stock display.
* FAQ and store-policy text configured in WooCommerce > AI Agent Demo.
* Order lookup only when order ID and exact billing email are supplied separately.
* Admin-only sales summary and low-stock report.
* Simple per-IP request throttling.

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/woocommerce-ai-agent or zip it and upload through Plugins > Add New > Upload Plugin.
2. Activate WooCommerce and this plugin.
3. Add [wc_ai_agent] to a page.
4. Configure FAQ and policy text under WooCommerce > AI Agent Demo.

== Demo limitations ==
Responses use deterministic local PHP rules, not an LLM. Order lookup requires order ID and billing email in separate request fields; the current chat UI does not yet collect these fields, so order tracking prompts the customer to use a future dedicated order form. Reports are available only to users with manage_woocommerce capability. Test on staging before production use.

== Changelog ==
= 0.1.0 =
* Initial local demo foundation.
