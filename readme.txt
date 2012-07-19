=== eShop Order Emailer ===
Contributors: paulswebsolutions
Donate link: http://csv-imp.paulswebsolutions.com
Tags: eshop, csv, orders, email, fulfillment
Requires at least: 3.3
Tested up to: 3.4.1
Stable tag: 2.1.0

Email your successful eShop orders to one or more email addresses daily for unlimited suppliers.

== Description ==

The purpose of the plugin is to enable email notification of one or more fulfillment centers automatically. You can have as many suppliers as you have products and provide each with all the information needed to fulfill an order. In addition to the fulfillment emails that will be sent out, there is also a summary report which you can mail to yourself to keep tabs on what orders have come in and where they were sent.

As of version 2.1.0, plugin now has 'Instant Mode' for those who prefer speed over efficiency.

== Installation ==

1. Either use the built-in plugin installer, or download the zip and extract to your 'wp-content/plugins' folder.
2. Activate the plugin in Plugins > Installed Plugins
2. Open the 'Order Emailer' main menu on the left side of your Wordpress dashboard
3. Click on 'Credits/Instructions'

== Frequently Asked Questions ==

No FAQs yet.

== Screenshots ==

No screenshots available.

== Changelog ==

= 1.1 =
* Compatible up to Wordpress 3.1 and eShop 6.2.6

= 2.0 =
* Compatible with Wordpress 3.3+ and eShop 6.2.11
* Major upgrade of codebase and functionality

= 2.0.1 =
* Minor bugfix to stop report fields exporting blank

= 2.1.0 =
* Added 'Instant Mode': Emails will be sent as orders become active (via gateways or when the order is manually changed to active in the orders screen)

== Upgrade Notice ==

Every effort has been made to ensure a smooth upgrade process but please make sure you backup your database before installing anyway.

IMPORTANT: 

Upon activation of the plugin a CSV file will be emailed to the Wordpress admin email address containing all orders up to that point in time.  This is to collect previous orders and record them so that they won't be sent again and risk causing repeat orders.  In addition to this precaution, I also advise you to always include the 'edited' field in reports so that there is always a date associated with each order.  The edited field is only updated when the order is changed some way, so your fulfillment center/s should notice if orders from months or years ago are being received.  If you're expecially concerned, you could even give the column a custom heading to draw attention to it.
