<?php
/**
 * TKPE product API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Verify TKPE AJAX request.
 *
 * @return void
 */
function tkpe_verify_ajax_request() {

	check_ajax_referer( 'tkpe_admin_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {

		wp_send_json_error(
			array(
				'message' => __( 'You do not have permission to perform this action.', 'tajirkendro-price-editor' ),
			),
			403
		);
	}
}


/**
 * Get filter options.
 *
 * @return array
 */
function tkpe_get_filter_options() {

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $categories ) ) {
		$categories = array();
	}


	/*
	 * Get registered WordPress post statuses.
	 *
	 * Product statuses are WordPress post statuses because
	 * WooCommerce products are stored as the "product" post type.
	 */
	$product_statuses = get_post_statuses();


	return array(
		'categories'     => $categories,

		'types'          => function_exists( 'wc_get_product_types' )
			? wc_get_product_types()
			: array(),

		'stock_statuses' => function_exists( 'wc_get_product_stock_status_options' )
			? wc_get_product_stock_status_options()
			: array(),

		'statuses'       => $product_statuses,
	);
}


/**
 * Get products by search.
 *
 * Search is independent from filters.
 *
 * @param string $search Search term.
 * @param int    $limit  Number of products.
 * @return WC_Product[]
 */
function tkpe_get_products_by_search( $search, $limit = 20 ) {

	$search = sanitize_text_field( $search );

	if ( '' === $search ) {
		return array();
	}

	return wc_get_products(
		array(
			'search' => $search,
			'status' => array(
				'publish',
				'draft',
				'pending',
				'private',
			),
			'limit'  => absint( $limit ),
			'return' => 'objects',
		)
	);
}


/**
 * Get products by filters.
 *
 * Search is deliberately not accepted here.
 *
 * @param array $filters Filter values.
 * @param int   $limit   Number of products.
 * @return WC_Product[]
 */
function tkpe_get_products_by_filters( $filters = array(), $limit = 20 ) {

	$args = array(
		'status' => array(
			'publish',
			'draft',
			'pending',
			'private',
		),
		'limit'  => absint( $limit ),
		'return' => 'objects',
	);

	/*
	 * Category.
	 *
	 * The admin form sends the category term ID.
	 * WooCommerce product queries expect category slugs.
	 */
	if ( ! empty( $filters['category'] ) ) {

		$category = get_term(
			absint( $filters['category'] ),
			'product_cat'
		);

		if ( $category && ! is_wp_error( $category ) ) {

			$args['category'] = array(
				$category->slug,
			);
		}
	}


	/*
	 * Product type.
	 */
	if ( ! empty( $filters['type'] ) ) {

		$args['type'] = sanitize_key(
			$filters['type']
		);
	}


	/*
	 * Stock status.
	 */
	if ( ! empty( $filters['stock_status'] ) ) {

		$args['stock_status'] = sanitize_key(
			$filters['stock_status']
		);
	}


	/*
	 * Product status.
	 */
	if ( ! empty( $filters['status'] ) ) {

		$args['status'] = sanitize_key(
			$filters['status']
		);
	}


	return wc_get_products( $args );
}


/**
 * Get product search suggestions.
 *
 * Searches product names and SKUs independently from filters.
 *
 * @param string $search Search term.
 * @param int    $limit  Maximum number of suggestions.
 * @return WC_Product[]
 */
function tkpe_get_search_suggestions( $search, $limit = 8 ) {

	global $wpdb;

	$search = sanitize_text_field( $search );
	$limit  = absint( $limit );

	if ( '' === $search || 0 === $limit ) {
		return array();
	}

	$search_like = '%' . $wpdb->esc_like( $search ) . '%';

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT DISTINCT posts.ID
			FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->postmeta} AS sku_meta
				ON posts.ID = sku_meta.post_id
				AND sku_meta.meta_key = '_sku'

			WHERE posts.post_type = 'product'

			AND posts.post_status IN (
				'publish',
				'draft',
				'pending',
				'private'
			)

			AND (
				posts.post_title LIKE %s
				OR sku_meta.meta_value LIKE %s
			)

			ORDER BY
				CASE
					WHEN posts.post_title LIKE %s THEN 0
					WHEN sku_meta.meta_value LIKE %s THEN 1
					ELSE 2
				END,
				posts.post_title ASC

			LIMIT %d
			",
			$search_like,
			$search_like,
			$search_like,
			$search_like,
			$limit
		)
	);

	if ( empty( $product_ids ) ) {
		return array();
	}

	$products = array();

	foreach ( $product_ids as $product_id ) {

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		$products[] = $product;
	}

	return $products;
}


/**
 * Prepare product data for JavaScript.
 *
 * @param WC_Product[] $products Products.
 * @return array
 */
