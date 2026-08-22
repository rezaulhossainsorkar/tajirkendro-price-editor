<?php
/**
 * TKPE Quick Edit API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Calculate a new price from a pricing rule.
 *
 * @param string $current_price Current price.
 * @param string $method        Pricing method.
 * @param string $value         Rule value.
 * @return string
 */
function tkpe_calculate_price( $current_price, $method, $value ) {

	if ( '' === $method ) {
		return $current_price;
	}

	$current_price = (float) $current_price;
	$value         = (float) $value;

	switch ( $method ) {

		case 'set':
			$new_price = $value;
			break;

		case 'increase_percentage':
			$new_price = $current_price + (
				$current_price * ( $value / 100 )
			);
			break;

		case 'decrease_percentage':
			$new_price = $current_price - (
				$current_price * ( $value / 100 )
			);
			break;

		case 'increase_fixed':
			$new_price = $current_price + $value;
			break;

		case 'decrease_fixed':
			$new_price = $current_price - $value;
			break;

		default:
			return $current_price;
	}

	/*
	 * A price must never become negative.
	 */
	$new_price = max( 0, $new_price );

	return wc_format_decimal( $new_price );
}


/**
 * Validate pricing rule.
 *
 * @param array $rule Pricing rule.
 * @return true|WP_Error
 */
function tkpe_validate_pricing_rule( $rule ) {

	if ( ! is_array( $rule ) ) {

		return new WP_Error(
			'invalid_pricing_rule',
			__( 'Invalid pricing rule.', 'tajirkendro-price-editor' )
		);
	}

	$method = isset( $rule['method'] )
		? sanitize_key( $rule['method'] )
		: '';

	$value = isset( $rule['value'] )
		? wc_format_decimal( $rule['value'] )
		: '';

	if ( '' === $method ) {
		return true;
	}

	$allowed_methods = array(
		'set',
		'increase_percentage',
		'decrease_percentage',
		'increase_fixed',
		'decrease_fixed',
	);

	if ( ! in_array( $method, $allowed_methods, true ) ) {

		return new WP_Error(
			'invalid_pricing_method',
			__( 'Invalid pricing method.', 'tajirkendro-price-editor' )
		);
	}

	if ( '' === $value || (float) $value < 0 ) {

		return new WP_Error(
			'invalid_pricing_value',
			__( 'Please enter a valid pricing value.', 'tajirkendro-price-editor' )
		);
	}

	return true;
}


/**
 * Apply pricing rule to a product price.
 *
 * @param string $current_price Current price.
 * @param array  $rule          Pricing rule.
 * @return string|WP_Error
 */
function tkpe_apply_pricing_rule( $current_price, $rule ) {

	$validation = tkpe_validate_pricing_rule( $rule );

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$method = isset( $rule['method'] )
		? sanitize_key( $rule['method'] )
		: '';

	$value = isset( $rule['value'] )
		? wc_format_decimal( $rule['value'] )
		: '';

	return tkpe_calculate_price(
		$current_price,
		$method,
		$value
	);
}


/**
 * Update a simple product.
 *
 * @param WC_Product $product Product.
 * @param array      $pricing Pricing rules.
 * @return true|WP_Error
 */
