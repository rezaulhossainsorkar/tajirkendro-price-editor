<?php
/**
 * KTPE product REST API.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register the KTPE product REST route.
 *
 * @return void
 */
function tkpe_register_product_rest_route() {

	register_rest_route(
		'tkpe/v1',
		'/products',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'tkpe_get_products_rest',
			'permission_callback' => 'tkpe_product_rest_permission',
			'args'                => array(
				'search' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'category' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_title',
				),
				'type' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'tkpe_validate_product_type',
				),
				'stock_status' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'tkpe_validate_stock_status',
				),
				'status' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'tkpe_validate_product_status',
				),
				'page' => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 1,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
				'per_page' => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 10,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'tkpe_validate_per_page',
				),
			),
		)
	);

}

add_action( 'rest_api_init', 'tkpe_register_product_rest_route' );


/**
 * Check permission for the product REST endpoint.
 *
 * WordPress REST cookie authentication verifies the wp_rest nonce
 * sent by the browser. This callback additionally restricts access
 * to users who can manage WooCommerce.
 *
 * @return bool
 */
function tkpe_product_rest_permission() {

	return current_user_can( 'manage_woocommerce' );

}


/**
 * Validate the product type.
 *
 * @param mixed           $value   Requested product type.
 * @param WP_REST_Request $request REST request.
 * @param string          $param   Parameter name.
 * @return bool|WP_Error
 */
function tkpe_validate_product_type( $value, $request, $param ) {

	$allowed_types = array(
		'',
		'simple',
		'variable',
		'grouped',
		'external',
	);

	if ( in_array( $value, $allowed_types, true ) ) {
		return true;
	}

	return new WP_Error(
		'tkpe_invalid_product_type',
		__( 'Invalid product type.', 'tajirkendro-price-editor' ),
		array(
			'status' => 400,
		)
	);

}


/**
 * Validate the stock status.
 *
 * @param mixed           $value   Requested stock status.
 * @param WP_REST_Request $request REST request.
 * @param string          $param   Parameter name.
 * @return bool|WP_Error
 */
function tkpe_validate_stock_status( $value, $request, $param ) {

	$allowed_statuses = array(
		'',
		'instock',
		'outofstock',
		'onbackorder',
	);

	if ( in_array( $value, $allowed_statuses, true ) ) {
		return true;
	}

	return new WP_Error(
		'tkpe_invalid_stock_status',
		__( 'Invalid stock status.', 'tajirkendro-price-editor' ),
		array(
			'status' => 400,
		)
	);

}


/**
 * Validate the product status.
 *
 * @param mixed           $value   Requested product status.
 * @param WP_REST_Request $request REST request.
 * @param string          $param   Parameter name.
 * @return bool|WP_Error
 */
function tkpe_validate_product_status( $value, $request, $param ) {

	$allowed_statuses = array(
		'',
		'publish',
		'draft',
		'pending',
		'private',
	);

	if ( in_array( $value, $allowed_statuses, true ) ) {
		return true;
	}

	return new WP_Error(
		'tkpe_invalid_product_status',
		__( 'Invalid product status.', 'tajirkendro-price-editor' ),
		array(
			'status' => 400,
		)
	);

}


/**
 * Validate products per page.
 *
 * @param mixed           $value   Requested number of products per page.
 * @param WP_REST_Request $request REST request.
 * @param string          $param   Parameter name.
 * @return bool|WP_Error
 */
function tkpe_validate_per_page( $value, $request, $param ) {

	$value = absint( $value );

	if ( in_array( $value, array( 10, 20, 30, 50 ), true ) ) {
		return true;
	}

	return new WP_Error(
		'tkpe_invalid_per_page',
		__( 'Products per page must be 10, 20, 30, or 50.', 'tajirkendro-price-editor' ),
		array(
			'status' => 400,
		)
	);

}


/**
 * Get products for the REST endpoint.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function tkpe_get_products_rest( WP_REST_Request $request ) {

	$search       = $request->get_param( 'search' );
	$category     = $request->get_param( 'category' );
	$type         = $request->get_param( 'type' );
	$stock_status = $request->get_param( 'stock_status' );
	$status       = $request->get_param( 'status' );
	$page         = max( 1, absint( $request->get_param( 'page' ) ) );
	$per_page     = absint( $request->get_param( 'per_page' ) );

	if ( ! in_array( $per_page, array( 10, 20, 30, 50 ), true ) ) {
		$per_page = 10;
	}

	/**
	 * Validate the selected category before passing it to WooCommerce.
	 */
	if ( ! empty( $category ) ) {

		$category_term = get_term_by(
			'slug',
			$category,
			'product_cat'
		);

		if ( ! $category_term || is_wp_error( $category_term ) ) {
			return new WP_Error(
				'tkpe_invalid_category',
				__( 'Invalid product category.', 'tajirkendro-price-editor' ),
				array(
					'status' => 400,
				)
			);
		}
	}

	$query_args = array(
		'limit'    => $per_page,
		'page'     => $page,
		'paginate' => true,
		'orderby'  => 'title',
		'order'    => 'ASC',
	);

	if ( '' !== $search ) {
		$query_args['search'] = $search;
	}

	if ( '' !== $category ) {
		$query_args['category'] = array( $category );
	}

	if ( '' !== $type ) {
		$query_args['type'] = array( $type );
	}

	if ( '' !== $stock_status ) {
		$query_args['stock_status'] = $stock_status;
	}

	if ( '' !== $status ) {
		$query_args['status'] = $status;
	}

	$results = wc_get_products( $query_args );

	if ( ! is_object( $results ) || ! isset( $results->products, $results->total, $results->max_num_pages ) ) {
		return new WP_Error(
			'tkpe_product_query_failed',
			__( 'Unable to retrieve products.', 'tajirkendro-price-editor' ),
			array(
				'status' => 500,
			)
		);
	}

	$products = $results->products;

	/**
	 * Collect category IDs from only the current page.
	 */
	$category_ids = array();

	foreach ( $products as $product ) {

		$product_category_ids = $product->get_category_ids();

		if ( ! empty( $product_category_ids ) ) {
			$category_ids = array_merge( $category_ids, $product_category_ids );
		}
	}

	$category_ids = array_values( array_unique( array_map( 'absint', $category_ids ) ) );

	/**
	 * Resolve all category names with one taxonomy query.
	 */
	$category_map = array();

	if ( ! empty( $category_ids ) ) {

		$category_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => $category_ids,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $category_terms ) ) {

			foreach ( $category_terms as $category_term ) {
				$category_map[ $category_term->term_id ] = $category_term->name;
			}
		}
	}

	$product_data = array();

	foreach ( $products as $product ) {

		$product_category_names = array();

		foreach ( $product->get_category_ids() as $category_id ) {

			if ( isset( $category_map[ $category_id ] ) ) {
				$product_category_names[] = $category_map[ $category_id ];
			}
		}

		$product_data[] = array(
			'id'            => $product->get_id(),
			'name'          => $product->get_name(),
			'image'         => $product->get_image_id()
				? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' )
				: '',
			'categories'    => $product_category_names,
			'type'          => $product->get_type(),
			'status'        => $product->get_status(),
			'stock_status'  => $product->get_stock_status(),
			'regular_price' => $product->get_regular_price(),
			'sale_price'   => $product->get_sale_price(),
		);
	}

	$response = array(
		'products' => $product_data,
		'total'    => (int) $results->total,
		'pages'    => (int) $results->max_num_pages,
		'page'     => $page,
		'per_page' => $per_page,
	);

	return rest_ensure_response( $response );

}