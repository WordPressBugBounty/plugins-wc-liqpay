=== Payment Gateway for LiqPay for Woocommerce ===
Contributors: Komanda
Tags: LiqPay, liqpay, payment, gateway, Woocommerce,
Requires at least: 5.7.2
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 2.8.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Plugin for paying for products through the LiqPay service. Works in conjunction with the Woocommerce plugin

== Description ==

Payment for products of online stores (Woocommerce) through the LiqPay service. Works in conjunction with the Woocommerce plugin.


== Features ==
- Payment for any type of products
- Support for block themes
- Support for PPO (Fiscal Receipt Printing)
- Debugging in standard WooCommerce logs
- Configuration options:
  - Payment method name
  - Payment method description
  - Payment method icon
  - Language of LiqPay payment pages
  - Order status after payment
  - Payment destination
  - Redirect page in case of payment failure
  - Enable/disable PPO
  - Enable/disable WooCommerce debug log

Now you can use the filter:
```
add_filter('wc_liqpay_request_filter', 'modify_request');
function modify_request($request) {
    // Modify the $request array here
    $request['version'] = '3';
    return $request;
}
```

== Installation ==

Unzip the contents of the zip file to your site\'s plugins folder (wp-content/plugins/) using your favorite FTP program.
or install from the official Worpress.org repository
Activate the plugin on the \"Plugins\" page in the admin panel.
installation is completed.

After installing and activating the plugin, go to the admin panel in the left menu in WooCommerce -> Settings (Settings) -> Payments (Payments) and activate LiqPay, then go into it and write down public_key and private_key - you will receive them when registering in the liqpay.ua system and adding your site to it.

== Screenshots ==

1. Woocommerce payment methods settings page
2. Plugin settings page
3. Payment methods in block themes
4. Single product RRO settings
5. Variable product RRO settings

== Changelog ==
= 1.0 = 
* First release
= 1.1 =
Automatic redirection to Liqpay page
Added a field for redirection when the payment has not been made
Ukrainian translation added
= 1.2 =
Small changes
= 1.3 =
Changes php version
= 1.4 =
Tested wordpress 6.3.1
= 1.5 =
Updated data transfer method. Tested wordpress 6.4.3
= 1.6 =
Added screenshots
= 1.7 =
Bugs fixed
= 1.8 =
Tested wordpress 6.5
= 1.9 =
Added filter "wc_liqpay_request_filter" to query array before sending data to liqpay.
= 1.10 =
Bugs fixed:
removed admin css/js plugins files
fixed duplicated origin column value
= 1.11 =
Tested wordpress 6.7.1
Сode refactoring
Add 'rro_info'
Bugs fixed:
link to thank you page after successful payment
the order status after successful payment
localisation and translations
= 1.12 =
Bugs fixed:
Removing quotes in 'rro_info' array for 'price' and 'cost' values
= 1.13 =
'rro_info' string 'email' to array
= 1.14 =
Add metabox 'rro_id'
= 2.0 =
Added the ability to enable/disable data submission for PPO
Fixed the processing of "refunds" in the WooCommerce plugin
Fixed translation errors and text repetition
Enabled logs in debug mode
Fixed wpcs validation
Fixed the plugin's incompatibility with the current version of WooCommerce 9 (block theme support)
= 2.3 =
Added quick navigation to settings
Added logging to WooCommerce logs
= 2.6 =
Added order object to filter "wc_liqpay_request_filter"
Added a short description of the RRO field
= 2.7 =
Update Wordpress 6.8.1
= 2.8 =
Sending technical information with consent
RRO for variable products
Updated screenshots

== Upgrade Notice ==

= 1.1 =
Automatic redirection to Liqpay page
Added a field for redirection when the payment has not been made
Ukrainian translation added
= 1.5 =
This version fixes a bug related to statuses. Updated data transfer method. Please update.
= 1.6 =
Added screenshots
= 1.7 =
Bugs fixed
= 1.8 =
Tested wordpress 6.5
= 1.9 =
Added filter to query array
= 1.10 =
Small bugs fixed
= 1.11 =
Сode refactoring
Add 'rro_info'
Bugs fixed
= 1.12 =
Bugs fixed:
Removing quotes in 'rro_info' array for 'price' and 'cost' values
= 1.13 =
'rro_info' string 'email' to array
= 2.0 =
Added the ability to enable/disable data submission for PPO
Fixed the processing of "refunds" in the WooCommerce plugin
Fixed translation errors and text repetition
Enabled logs in debug mode
Fixed wpcs validation
Fixed the plugin's incompatibility with the current version of WooCommerce 9 (block theme support)
= 2.3 =
Added quick navigation to settings
Added logging to WooCommerce logs
= 2.6 =
Added order object to filter "wc_liqpay_request_filter"
Added a short description of the RRO field
= 2.7 =
Update Wordpress 6.8.1
= 2.8 =
Sending technical information with consent
RRO for variable products
Updated screenshots

== Translations ==

* English - default, always included
* Ukraine: always with you!