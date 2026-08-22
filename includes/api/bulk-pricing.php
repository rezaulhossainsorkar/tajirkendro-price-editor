<?php
/**
 * TKPE bulk pricing API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get the price from a product.
 *
 * @param WC_Product $product   Product object.
 * @param string     $price_type Price type.
 * @return string
 */
function tkpe_get_bulk_price( $product, $price_type ) {

	if ( 'sale' === $price_type ) {
		return $product->get_sale_price();
	}

	return $product->get_regular_price();
}


/**
 * Set a product price.
 *
 * @param WC_Product $product    Product object.
 * @param string     $price_type Price type.
 * @param string     $price      New price.
 * @return void
 */
function tkpe_set_bulk_price(
	$product,
	$price_type,
	$price
) {

	if ( 'sale' === $price_type ) {

		$product->set_sale_price( $price );

		return;
	}

	$product->set_regular_price( $price );
}


/**
 * Validate the resulting product prices.
 *
 * @param WC_Product $product Product object.
 * @return true|WP_Error
 */
function tkpe_validate_bulk_product_prices( $product ) {

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
				'Sale price cannot be greater than the regular price. Decrease the sale price or increase the regular price.',
				'tajirkendro-price-editor'
			)
		);
	}

	return true;
}


/**
 * Apply a pricing rule to one price.
 *
 * @param WC_Product $product    Product object.
 * @param string     $price_type Price type.
 * @param array      $rule       Pricing rule.
 * @return true|WP_Error
 */
function tkpe_apply_bulk_pricing_to_product(
	$product,
	$price_type,
	$rule
) {

	$method = isset( $rule['method'] )
		? sanitize_key( $rule['method'] )
		: '';

	/*
	 * Empty method means no change.
	 */
	if ( '' === $method ) {
		return true;
	}


	/*
	 * Handle both regular and sale prices.
	 */
	if ( 'both' === $price_type ) {

		/*
		 * Store the original prices so we can
		 * validate the final result if anything fails.
		 */
		$original_regular_price = $product->get_regular_price();
		$original_sale_price    = $product->get_sale_price();


		/*
		 * Apply the rule to the regular price.
		 */
		$regular_price = $product->get_regular_price();

		$new_regular_price = tkpe_apply_pricing_rule(
			$regular_price,
			$rule
		);

		if ( is_wp_error( $new_regular_price ) ) {
			return $new_regular_price;
		}

		$product->set_regular_price(
			$new_regular_price
		);


		/*
		 * Apply the rule to the sale price.
		 *
		 * If there is no existing sale price, only
		 * "Set new price" is allowed to create one.
		 */
		$sale_price = $product->get_sale_price();

		if ( '' === $sale_price ) {

			if ( 'set' !== $method ) {

				/*
				 * Restore the original regular price
				 * before returning the error.
				 */
				$product->set_regular_price(
					$original_regular_price
				);

				return new WP_Error(
					'invalid_sale_base',
					__(
						'This product does not have a sale price. Use "Set new price" if you want to create a sale price.',
						'tajirkendro-price-editor'
					)
				);
			}

			/*
			 * For "Set new price", create the sale price
			 * using the same value.
			 */
			$new_sale_price = tkpe_apply_pricing_rule(
				$sale_price,
				$rule
			);

		} else {

			$new_sale_price = tkpe_apply_pricing_rule(
				$sale_price,
				$rule
			);
		}

		if ( is_wp_error( $new_sale_price ) ) {

			$product->set_regular_price(
				$original_regular_price
			);

			return $new_sale_price;
		}

		$product->set_sale_price(
			$new_sale_price
		);


		/*
		 * Validate the final regular/sale price
		 * relationship.
		 */
		$validation = tkpe_validate_bulk_product_prices(
			$product
		);

		if ( is_wp_error( $validation ) ) {

			$product->set_regular_price(
				$original_regular_price
			);

			$product->set_sale_price(
				$original_sale_price
			);

			return $validation;
		}

		$product->save();

		return true;
	}


	/*
	 * Existing single-price behavior.
	 */
	$current_price = tkpe_get_bulk_price(
		$product,
		$price_type
	);


	/*
	 * A sale price must exist when using
	 * increase/decrease operations.
	 */
	if (
		'sale' === $price_type &&
		'' === $current_price &&
		'set' !== $method
	) {

		return new WP_Error(
			'invalid_sale_base',
			__(
				'This product does not have a sale price. Choose "Set new price" to create one.',
				'tajirkendro-price-editor'
			)
		);
	}


	$new_price = tkpe_apply_pricing_rule(
		$current_price,
		$rule
	);

	if ( is_wp_error( $new_price ) ) {
		return $new_price;
	}


	tkpe_set_bulk_price(
		$product,
		$price_type,
		$new_price
	);


	$validation = tkpe_validate_bulk_product_prices(
		$product
	);

	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$product->save();

	return true;
}


