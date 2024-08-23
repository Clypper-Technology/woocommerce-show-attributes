<?php

class Product_attributes {

	public function __construct() {

	}

	/**
	 * Determine if the product has any visible custom attributes.
	 *
	 * @param WC_Product $product The product instance.
	 * @return bool True if there are visible custom attributes, false otherwise.
	 */
	public function has_visible_custom_attributes(WC_Product $product): bool
	{
		foreach ($product->get_attributes() as $attribute) {
			if (is_a($attribute, 'WC_Product_Attribute') && $attribute->get_visible() && !$attribute->get_variation()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the non-variation attributes of a product.
	 *
	 * @param WC_Product $product Product instance.
	 *
	 * @return array Array of attributes, each with label and value.
	 * @since 1.6.4
	 */
	public function get_attributes( WC_Product $product ): array {
		$out = [];
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! ( $attribute instanceof WC_Product_Attribute ) || $attribute->get_variation() || ! $attribute->get_visible() ) {
				continue;
			}
			$name  = $attribute->get_name();
			$data  = $attribute->is_taxonomy()
				? $this->get_global_taxonomy_attribute_data( $name, $product )
				: [ 'label' => $name, 'value' => esc_html( implode( ', ', $attribute->get_options() ) ) ];
			$out[] = $data;
		}

		return $out;
	}

	/**
	 * Get the attribute label and value for a global attribute.
	 *
	 * Global attributes are those which are stored as taxonomies and created on the Products > Attributes page.
	 *
	 * @param string $name Name of the attribute
	 * @param WC_Product $product Product id or instance.
	 *
	 * @since 1.6.4
	 */
	private function get_global_taxonomy_attribute_data( string $name, WC_Product $product ): array {
		$out   = [];
		$terms = wp_get_post_terms( $product->get_id(), $name, 'all' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $out;
		}

		$tax_object   = get_taxonomy( $terms[0]->taxonomy );
		$out['label'] = $tax_object->labels->singular_name ?? ( isset( $tax_object->label ) ? substr( $tax_object->label, strlen( __( 'Product', 'woocommerce-show-attributes' ) . ' ' ) ) : null );

		$tax_terms = [];

		foreach ( $terms as $term ) {
			$tax_terms[] = esc_html( $term->name );
		}

		$out['value'] = implode( ', ', $tax_terms );

		return $out;
	}



}