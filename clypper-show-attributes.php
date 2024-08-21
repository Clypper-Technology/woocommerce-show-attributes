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
    private bool $show_weight;
    private bool $show_dimensions;
    private bool $skip_attributes;
    private bool $span_option;
    private bool $terms_are_links;
    private bool $visible_in_cart;
    private bool $show_weight_cart;
    private bool $show_dimensions_cart;
    private bool $skip_attributes_cart;
    private string $unit;
    private string $display_position_shop;

    private Attribute_HTML_generator $attribute_html;

    public static function get_instance(): WooCommerce_Show_Attributes
    {
        if ( self::$instance == null ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {

        require_once plugin_dir_path( __FILE__ ) . 'settings.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-attribute-html-generator.php';

        $options = get_options([
            'show_attributes_product_weight',
            'show_attributes_dimensions_product',
            'show_attributes_product',
            'show_attributes_hide_labels',
            'show_attributes_show_span',
            'show_attributes_remove_semicolon',
            'woocommerce_weight_unit',
            'show_attributes_as_links',
            'show_attributes_cart',
            'show_attributes_weight_cart',
            'show_attributes_dimensions_cart',
            'show_attributes_on_cart',
            'show_attributes_on_shop'
        ]);

        $this->show_weight = $options['show_attributes_product_weight'] === 'yes';
        $this->show_dimensions = $options['show_attributes_dimensions_product'] === 'yes';
        $this->skip_attributes = $options['show_attributes_product'] === 'no';
        $this->span_option = $options['show_attributes_show_span'] === 'yes';
        $this->unit = empty( $options['woocommerce_weight_unit'] ) ? '' : $options['woocommerce_weight_unit'];
        $this->terms_are_links = $options['show_attributes_as_links'] === 'yes';
        $this->visible_in_cart  = $options['show_attributes_cart'] === 'yes';
        $this->show_weight_cart = $options['show_attributes_weight_cart'] === 'yes';
        $this->show_dimensions_cart = $options['show_attributes_dimensions_cart'] === 'yes';
        $this->skip_attributes_cart = $options['show_attributes_on_cart'] === 'yes';
        $this->display_position_shop = $options['show_attributes_on_shop'];

        $hide_labels = $options['show_attributes_hide_labels'] === 'yes';
        $colon = $options['show_attributes_remove_semicolon'] === 'yes' ? ' ' : ': ';
        $display_element = $this->span_option ? 'span' : 'li';


        $this->attribute_html = new Attribute_HTML_generator($hide_labels, $display_element, $colon);


        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_attributes_on_product_page'), 25 );
        add_filter( 'woocommerce_product_tabs', array( $this, 'additional_info_tab' ), 98 );
        add_filter( 'woocommerce_cart_item_name', array( $this, 'show_attributes_on_cart'), 10, 3 );
        add_filter( 'woocommerce_get_settings_products', 'add_settings', 10, 2 );
        add_filter( 'woocommerce_get_sections_products', 'add_section' );
        add_action( 'init', array( $this, 'if_show_atts_on_shop' ) );
        add_action( 'woocommerce_grouped_product_list_before_price', array( $this, 'show_atts_grouped_product' ) );

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
     * Get the non-variation attributes of a product.
     *
     * @param WC_Product $product Product instance.
     * @param bool $single_product True when on single product page.
     * @return array Array of attributes, each with label and value.
     * @since 1.6.4
     */
    private function get_attributes(WC_Product $product, bool $single_product): array
    {
        $out = [];
        foreach ($product->get_attributes() as $attribute) {
            if (!($attribute instanceof WC_Product_Attribute) || $attribute->get_variation() || !$attribute->get_visible()) {
                continue;
            }
            $name = $attribute->get_name();
            $data = $attribute->is_taxonomy()
                ? $this->get_global_taxonomy_attribute_data($name, $product, $single_product)
                : ['label' => $name, 'value' => esc_html(implode(', ', $attribute->get_options()))];
            $out[] = $data;
        }
        return $out;
    }

    /**
     * Returns the HTML string for the product attributes.
     * This does not affect nor include attributes which are used for Variations.
     *
     * @param WC_Product $product Product instance.
     * @param boolean $show_weight Whether to show the product weight.
     * @param boolean $show_dimensions Whether to show the product dimensions.
     * @param boolean $skip_atts Whether to skip the attributes and only honor weight and dimensions.
     * @param bool $single_product True when on the single product page.
     * @return string The HTML string of product attributes.
     */
    public function product_attributes_html(WC_Product $product, bool $show_weight = false, bool $show_dimensions = false, bool $skip_atts = false, bool $single_product = false): string
    {
        $attribute_list = [];

        if ($show_weight && $product->has_weight()) {
            $weight_value = "{$product->get_weight()} {$this->unit}";
            $weight_text = __('Weight', 'woocommerce-show-attributes');

            $attribute_list[] = $this->attribute_html->single_attribute_element($weight_text, $weight_value, 'weight');
        }

        if ($show_dimensions && $product->has_dimensions()) {
            $dimensions = wc_format_dimensions($product->get_dimensions(false));
            $dimensions_label = __('Dimensions', 'woocommerce-show-attributes');

            $attribute_list[] = $this->attribute_html->single_attribute_element($dimensions_label, $dimensions, 'dimensions');
        }

        if (!$skip_atts && !empty($attributes = $this->get_attributes($product, $single_product))) {
            foreach ($attributes as $attribute) {
                $attribute_list[] = $this->attribute_html->single_attribute_element($attribute['label'], $attribute['value'], $attribute['value']);
            }
        }

        $attribute_list_string = implode('', $attribute_list);
        return $this->attribute_html->wrap_attributes($attribute_list_string);
    }

    /**
     * Get the attribute label and value for a global attribute.
     *
     * Global attributes are those which are stored as taxonomies and created on the Products > Attributes page.
     *
     * @param string $name Name of the attribute
     * @param WC_Product $product Product id or instance.
     * @param mixed $single_product_page true when on single product page
     * @since 1.6.4
     */
    private function get_global_taxonomy_attribute_data(string $name, WC_Product $product, string $single_product_page ): array
    {
        $out = [];
        $terms = wp_get_post_terms( $product->get_id(), $name, 'all' );

        if(empty($terms) || is_wp_error( $terms )) {
            return $out;
        }

        $tax_object = get_taxonomy( $terms[0]->taxonomy );
        $out['label'] = $tax_object->labels->singular_name ?? (isset($tax_object->label) ? substr($tax_object->label, strlen(__('Product', 'woocommerce-show-attributes') . ' ')) : null);

        $tax_terms = [];
        foreach ( $terms as $term ) {
            $single_term = esc_html( $term->name );
            if ( $single_product_page && $this->terms_are_links && ! $term_link = get_term_link( $term )) {
                $single_term = '<a href="' . esc_url( $term_link ) . '">' . esc_html( $term->name) . '</a>';
            }
            $tax_terms[] = $single_term;
        }
        $out['value'] = implode( ', ', $tax_terms );

        return $out;
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
        echo wp_kses_post(
            $this->product_attributes_html(
                $product,
                $this->show_weight,
                $this->show_dimensions,
                $this->skip_attributes,
                true
            )
        );
    }

    /**
     * Show product attributes on the Cart page.
     */
    public function show_attributes_on_cart($name, $cart_item, $cart_item_key) {
        // Return the original product name if attributes are not visible in the cart
        if (!$this->visible_in_cart) {
            return $name;
        }

        $product = $cart_item['data'];
        $attributes = wp_kses_post(
            $this->product_attributes_html(
                $product,
                $this->show_weight_cart,
                $this->show_dimensions_cart,
                $this->skip_attributes_cart
            )
        );

        return "$name<br />$attributes";
    }


    /**
     * Show product attributes on the child products of a Grouped Product page.
     *
     * @param WC_Product $product , the product object
     * @since 1.2.4
     */
    public function show_atts_grouped_product( WC_Product $product ) {
        echo '<td class="grouped-product-custom-attributes">' . wp_kses_post( $this->product_attributes_html( $product, $this->show_weight, $this->show_dimensions, $this->skip_attributes ) ) . '</td>';
    }

    /**
     * Show the attributes on the main shop page.
     * @since 1.2.3
     */
    public function show_attributes_on_shop() {
        global $product;
        echo wp_kses_post( $this->product_attributes_html( $product ) );
    }


    /**
     * Customize the Additional Information tab to NOT show our custom attributes.
     *
     * @param array $tabs The current array of tabs.
     * @return array The modified array of tabs.
     */
    public function additional_info_tab(array $tabs): array
    {
        global $product;

        // Ensure $product is a valid object with attributes
        if (!is_object($product) || !$product->has_attributes()) {
            return $tabs;
        }

        // Check if the product has dimensions or weight
        $has_dimensions_or_weight = $product->has_dimensions() || $product->has_weight();

        // Determine if the Additional Information tab should be removed or modified
        if ($has_dimensions_or_weight) {
            if ($this->show_weight && $this->show_dimensions) {
                // Remove the tab if both weight and dimensions are set to be shown
                unset($tabs['additional_information']);
            } else {
                // Modify the callback for the tab content if not both are shown
                $tabs['additional_information']['callback'] = array($this, 'additional_info_tab_content');
            }
        } else {
            // Check if any custom attributes need to be shown in the tab
            $custom_attributes_visible = $this->has_visible_custom_attributes($product);

            if ($custom_attributes_visible) {
                $tabs['additional_information']['callback'] = array($this, 'additional_info_tab_content');
            } else {
                // Remove the tab if no custom attributes, dimensions, or weight should be shown
                unset($tabs['additional_information']);
            }
        }

        return $tabs;
    }

    /**
     * Determine if the product has any visible custom attributes.
     *
     * @param WC_Product $product The product instance.
     * @return bool True if there are visible custom attributes, false otherwise.
     */
    private function has_visible_custom_attributes(WC_Product $product): bool
    {
        foreach ($product->get_attributes() as $attribute) {
            if (is_a($attribute, 'WC_Product_Attribute') && $attribute->get_visible() && !$attribute->get_variation()) {
                return true;
            }
        }
        return false;
    }


    /**
     * The custom HTML for the Additional Information tab which now excludes our custom attributes.
     */
    public function additional_info_tab_content() { ?>
        <h2><?php _e( 'Additional Information', 'woocommerce-show-attributes' ); ?></h2>
        <table class="shop_attributes">
            <?php
            global $product;
            $attributes = $product->get_attributes();
            $has_weight = $product->has_weight();
            $has_dimensions = $product->has_dimensions();
            $display_dimensions = apply_filters( 'wc_product_enable_dimensions_display', $has_weight || $has_dimensions );

            if ( $this->show_weight && $display_dimensions && $has_weight) {
                ?>
                <tr>
                    <th><?php _e( 'Weight', 'woocommerce-show-attributes' ) ?></th>
                    <td class="product_weight"><?php echo esc_html( wc_format_weight( $product->get_weight() ) ); ?></td>
                </tr>
                <?php
            }

            if ( $this->show_dimensions && $display_dimensions && $has_dimensions ) {
                ?>
                <tr>
                    <th><?php _e( 'Dimensions', 'woocommerce-show-attributes' ) ?></th>
                    <td class="product_dimensions"><?php echo esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ); ?></td>
                </tr>
                <?php
            }

            foreach ( $attributes as $attribute ) :
                $name = $attribute->get_name();
                ?>
                <tr>
                    <th><?php echo esc_html( wc_attribute_label( $name ) ); ?></th>
                    <td><?php
                        $values = $this->formatted_attribute_values($product, $attribute);
                        echo apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values );
                        ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    /**
     * Retrieve formatted attribute values for a product.
     *
     * @param WC_Product $product   The product object.
     * @param WC_Product_Attribute $attribute The product attribute object.
     *
     * @return array The list of formatted attribute values.
     */
    public function formatted_attribute_values(WC_Product $product, WC_Product_Attribute $attribute ) : array {
        $values = [];

        if( ! $attribute->is_taxonomy()) {
            return array_map( 'esc_html', $attribute->get_options() );
        }

        $product_terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

        foreach ( $product_terms as $product_term ) {
            $product_term_name = esc_html( $product_term->name );
            $link = get_term_link( $product_term->term_id, $attribute->get_name() );

            $is_public = ! empty( wc_get_attribute( wc_attribute_taxonomy_id_by_name( $attribute->get_name() ) )->attribute_public );

            $values[] = $is_public ? '<a href="' . esc_url( $link ) . '" rel="tag">' . $product_term_name . '</a>' : $product_term_name;
        }

        return $values;
    }

    /**
     * Save default options upon plugin activation
     */
    static function install() {
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

} // end class

// only if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    $WooCommerce_Show_Attributes = WooCommerce_Show_Attributes::get_instance();
    register_activation_hook(__FILE__, array( $WooCommerce_Show_Attributes, 'install' ) );
}
