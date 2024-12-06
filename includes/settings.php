<?php

/**
 * Add settings to the Show Attributes section.
 * @since 1.4.0
 */
function add_settings( $settings, $current_section ) {
    if ( 'wc_show_attributes' == $current_section ) {
        return wcsa_all_settings();
    } else {
        return $settings;
    }
}

/**
 * Add our settings section under the Products tab.
 * @since 1.4.0
 */
function add_section( $sections ) {
    $sections['wc_show_attributes'] = __( 'Show Attributes', 'woocommerce-show-attributes' );
    return $sections;
}

/**
 * Return an array of all our settings
 * @since 1.6.1
 */
function wcsa_all_settings(): array
{
    return array(
        array(
            'name' => __('WooCommerce Show Attributes Options', 'woocommerce-show-attributes'),
            'type' => 'title',
            'desc' => __('Where would you like to show your custom product attributes?', 'woocommerce-show-attributes'),
            'id' => 'wc_show_attributes'),
        array(
            'name' => __('Show Attributes on Product Page', 'woocommerce-show-attributes'),
            'id' => 'wcsa_product',
            'default' => 'yes',
            'type' => 'checkbox',
            'desc' => __('Show attributes on the single product above Add To Cart, and on Grouped products.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show Attributes on Shop Pages', 'woocommerce-show-attributes'),
            'desc' => __('Whether to show attributes on the main shop page and shop category pages.', 'woocommerce-show-attributes'),
            'id' => 'woocommerce_show_attributes_on_shop',
            'css' => '',
            'default' => 'no',
            'type' => 'select',
            'options' => array(
                '' => __('No', 'woocommerce-show-attributes'),
                'above_price' => __('Show them above the price', 'woocommerce-show-attributes'),
                'above_add2cart' => __('Show them above "Add to Cart"', 'woocommerce-show-attributes'),
                'above_title' => __('Show them above product title', 'woocommerce-show-attributes'),
            ),
            'desc_tip' => true,
        ),
        array(
            'name' => __('Show Attributes on Cart Page', 'woocommerce-show-attributes'),
            'id' => 'wcsa_cart',
            'default' => 'yes',
            'type' => 'checkbox',
            'desc' => __('Show attributes on the cart and checkout pages.', 'woocommerce-show-attributes')
        ),
        array('type' => 'sectionend', 'id' => 'wc_show_attributes'),
        // style
        array(
            'title' => __('Style Options', 'woocommerce-show-attributes'),
            'desc' => __('These options affect the style or appearance of the attributes.', 'woocommerce-show-attributes'),
            'type' => 'title',
            'id' => 'wcsa_style'
        ),
        array(
            'name' => __('Hide the Labels When Showing Product Attributes', 'woocommerce-show-attributes'),
            'id' => 'woocommerce_show_attributes_hide_labels',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Check this box to hide the attribute labels and only show the attribute values.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show Attributes in a span Element', 'woocommerce-show-attributes'),
            'id' => 'woocommerce_show_attributes_span',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Check this box to use a span element instead of list bullets when showing product attributes on the single product page.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Remove Colon From Attribute Labels', 'woocommerce-show-attributes'),
            'id' => 'wcsa_remove_semicolon',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Check this box to remove the colon from the attribute labels. Useful for RTL languages.', 'woocommerce-show-attributes')
        ),
        array('type' => 'sectionend', 'id' => 'wcsa_style'),
        // weight and Dimensions
        array(
            'title' => __('Show Weight and Dimensions', 'woocommerce-show-attributes'),
            'desc' => __('These options let you show the product weight and dimensions in various places.', 'woocommerce-show-attributes'),
            'type' => 'title',
            'id' => 'wc_show_weight_dimensions'
        ),
        array(
            'name' => __('Show Weight on Product Page Above Add To Cart', 'woocommerce-show-attributes'),
            'id' => 'wcsa_weight_product',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show product weight on the single product pages, and Grouped products, above Add To Cart instead of in the Additional Information tab.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show Dimensions on Product Page Above Add To Cart', 'woocommerce-show-attributes'),
            'id' => 'wcsa_dimensions_product',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show product dimensions on the single product pages, and Grouped products, above Add To Cart instead of in the Additional Information tab.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show Weight on Cart Page', 'woocommerce-show-attributes'),
            'id' => 'wcsa_weight_cart',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show product weight on the cart and checkout pages.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show Dimensions on Cart Page', 'woocommerce-show-attributes'),
            'id' => 'wcsa_dimensions_cart',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show product dimensions on the cart and checkout pages.', 'woocommerce-show-attributes')
        ),
        array(
            'name' => __('Show visible attributes on Cart Page', 'woocommerce-show-attributes'),
            'id' => 'wcsa_visible_cart',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show visible product attributes on the cart and checkout pages.', 'woocommerce-show-attributes')
        ),
        array('type' => 'sectionend', 'id' => 'wc_show_weight_dimensions'),
        // Extra Options
        array(
            'title' => __('Extra Options', 'woocommerce-show-attributes'),
            'type' => 'title',
            'id' => 'wcsa_extra_options'
        ),
        array(
            'name' => __('Show Attribute Terms as Links', 'woocommerce-show-attributes'),
            'id' => 'wcsa_terms_as_links',
            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('On the single product page, show the attribute terms as links. They will link to their archive pages. This only works with Global Attributes. Global Attributes are created in Products -> Attributes.', 'woocommerce-show-attributes')
        ),
        array('type' => 'sectionend', 'id' => 'wcsa_extra_options'),
    );
}