<?php
/*
Plugin Name: WooCommerce Show Attributes
Plugin URI: https://isabelcastillo.com/docs/woocommerce-show-attributes
Description: Show WooCommerce custom product attributes on the Product, Shop and Cart pages, admin Order Details page and emails.
Version: 1.6.4
Author: Isabel Castillo
Author URI: https://isabelcastillo.com
License: GPL2
Text Domain: woocommerce-show-attributes
Domain Path: languages

Copyright 2014-2018 Isabel Castillo

WooCommerce Show Attributes is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

WooCommerce Show Attributes is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

if ( ! defined( 'ABSPATH' ) ) exit;
class WooCommerce_Show_Attributes {

	private static ?WooCommerce_Show_Attributes $instance;
	private bool $visible_in_cart;
	private string $display_position_shop;

	private Attribute_HTML $attribute_html;
	private Product_Attributes $product_attributes;

	public static function get_instance(): WooCommerce_Show_Attributes
	{
		if ( self::$instance == null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {

		require_once plugin_dir_path( __FILE__ ) . 'settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-attribute-html.php';

		$options = get_options([
			'show_attributes_product',
			'show_attributes_hide_labels',
			'show_attributes_remove_semicolon',
			'show_attributes_on_cart',
			'show_attributes_on_shop'
		]);

		$this->visible_in_cart  = $options['show_attributes_on_cart'] === 'yes';
		$this->display_position_shop = $options['show_attributes_on_shop'];

		$hide_labels = $options['show_attributes_hide_labels'] === 'yes';
		$colon = $options['show_attributes_remove_semicolon'] === 'yes' ? ' ' : ': ';

		$this->attribute_html = new Attribute_HTML($hide_labels, $colon);
		$this->product_attributes = new Product_attributes();

		add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_attributes_on_product_page'), 25 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'show_attributes_on_cart'), 10, 3 );
		add_filter( 'woocommerce_get_settings_products', 'add_settings', 10, 2 );
		add_filter( 'woocommerce_get_sections_products', 'add_section' );
		add_action( 'woocommerce_grouped_product_list_before_price', array( $this, 'attributes_grouped_product_html' ) );

		if ( $this->display_position_shop == 'above_price' ) {
			add_action ( 'woocommerce_after_shop_loop_item_title', array( $this, 'show_attributes_on_shop'), 4 );
		} elseif ( $this->display_position_shop == 'above_add2cart' ) {
			add_action ( 'woocommerce_after_shop_loop_item', array( $this, 'show_attributes_on_shop'), 4 );
		}

		if( ! is_admin()) {
			wp_enqueue_style('clypper-show-attributes', plugin_dir_url(__FILE__) . 'assets/css/clypper-show-attributes.css');
		}
	}


	/**
	 * Show product attributes on the product page.
	 *
	 * Show product attributes above the Add to Cart button on the single product page
	 * and on the Parent of Grouped products.
	 */
	public function display_product_attributes_on_product_page(): void
	{
		global $product;

		$attributes = $this->product_attributes->get_attributes($product);

		echo wp_kses_post( $this->attribute_html->product_attributes_html( $attributes ) );
	}

	/**
	 * Show product attributes on the Cart page.
	 */
	public function show_attributes_on_cart($name, $cart_item, $cart_item_key) {
		if (!$this->visible_in_cart) {
			return $name;
		}

		$product = $cart_item['data'];
		$attributes = $this->product_attributes->get_attributes($product);

		$attributes = wp_kses_post(
			$this->attribute_html->product_attributes_html(
				$attributes
			)
		);

		return "$name<br />$attributes";
	}


	/**
	 * Show product attributes on the child products of a Grouped Product page.
	 *
	 * @param WC_Product $product , the product object
	 */
	public function attributes_grouped_product_html( WC_Product $product ): void {
		$attributes = $this->product_attributes->get_attributes($product);

        echo $this->attribute_html->attributes_grouped_product_html($attributes);
    }

	/**
	 * Show the attributes on the main shop page.
	 */
	public function show_attributes_on_shop(): void {
		global $product;

        $attributes = $this->product_attributes->get_attributes($product);
        $attribute_html = $this->attribute_html->product_attributes_html( $attributes );

		echo wp_kses_post( $attribute_html );
	}

	static function install(): void {
		$settings = show_attributes_get_all_settings();
		foreach ( $settings as $option ) {
			if ( ! isset($option['default']) || $option['default'] === '' ) {
				continue;
			}

			$db_option = get_option( $option['id'], false );

			if ( $db_option === false ) {
				update_option( $option['id'], $option['default'] );
			}
		}
	}

}

// only if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	$WooCommerce_Show_Attributes = WooCommerce_Show_Attributes::get_instance();
	register_activation_hook(__FILE__, array( $WooCommerce_Show_Attributes, 'install' ) );
}