function tkpe_prepare_products( $products ) {

	$data = array();

	foreach ( $products as $product ) {

		$image_id  = $product->get_image_id();
		$image_url = $image_id
			? wp_get_attachment_image_url( $image_id, 'thumbnail' )
			: wc_placeholder_img_src( 'thumbnail' );

		$type = $product->get_type();

		$type_labels = function_exists( 'wc_get_product_types' )
			? wc_get_product_types()
			: array();

		$status_labels = function_exists( 'wc_get_product_statuses' )
			? wc_get_product_statuses()
			: array();

		$stock_labels = function_exists( 'wc_get_product_stock_status_options' )
			? wc_get_product_stock_status_options()
			: array();

		$stock_status = $product->get_stock_status();

		$data[] = array(
			'id'              => $product->get_id(),
			'name'            => $product->get_name(),
			'image'           => $image_url,
			'type'            => $type,
			'type_label'      => isset( $type_labels[ $type ] )
				? $type_labels[ $type ]
				: ucfirst( $type ),
			'status'          => $product->get_status(),
			'status_label'    => isset( $status_labels[ $product->get_status() ] )
				? $status_labels[ $product->get_status() ]
				: ucfirst( $product->get_status() ),
			'stock_status'    => $stock_status,
			'stock_label'     => isset( $stock_labels[ $stock_status ] )
				? $stock_labels[ $stock_status ]
				: ucfirst( $stock_status ),
			'stock_quantity'  => $product->get_stock_quantity(),
			'manage_stock'    => $product->managing_stock(),
			'regular_price'   => $product->get_regular_price(),
			'sale_price'      => $product->get_sale_price(),
			'price'           => $product->get_price(),
		);
	}

	return $data;
}


/**
 * AJAX: Search suggestions.
 *
 * @return void
 */
function tkpe_ajax_search_suggestions() {

	tkpe_verify_ajax_request();

	$search = isset( $_POST['search'] )
		? sanitize_text_field( wp_unslash( $_POST['search'] ) )
		: '';

	if ( strlen( $search ) < 2 ) {

		wp_send_json_success(
			array(
				'suggestions' => array(),
			)
		);
	}

	$products = tkpe_get_search_suggestions( $search, 8 );

	$suggestions = array();

	foreach ( $products as $product ) {

		$image_id = $product->get_image_id();

		$suggestions[] = array(
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'sku'   => $product->get_sku(),
			'image' => $image_id
				? wp_get_attachment_image_url(
					$image_id,
					'thumbnail'
				)
				: wc_placeholder_img_src( 'thumbnail' ),
		);
	}

	wp_send_json_success(
		array(
			'suggestions' => $suggestions,
		)
	);
}

add_action(
	'wp_ajax_tkpe_search_suggestions',
	'tkpe_ajax_search_suggestions'
);


/**
 * AJAX: Search products.
 *
 * @return void
 */
function tkpe_ajax_search_products() {

	tkpe_verify_ajax_request();

	$search = isset( $_POST['search'] )
		? sanitize_text_field( wp_unslash( $_POST['search'] ) )
		: '';

	if ( '' === $search ) {

		wp_send_json_error(
			array(
				'message' => __( 'Please enter a search term.', 'tajirkendro-price-editor' ),
			)
		);
	}

	$products = tkpe_get_products_by_search( $search );

	wp_send_json_success(
		array(
			'source'   => 'search',
			'products' => tkpe_prepare_products( $products ),
		)
	);
}

add_action(
	'wp_ajax_tkpe_search_products',
	'tkpe_ajax_search_products'
);


/**
 * AJAX: Filter products.
 *
 * @return void
 */
function tkpe_ajax_filter_products() {

	tkpe_verify_ajax_request();

	$filters = array(
		'category'     => isset( $_POST['category'] )
			? absint( $_POST['category'] )
			: 0,
		'type'         => isset( $_POST['type'] )
			? sanitize_key( wp_unslash( $_POST['type'] ) )
			: '',
		'stock_status' => isset( $_POST['stock_status'] )
			? sanitize_key( wp_unslash( $_POST['stock_status'] ) )
			: '',
		'status'       => isset( $_POST['status'] )
			? sanitize_key( wp_unslash( $_POST['status'] ) )
			: '',
	);

	$products = tkpe_get_products_by_filters( $filters );

	wp_send_json_success(
		array(
			'source'   => 'filter',
			'products' => tkpe_prepare_products( $products ),
		)
	);
}

add_action(
	'wp_ajax_tkpe_filter_products',
	'tkpe_ajax_filter_products'
);


/**
 * AJAX: Get one selected product.
 *
 * @return void
 */
function tkpe_ajax_get_selected_product() {

	tkpe_verify_ajax_request();

	$product_id = isset( $_POST['product_id'] )
		? absint( $_POST['product_id'] )
		: 0;

	if ( ! $product_id ) {

		wp_send_json_error(
			array(
				'message' => __( 'Invalid product.', 'tajirkendro-price-editor' ),
			)
		);
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {

		wp_send_json_error(
			array(
				'message' => __( 'Product not found.', 'tajirkendro-price-editor' ),
			)
		);
	}

	wp_send_json_success(
		array(
			'source'   => 'selected_product',
			'products' => tkpe_prepare_products(
				array( $product )
			),
		)
	);
}

add_action(
	'wp_ajax_tkpe_get_selected_product',
	'tkpe_ajax_get_selected_product'
);