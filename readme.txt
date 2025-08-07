=== QRIS Unique Code for WooCommerce ===
Contributors: (your-wp-username)
Tags: woocommerce, payment gateway, qris, indonesia, unique code, macrodroid, webhook
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.5.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An innovative QRIS payment gateway for WooCommerce with unique amount validation, automatic confirmation via webhook, and Android connection monitoring.

== Description ==
The QRIS Unique Code for WooCommerce plugin provides an innovative QRIS payment method without requiring expensive, direct API integrations with banks or payment gateways.

How Does It Work?
Unique Code: When a customer checks out, the plugin adds a unique amount (e.g., Rp 523) to the order total, making the final transfer amount unique (e.g., Rp 100,523).
Dynamic QR: The plugin displays a QR Code that has been dynamically modified with this unique amount.
Android Automation: With the help of the MacroDroid app on your Android device, every incoming payment notification from your acquirer's app (m-banking, GoBiz, etc.) is automatically read.
Secure Webhook: MacroDroid extracts the amount from the notification and sends it to your WooCommerce store via a webhook secured with an API Key.
Automatic Confirmation: If the amount matches a pending order, the order status is automatically changed to "Processing" or "Completed".

This plugin also features a Heartbeat system to monitor the connection between your Android device and your website, sending an email notification if the connection is lost.

Key Features:
Payment validation based on a unique amount.
Dynamic QR Code generation with the unique amount.
Secure webhook with API Key validation.
User-friendly onboarding wizard for easy initial setup.
Full automation using MacroDroid (a pre-configured macro file is provided).
Connection monitoring system (Heartbeat) with email notifications.
Dashboard widget for the last connection status.
Full customization for the payment page layout.
Support for fixed or percentage-based additional fees.
QR Code scanner on the admin settings page for easy setup.

== Installation ==

Upload the qris-kode-unik folder to the /wp-content/plugins/ directory.
Activate the plugin through the 'Plugins' menu in WordPress.
You will be automatically redirected to our setup wizard to guide you through the initial configuration.
If you skip the wizard, you can configure the plugin by going to WooCommerce > Settings > Payments and clicking "Manage" on QRIS (Unique Code).

== Frequently Asked Questions ==

= Do I need to pay for an API service to use this plugin? =
No. This is the main advantage of this plugin. You do not need a paid API integration. With just an Android device with your acquirer's app and MacroDroid installed, you can have a fully automated payment confirmation system.

= What Android apps do I need? =
You will need the <a href="https://play.google.com/store/apps/details?id=com.arlosoft.macrodroid" target="_blank">MacroDroid</a> app and your acquirer's app (e.g., your m-banking app, GoBiz, OVO Merchant, etc., that can display incoming transaction notifications).

= How do I set up MacroDroid? =
It's very easy. The setup wizard will guide you, and on the plugin's settings page, we provide complete instructions and a button to download the QRISNotify.macro file. This file is automatically configured with your Webhook URL, API Key, and your acquirer's app Package Name. You just need to import it into MacroDroid and grant the necessary permissions.

= Is this secure? =
Yes. The communication between your Android device and your website is secured using a unique API Key that only you know. All webhook requests without a valid key will be rejected.

== Changelog ==

= 1.5.0 =
NEW: Added a step-by-step onboarding setup wizard for a seamless first-time activation experience.
NEW: Added a dismissible admin notice to encourage plugin ratings after successful configuration.
TWEAK: Improved the overall user experience for new users.

= 1.4.0 =
FIX: Bundled third-party JavaScript libraries (jsQR, qrcode.js) locally to comply with WordPress.org standards.
FIX: Localized JavaScript strings to make them translatable.
NEW: Added a comprehensive English readme.txt file.

= 1.3.1 =
FIX: Dynamic logic for package_name in the downloadable macro file.

= 1.3.0 =
NEW: Added a Heartbeat system to monitor the Android connection.
NEW: Added a dashboard widget for Heartbeat status.
NEW: Provided a dynamically configured QRISNotify.macro file for download.
NEW: Added complete setup instructions for MacroDroid.

= 1.2.0 =
SECURITY: Hardened the webhook security with a static API Key validation.
NEW: Added "copy" and "regenerate" features for the API Key on the settings page.

= 1.1.0 =
NEW: Added an automatic payment status check (AJAX Polling) on the customer's payment page.
NEW: Added custom action options (redirect/message) for successful or timed-out payments.

= 1.0.0 =
Initial release.