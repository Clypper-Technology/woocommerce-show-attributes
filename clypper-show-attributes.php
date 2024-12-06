<?php
	/*
	Plugin Name: Clypper Show Attributes
	Description: Show WooCommerce custom product attributes on the Product, Shop and Cart pages, admin Order Details page and emails.
	Version: 1.7.1
	Author: Clypper von H
	License: GPL2
	Text Domain: woocommerce-show-attributes
	Domain Path: languages

	WooCommerce Show Attributes is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
	*/
	if ( ! defined( 'ABSPATH' ) ) exit;
	class Clypper_Show_Attributes {

        private array $options;
		private static $instance = null;
		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		private function __construct() {
            require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';

            $this->options = get_options( [
                'woocommerce_show_attributes_hide_labels',
                'woocommerce_show_attributes_span',
	            'wcsa_remove_semicolon',
	            'woocommerce_weight_unit',
	            'wcsa_terms_as_links',
	            'wcsa_weight_product',
	            'wcsa_dimensions_product',
	            'wcsa_product',
            ] );

            add_action( 'woocommerce_single_product_summary', array( $this, 'show_atts_on_product_page' ), 25 );
			add_filter( 'woocommerce_product_tabs', array( $this, 'additional_info_tab' ), 98 );
            add_action ( 'woocommerce_shop_loop_item_title', array( $this, 'show_atts_on_shop' ), 4 );
            add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        }

        public function enqueue_styles() {
            wp_enqueue_style('clypper-show-attributes', plugin_dir_url(__FILE__) . 'assets/css/clypper-show-attributes.css', array(), '1.7.1');
        }

        /**
         * Get the non-variation attributes of a product.
         *
         * @param WC_Product_Simple|WC_Product_Variation $product Product instance.
         * @param bool $single_product True when on single product page.
         * @return array Array of attributes, each with label and value.
         * @since 1.6.4
         */
        private function get_attributes($product, $single_product): array {
            // Cache key for this product's terms (shared with clypper_additional_information)
            $cache_key = 'clypper_product_terms_' . $product->get_id();
            $terms_by_taxonomy = wp_cache_get($cache_key, 'woocommerce');

            if (false === $terms_by_taxonomy) {
                // Cache miss, so fetch terms from the database
                $product_attributes = $product->get_attributes();
                $taxonomy_names = [];

                foreach ($product_attributes as $attribute) {
                    if ($attribute->is_taxonomy()) {
                        $taxonomy_names[] = $attribute->get_name();
                    }
                }

                if (!empty($taxonomy_names)) {
                    $terms = wp_get_object_terms($product->get_id(), $taxonomy_names);
                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $term) {
                            $terms_by_taxonomy[$term->taxonomy][] = $term;
                        }
                    }
                }

                // Cache the results for future use
                wp_cache_set($cache_key, $terms_by_taxonomy, 'woocommerce', 3600);
            }

            // Now use the cached terms to build the attributes
            $attributes = [];
            $product_attributes = $product->get_attributes();

            foreach ($product_attributes as $attribute) {
                $options = $attribute->get_options();

                if (!$attribute->get_visible() || empty($options)) {
                    continue;
                }

                $name = wc_attribute_label($attribute->get_name());

                // Build the attribute value based on taxonomy check
                $value = $attribute->is_taxonomy()
                    ? $this->get_global_taxonomy_attribute_data_from_cache($attribute->get_name(), $terms_by_taxonomy)
                    : ['label' => $name, 'value' => esc_html(implode(', ', $options))];

                // Append to attributes list
                $attributes[] = $value;
            }

            return $attributes;
        }


        /**
         * Display product attributes.
         *
         * @param WC_Product|null $product Product instance.
         * @param string $element HTML element to wrap attributes in.
         * @param bool $show_weight Whether to show weight.
         * @param bool $show_dimensions Whether to show dimensions.
         * @param bool $skip_atts Whether to skip attributes.
         * @param bool $single_product True when on single product page.
         * @return string HTML string of attributes.
         */
        public function the_attributes($product = null, $element = 'span', $show_weight = false, $show_dimensions = false, $skip_atts = false, $single_product = false): string {
            if (!$product || !is_object($product)) return '';

            $hide_labels = $this->options['woocommerce_show_attributes_hide_labels'] === 'yes';
            $span_option = $this->options['woocommerce_show_attributes_span'] === 'yes';
            $colon = $this->options['wcsa_remove_semicolon'] === 'yes' ? ' ' : ': ';
            $weight_unit = $this->options['woocommerce_weight_unit'];
	        $terms_as_links = $this->options['wcsa_terms_as_links'] === 'yes';

            $element = $span_option ? 'span' : $element;
            $attributes_list = [];

            // Add weight attribute
            if ($show_weight && $product->has_weight()) {
                $attributes_list[] = $this->format_attribute($element, 'Weight', $product->get_weight() . ' ' . $weight_unit, $hide_labels, $colon);
            }

            // Add dimensions attribute
            if ($show_dimensions && $product->has_dimensions()) {
                $attributes_list[] = $this->format_attribute($element, 'Dimensions', wc_format_dimensions($product->get_dimensions(false)), $hide_labels, $colon);
            }

            // Add product attributes
            if (!$skip_atts) {
                $product_attributes = $this->get_attributes($product, $single_product);
                foreach ($product_attributes as $attribute) {
                    $attributes_list[] = $this->format_attribute($element, $attribute['label'], $attribute['value'], $hide_labels, $colon);
                }
            }

            if (empty($attributes_list)) {
                return '';
            }

            $attributes_html = implode('', $attributes_list);
            $wrapper_tag = $element === 'li' ? 'ul' : 'span';

            return sprintf('<%s class="custom-attributes">%s</%s>', $wrapper_tag, $attributes_html, $wrapper_tag);
        }

        /**
         * Format a single attribute for display.
         *
         * @param string $element HTML element to wrap attribute in.
         * @param string $label Attribute label.
         * @param string $value Attribute value.
         * @param bool $hide_labels Whether to hide labels.
         * @param string $colon Character(s) to use as colon.
         * @return string HTML string of formatted attribute.
         */
        private function format_attribute($element, $label, $value, $hide_labels, $colon): string {
            $label_html = $hide_labels ? '' : sprintf('<span class="attribute-label"><span class="attribute-label-text">%s</span>%s</span>', esc_html($label), $colon);
            return sprintf('<%s class="attribute-list-item">%s<span class="attribute-value">%s</span></%s>', esc_attr($element), $label_html, esc_html($value), esc_attr($element));
        }

        /**
         * Get the attribute label and value for a global attribute using cached terms.
         *
         * @param string $name Name of the attribute.
         * @param array $terms_by_taxonomy Cached terms for the product.
         * @return array Array with label and value.
         * @since 1.6.4
         */
        private function get_global_taxonomy_attribute_data_from_cache($name, $terms_by_taxonomy): array {
            $label = wc_attribute_label($name);
            $terms = $terms_by_taxonomy[$name] ?? [];

            if (empty($terms)) {
                return [
                    'label' => $label,
                    'value' => ''
                ];
            }

            $term_names = array_map(function ($term) {
                return esc_html($term->name);
            }, $terms);

            return [
                'label' => $label,
                'value' => implode(', ', $term_names)
            ];
        }

        /**
		 * Show product attributes on the product page.
		 *
		 * Show product attributes above the Add to Cart button on the single product page
		 * and on the Parent of Grouped products.
		 */

        public function show_atts_on_product_page() {
            $show_weight = $this->options[ 'wcsa_weight_product' ] === 'yes';
            $show_dimensions = $this->options[ 'wcsa_dimensions_product' ] === 'yes';
            $skip_atts = $this->options[ 'wcsa_product' ] === 'no';

            global $product;
            echo wp_kses_post($this->the_attributes($product, 'li', $show_weight, $show_dimensions, $skip_atts, true));
        }


        /**
		 * Show the attributes on the main shop page.
		 * @since 1.2.3
		 */
		public function show_atts_on_shop() {
			global $product;

			echo wp_kses_post( $this->the_attributes( $product, 'li' ) );
		}


		/**
		 * Customize the Additional Information tab to NOT show our custom attributes
		 */
        public function additional_info_tab($tabs)
        {
            global $product;

            if (!is_a($product, 'WC_Product')) return $tabs;

            if (!empty($product->get_attributes())) {
                $tabs['additional_information']['title'] = __('Specifikationer');
                $tabs['additional_information']['callback'] = [$this, 'clypper_additional_information'];
                $tabs['additional_information']['priority'] = 1;
            } else {
                unset($tabs['additional_information']);
            }

            return $tabs;
        }

        public function clypper_additional_information() {
            global $product;

            // Cache key for this product's terms
            $cache_key = 'clypper_product_terms_' . $product->get_id();
            $terms_by_taxonomy = wp_cache_get($cache_key, 'woocommerce');

            if (false === $terms_by_taxonomy) {
                // Cache miss, so fetch terms from the database
                $product_attributes = $product->get_attributes();
                $taxonomy_names = [];

                foreach ($product_attributes as $attribute) {
                    if ($attribute->is_taxonomy()) {
                        $taxonomy_names[] = $attribute->get_name();
                    }
                }

                if (!empty($taxonomy_names)) {
                    // Fetch all terms in a single query
                    $terms = wp_get_object_terms($product->get_id(), $taxonomy_names);
                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $term) {
                            $terms_by_taxonomy[$term->taxonomy][] = $term;
                        }
                    }
                }

                // Cache the results for future use
                wp_cache_set($cache_key, $terms_by_taxonomy, 'woocommerce', 3600);
            }

            // Display the attributes and associated terms
            ?>
            <div class="attribute-wrapper">
                <?php foreach ($product->get_attributes() as $product_attribute) {
                    $this->display_attribute_item($product_attribute, $terms_by_taxonomy);
                } ?>
            </div>
            <?php
        }

        private function display_attribute_item($product_attribute, $terms_by_taxonomy) {
            ?>
            <div class="attribute-item">
                <p class="attribute-name">
                    <strong><?php echo esc_html(wc_attribute_label($product_attribute->get_name())) . ':'; ?></strong>
                </p>
                <div class="attribute-value-wrapper">
                    <?php $this->display_attribute_values($product_attribute, $terms_by_taxonomy); ?>
                </div>
            </div>
            <?php
        }

        private function display_attribute_values($product_attribute, $terms_by_taxonomy) {
            $output = [];

            if ($product_attribute->is_taxonomy()) {
                $taxonomy = $product_attribute->get_name();
                $attribute_values = $terms_by_taxonomy[$taxonomy] ?? [];

                foreach ($attribute_values as $term) {
                    $output[] = sprintf('<p class="attribute-value-single">%s</p>', esc_html($term->name));
                }
            } else {
                $attribute_values = $product_attribute->get_options();
                foreach ($attribute_values as $attribute_value) {
                    $output[] = sprintf('<p class="attribute-value-single">%s</p>', esc_html($attribute_value));
                }
            }

            // Implode the array to output the HTML all at once
            echo implode('', $output);
        }


        /**
		 * Save default options upon plugin activation
		 */
		static function install() {
			$settings = wcsa_all_settings();
			foreach ( $settings as $option ) {
				if ( ! empty( $option['default'] ) ) {// Only if we have any defaults
					$db_option = get_option( $option['id'] );
					if ( empty( $db_option ) ) {// If option is empty, set the default value
						update_option( $option['id'], $option['default'] );
					}
				}
			}

		}

	} // end class

    // only if WooCommerce is active
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$WooCommerce_Show_Attributes = Clypper_Show_Attributes::get_instance();
		register_activation_hook(__FILE__, array( $WooCommerce_Show_Attributes, 'install' ) );
	}