/**
 * AJAX: Apply bulk pricing.
 *
 * @return void
 */
function tkpe_ajax_apply_bulk_pricing() {

	tkpe_verify_ajax_request();


	$product_ids = isset( $_POST['product_ids'] )
		? wp_unslash( $_POST['product_ids'] )
		: array();

	$price_type = isset( $_POST['price_type'] )
		? sanitize_key(
			wp_unslash( $_POST['price_type'] )
		)
		: '';

	$method = isset( $_POST['method'] )
		? sanitize_key(
			wp_unslash( $_POST['method'] )
		)
		: '';

	$value = isset( $_POST['value'] )
		? wc_format_decimal(
			wp_unslash( $_POST['value'] )
		)
		: '';


	/*
	 * Validate product IDs.
	 */
	if ( ! is_array( $product_ids ) ) {

		wp_send_json_error(
			array(
				'message' => __(
					'No products were selected.',
					'tajirkendro-price-editor'
				),
			),
			400
		);
	}


	$product_ids = array_filter(
		array_map( 'absint', $product_ids )
	);


	if ( empty( $product_ids ) ) {

		wp_send_json_error(
			array(
				'message' => __(
					'Please select at least one product.',
					'tajirkendro-price-editor'
				),
			),
			400
		);
	}


	/*
	 * Validate price type.
	 */
	if ( ! in_array(
		$price_type,
		array( 'regular', 'sale', 'both' ),
		true
	) ) {

		wp_send_json_error(
			array(
				'message' => __(
					'Invalid price type.',
					'tajirkendro-price-editor'
				),
			),
			400
		);
	}


	/*
	 * Validate the pricing rule.
	 */
	$rule = array(
		'method' => $method,
		'value'  => $value,
	);


	$rule_validation = tkpe_validate_pricing_rule(
		$rule
	);


	if ( is_wp_error( $rule_validation ) ) {

		wp_send_json_error(
			array(
				'message' => $rule_validation->get_error_message(),
			),
			400
		);
	}


	$updated = array();
	$failed  = array();


	foreach ( $product_ids as $product_id ) {

		$product = wc_get_product( $product_id );


		if ( ! $product ) {

			$failed[] = array(
				'id'      => $product_id,
				'name'    => __(
					'Unknown product',
					'tajirkendro-price-editor'
				),
				'message' => __(
					'Product could not be found.',
					'tajirkendro-price-editor'
				),
			);

			continue;
		}


		/*
		 * Variable products are handled through
		 * their variations.
		 */
		if ( $product->is_type( 'variable' ) ) {

			$variation_failed = false;


			foreach ( $product->get_children() as $variation_id ) {

				$variation = wc_get_product(
					$variation_id
				);


				if ( ! $variation ) {
					continue;
				}


				$result = tkpe_apply_bulk_pricing_to_product(
					$variation,
					$price_type,
					$rule
				);


				if ( is_wp_error( $result ) ) {

					$variation_failed = true;


					$failed[] = array(
						'id'      => $variation->get_id(),
						'name'    => $product->get_name(),
						'message' => $result->get_error_message(),
					);
				}
			}


			/*
			 * Save the parent variable product so
			 * WooCommerce can recalculate its prices.
			 */
			$product->save();


			if ( ! $variation_failed ) {

				$updated[] = array(
					'id'   => $product->get_id(),
					'name' => $product->get_name(),
				);
			}


			continue;
		}


		/*
		 * Simple product.
		 */
		$result = tkpe_apply_bulk_pricing_to_product(
			$product,
			$price_type,
			$rule
		);


		if ( is_wp_error( $result ) ) {

			$failed[] = array(
				'id'      => $product->get_id(),
				'name'    => $product->get_name(),
				'message' => $result->get_error_message(),
			);

			continue;
		}


		$updated[] = array(
			'id'   => $product->get_id(),
			'name' => $product->get_name(),
		);
	}


	/*
	 * Return updated products so the
	 * table can update without reloading.
	 */
	$updated_products = array();


	foreach ( $updated as $updated_product ) {

		$product = wc_get_product(
			$updated_product['id']
		);


		if ( ! $product ) {
			continue;
		}


		$updated_products[] = tkpe_prepare_products(
			array( $product )
		)[0];
	}


	wp_send_json_success(
		array(
			'message'  => __(
				'Bulk pricing completed.',
				'tajirkendro-price-editor'
			),
			'updated'  => $updated,
			'failed'   => $failed,
			'products' => $updated_products,
		)
	);
}


add_action(
	'wp_ajax_tkpe_apply_bulk_pricing',
	'tkpe_ajax_apply_bulk_pricing'
);