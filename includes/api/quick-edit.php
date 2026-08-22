<?php
/**
 * TKPE Quick Edit API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

	/*
	 * An empty method means that the
	 * corresponding price should not change.
	 */
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
 * The actual price calculation is handled by the
 * shared tkpe_calculate_price() function in
 * product-updater.php.
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

	/*
	 * No method means that the current price
	 * should remain unchanged.
	 */
	if ( '' === $method ) {
		return $current_price;
	}

	/*
	 * Use the shared calculation function
	 * declared in product-updater.php.
	 */
	return tkpe_calculate_price(
		$current_price,
		$method,
		$value
	);
}


/**
 * Update a simple product.
 *
 * This function is specific to Quick Edit.
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

		$regular_method = isset( $pricing['regular']['method'] )
			? sanitize_key( $pricing['regular']['method'] )
			: '';

		if ( '' !== $regular_method ) {
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
		$sale_method = isset( $pricing['sale']['method'] )
			? sanitize_key( $pricing['sale']['method'] )
			: '';

		if (
			'' === $current_sale_price &&
			'' !== $sale_method &&
			'set' !== $sale_method
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

		if ( '' !== $sale_method ) {
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