function tkpe_update_simple_product( $product, $pricing ) {

	if ( ! is_array( $pricing ) ) {

		return new WP_Error(
			'invalid_pricing',
			__( 'Invalid pricing data.', 'tajirkendro-price-editor' )
		);
	}


	/*
	 * Regular price.
	 */
	if ( isset( $pricing['regular'] ) ) {

		$result = tkpe_apply_pricing_rule(
			$product->get_regular_price(),
			$pricing['regular']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if (
			'' !== $pricing['regular']['method']
		) {
			$product->set_regular_price( $result );
		}
	}


	/*
	 * Sale price.
	 */
	if ( isset( $pricing['sale'] ) ) {

		$current_sale_price = $product->get_sale_price();

		/*
		 * If there is currently no sale price and
		 * an increase/decrease operation is requested,
		 * there is no meaningful base value.
		 */
		if (
			'' === $current_sale_price &&
			! empty( $pricing['sale']['method'] ) &&
			'set' !== $pricing['sale']['method']
		) {

			return new WP_Error(
				'invalid_sale_base',
				__(
					'A sale price must already exist before it can be increased or decreased.',
					'tajirkendro-price-editor'
				)
			);
		}

		$result = tkpe_apply_pricing_rule(
			$current_sale_price,
			$pricing['sale']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if (
			'' !== $pricing['sale']['method']
		) {
			$product->set_sale_price( $result );
		}
	}


	/*
	 * Validate sale price against regular price.
	 */
	$regular_price = $product->get_regular_price();
	$sale_price    = $product->get_sale_price();

	if (
		'' !== $regular_price &&
		'' !== $sale_price &&
		(float) $sale_price > (float) $regular_price
	) {

		return new WP_Error(
			'invalid_sale_price',
			__(
				'Sale price cannot be greater than the regular price.',
				'tajirkendro-price-editor'
			)
		);
	}

	return true;
}


/**
 * Update variable product variations.
 *
 * @param WC_Product_Variable $product    Variable product.
 * @param array               $variations Variation rules.
 * @return true|WP_Error
 */
function tkpe_update_variable_product( $product, $variations ) {

	if ( ! is_array( $variations ) ) {

		return new WP_Error(
			'invalid_variations',
			__( 'Invalid variation data.', 'tajirkendro-price-editor' )
		);
	}

	foreach ( $variations as $variation_data ) {

		if (
			! is_array( $variation_data ) ||
			empty( $variation_data['id'] )
		) {
			continue;
		}

		$variation_id = absint(
			$variation_data['id']
		);

		$variation = wc_get_product(
			$variation_id
		);

		if (
			! $variation ||
			'variation' !== $variation->get_type()
		) {
			continue;
		}

		/*
		 * Make sure this variation actually belongs
		 * to the variable product being edited.
		 */
		if (
			(int) $variation->get_parent_id() !==
			(int) $product->get_id()
		) {
			continue;
		}

		$pricing = isset( $variation_data['pricing'] )
			? $variation_data['pricing']
			: array();

		$result = tkpe_update_simple_product(
			$variation,
			$pricing
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$variation->save();
	}

	return true;
}


/**
 * AJAX: Update product from Quick Edit.
 *
 * @return void
 */
function tkpe_ajax_update_product() {

	tkpe_verify_ajax_request();

	$product_id = isset( $_POST['product_id'] )
		? absint( $_POST['product_id'] )
		: 0;

	if ( ! $product_id ) {

		wp_send_json_error(
			array(
				'message' => __(
					'Invalid product.',
					'tajirkendro-price-editor'
				),
			)
		);
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		wp_send_json_error(
			array(
				'message' => __(
					'Product not found.',
					'tajirkendro-price-editor'
				),
			)
		);
	}


	/*
	 * Normal product.
	 */
	if (
		isset( $_POST['pricing'] ) &&
		is_array( $_POST['pricing'] )
	) {

		$pricing = wp_unslash(
			$_POST['pricing']
		);

		$result = tkpe_update_simple_product(
			$product,
			$pricing
		);

		if ( is_wp_error( $result ) ) {

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		$product->save();
	}


	/*
	 * Variable product.
	 */
	if (
		'variable' === $product->get_type() &&
		isset( $_POST['variations'] ) &&
		is_array( $_POST['variations'] )
	) {

		$variations = wp_unslash(
			$_POST['variations']
		);

		$result = tkpe_update_variable_product(
			$product,
			$variations
		);

		if ( is_wp_error( $result ) ) {

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		/*
		 * Recalculate the variable product's
		 * cached pricing after variation changes.
		 */
		$product->save();
	}


	/*
	 * Re-fetch the product after saving.
	 *
	 * This guarantees that the browser receives
	 * WooCommerce's actual final values.
	 */
	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		wp_send_json_error(
			array(
				'message' => __(
					'Unable to retrieve the updated product.',
					'tajirkendro-price-editor'
				),
			)
		);
	}

	$product_data = tkpe_prepare_products(
		array( $product )
	);

	wp_send_json_success(
		array(
			'product' => $product_data[0],
		)
	);
}

add_action(
	'wp_ajax_tkpe_update_product',
	'tkpe_ajax_update_product'
);