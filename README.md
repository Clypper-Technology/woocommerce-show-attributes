# WooCommerce-Show-Attributes

=== WooCommerce Show Attributes ===
Contributors: isabel104, Casper Holten.
Tags: product attributes, woocommerce product attributes, woocommerce attributes, woocommerce, attributes
Stable tag: 1.0
License: GNU Version 2 or Any Later Venrsion
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show WooCommerce custom product attributes on the Product, Shop, and Cart pages, admin Order Details page and emails.

== Description ==

This is an extension for WooCommerce that will show your custom product attributes on the single product page above the "Add to cart" button instead of in the "Additional Information" tab. Your product attributes will also be displayed at several other locations, including on order emails. See the full list, below. This plugin does NOT affect nor include attributes which are used for Variations.

Your product attributes will **also** be displayed at these locations (with option to turn them off):

* Cart page
* View Order page on front end for customers
* Emails that goes to the customer, including:
    * Receipt (Order Processing) email that goes to the customer
    * Order Complete email that goes to the customer
    * Customer Invoice email
* New Order email that goes to the administrator
* Admin Order Details page on the back end, under Order Items
* Grouped product page
* Shop page (including product category and tag archives) (Off by default. You must enable this option on the settings page.)

See the [full documentation](https://isabelcastillo.com/docs/woocommerce-show-attributes).

**Languages**

If you want to translate this plugin to your language, you can do so easily [on this page](https://translate.wordpress.org/projects/wp-plugins/woocommerce-show-attributes). After you submit a translation, contact me in the support forum to request approval as a Translation Editor.

**Disclaimer**

I am not affiliated with WooCommerce or Automattic. I provide this plugin as a free service to the WP community because of the many requests that I received for it.


== Installation ==

**Install and Activate**

1. Install and activate the plugin in your WordPress dashboard by going to Plugins –> Add New.
2. Search for “WooCommerce Show Attributes” to find the plugin.
3. When you see WooCommerce Show Attributes, click “Install Now” to install the plugin.
4. Click “Activate” to activate the plugin.

**Setup**

1.  After you activate the plugin, your custom product attributes will automatically be shown at certain locations. ([see which locations](https://isabelcastillo.com/docs/woocommerce-show-attributes#docs-where)).

2.  If you want to show the attributes on the single product page, do this: For each attribute that you want to display, you must check the box for **“Visible on the product page.”** This is a WooCommerce native option and is found on the Edit Product page (for each product), under the individual attribute settings. If you uncheck that box, the attribute will not be shown for that product.

3.  Optional settings are at WooCommerce Settings -> Products tab. Click on "Show Attributes" to see this plugin's options.


== Frequently Asked Questions ==

= How do I show only some attributes, while not showing others? =

For each attribute that you want to display, you must check the box for “Visible on the product page.” So, you can use that setting to show some attributes. Leave the box unchecked for the attributes that you do not want to show.

= Why are my custom attributes NOT showing up? =

For each attribute that you want to display, you must check the box for “Visible on the product page.” If you leave that box unchecked, that attribute will not be shown by this plugin.

= Can I show the product weight and/or dimensions above the Add to Cart button? =

Yes, since version 1.4.0. See this plugin's settings page to enable this.

= How do I hide the attribute labels and only show the values? =

Go to WooCommerce Settings -> Product tab, under "Product Data". Check the box for **"Hide the Labels When Showing Product Attributes"**. Click "Save changes".

= How do I remove the list bullets from the attributes on the single product page? =

Go to WooCommerce Settings -> Product tab, under "Product Data". Check the box for **"Show Attributes in a span Element"**. Click "Save changes".

= How can I style the attributes? =

This plugin adds several CSS selectors so that you can style the output by adding your own CSS.

On the "single product page", the attributes are in an unordered list with the CSS class "custom-attributes". Each list item has two CSS classes: its attribute name and its value. Within each list item, each attribute label has the CSS class "attribute-label", and each attribute value has the CSS class "attribute-value".

On the Cart page, View Order page, admin Edit Order page, and in the emails, the attributes are wrapped in a 'span' element with the CSS class "custom-attributes". Each attribute name and value pair is wrapped in a 'span' which has two CSS classes: its attribute name and its value. Within this span, each attribute label has the CSS class "attribute-label", and each attribute value has the CSS class "attribute-value".


= How do I remove the extra left-margin space from the attributes on the single product page? =

Add this CSS:

`ul.custom-attributes {
  	margin-left: 0;
}`

= How do I make all the attribute labels bold? =

Add this CSS:

`.custom-attributes .attribute-label {
  font-weight: bold;
}`


= How do I make all the labels and values italic? =

Add this CSS:

`.custom-attributes {
  font-style:italic
}`