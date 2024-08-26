<?php

class Attribute_HTML {
	private bool $hide_labels;
	private string $colon;

	public function __construct( bool $hide_labels, string $colon ) {
		$this->hide_labels     = $hide_labels;
		$this->colon           = $colon;
	}

	/**
	 * Retrieve formatted attribute values for a product.
	 *
	 * @return array The list of formatted attribute values.
	 */
	public function formatted_attribute_values( array $attributes ): array {

		$values = [];

		foreach ( $attributes as $attribute ) {
			$product_term_name = esc_html( $attribute->name );
			$values[] = $product_term_name;
		}

		return $values;
	}

	/**
	 * Returns the HTML string for the product attributes.
	 * This does not affect nor include attributes which are used for Variations.
	 *
	 * @param array $attributes Product attributes to display.
	 *
	 * @return string The HTML string of product attributes.
	 */
	public function product_attributes_html( array $attributes ): string {
		$attribute_list = [];

		foreach ( $attributes as $attribute ) {
			$attribute_list[] = $this->single_attribute_element( $attribute['label'], $attribute['value'], $attribute['value'] );
		}

		$attribute_list_string = implode('', $attribute_list);
		return $this->wrap_attributes($attribute_list_string);
	}

	/**
	 * Show product attributes on the child products of a Grouped Product page.
	 *
	 * @param array $attributes Product attributes.
	 * @since 1.2.4
	 */
	public function attributes_grouped_product_html( array $attributes ): string {
		$attribute_html = $this->product_attributes_html( $attributes );

		return "<td class='grouped-product-custom-attributes'>{$attribute_html}</td>";
	}

	private function single_attribute_element(string $label, string $value, string $class): string {
		$label = esc_html($label);
		$value = esc_html($value);
		$class = esc_attr($class);

		$attribute_html = $this->hide_labels
			? "<span class='attribute-value'>{$value}</span>"
			: "<span class='attribute-label'>{$label}{$this->colon}</span><span class='attribute-value'>{$value}</span>";

		return "<{$this->display_element} class='show-attributes-{$class}'>{$attribute_html}</{$this->display_element}>";
	}

	private function wrap_attributes(string $attribute_list_string): string {
		return "<ul class='custom-attributes'>{$attribute_list_string}</ul>";
	}

}