jQuery(document).ready(function ($) {

	'use strict';

	var suggestionTimer = null;
	var suggestionRequest = null;
	var searchRequest = null;
	var filterRequest = null;


	/**
	 * Dynamic search suggestions.
	 */
	$('#tkpe-search').on('input', function () {

		var search = $.trim($(this).val());

		clearTimeout(suggestionTimer);

		if (suggestionRequest) {
			suggestionRequest.abort();
			suggestionRequest = null;
		}

		if (search.length < 2) {

			$('#tkpe-search-suggestions')
				.empty()
				.prop('hidden', true);

			return;
		}

		suggestionTimer = setTimeout(function () {

			tkpe_load_suggestions(search);

		}, 250);

	});


	/**
	 * Load search suggestions.
	 *
	 * @param {string} search Search term.
	 */
	function tkpe_load_suggestions(search) {

		suggestionRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_search_suggestions',
				nonce: tkpeAdmin.nonce,
				search: search
			},

			success: function (response) {

				if (!response.success) {
					return;
				}

				/*
				 * Make sure the response still belongs
				 * to the current search input.
				 */
				if (
					search !== $.trim(
						$('#tkpe-search').val()
					)
				) {
					return;
				}

				tkpe_render_suggestions(
					response.data.suggestions
				);

			},

			complete: function () {
				suggestionRequest = null;
			}

		});
	}


	/**
	 * Render suggestions.
	 *
	 * @param {Array} suggestions Suggestions.
	 */
	function tkpe_render_suggestions(suggestions) {

		var $container = $('#tkpe-search-suggestions');

		$container.empty();

		if (!suggestions.length) {

			$container
				.append(
					$('<div>', {
						class: 'tkpe-no-suggestions',
						text: 'No products found.'
					})
				)
				.prop('hidden', false);

			return;
		}

		$.each(suggestions, function (index, product) {

			var $item = $('<button>', {
				type: 'button',
				class: 'tkpe-search-suggestion'
			});

			var $image = $('<img>', {
				src: product.image,
				alt: product.name
			});

			var $details = $('<span>', {
				class: 'tkpe-suggestion-details'
			});

			$details.append(
				$('<strong>', {
					text: product.name
				})
			);

			if (product.sku) {

				$details.append(
					$('<small>', {
						text: 'SKU: ' + product.sku
					})
				);

			}

			$item
				.append($image)
				.append($details)
				.data('product-id', product.id)
				.data('product-name', product.name);

			$container.append($item);

		});

		$container.prop('hidden', false);
	}


	/**
	 * Load selected product.
	 *
	 * @param {number} productId Product ID.
	 */
	$(document).on(
		'click',
		'.tkpe-search-suggestion',
		function () {

			var productId = $(this).data('product-id');
			var productName = $(this).data('product-name');

			$('#tkpe-search').val(productName);

			$('#tkpe-search-suggestions')
				.empty()
				.prop('hidden', true);

			tkpe_load_selected_product(productId);
		}
	);


	/**
	 * Submit search.
	 *
	 * Search does not read filter values.
	 */
	$('#tkpe-search-form').on('submit', function (event) {

		event.preventDefault();

		var search = $.trim(
			$('#tkpe-search').val()
		);

		if (!search) {
			return;
		}

		$('#tkpe-search-suggestions')
			.empty()
			.prop('hidden', true);

		tkpe_search_products(search);

	});


	/**
	 * Search products.
	 *
	 * @param {string} search Search term.
	 */
	function tkpe_search_products(search) {

		if (searchRequest) {
			searchRequest.abort();
			searchRequest = null;
		}

		tkpe_show_loading();

		searchRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_search_products',
				nonce: tkpeAdmin.nonce,
				search: search
			},

			success: function (response) {

				if (!response.success) {
					return;
				}

				tkpe_render_products(
					response.data.products
				);

			},

			complete: function () {
				searchRequest = null;
			}

		});
	}


	/**
	 * Load the exact product selected from search suggestions.
	 *
	 * @param {number} productId Product ID.
	 */
	function tkpe_load_selected_product(productId) {

		if (searchRequest) {
			searchRequest.abort();
			searchRequest = null;
		}

		tkpe_show_loading();

		searchRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_get_selected_product',
				nonce: tkpeAdmin.nonce,
				product_id: productId
			},

			success: function (response) {

				if (!response.success) {
					return;
				}

				tkpe_render_products(
					response.data.products
				);

			},

			complete: function () {
				searchRequest = null;
			}

		});
	}


	/**
	 * Apply filters.
	 *
	 * The selected filter values are captured first.
	 * The current result state is then reset before
	 * the new filter request is sent.
	 *
	 * Search remains completely independent.
	 */
	$('#tkpe-filter-form').on('submit', function (event) {

		event.preventDefault();

		tkpe_apply_filters();

	});


	/**
	 * Apply the currently selected filters.
	 */
	function tkpe_apply_filters() {

		var filters = {
			category: $('#tkpe-category').val(),
			type: $('#tkpe-type').val(),
			stock_status: $('#tkpe-stock-status').val(),
			status: $('#tkpe-status').val()
		};


		/*
		 * Reset the current result state first.
		 *
		 * The filter form itself is deliberately
		 * not reset because these are the values
		 * we are about to apply.
		 */
		tkpe_reset_results();


		/*
		 * Apply the captured filter values.
		 */
		tkpe_filter_products(filters);

	}


	/**
	 * Reset current product results.
	 *
	 * This resets the current result state only.
	 * Filter values are preserved.
	 */
	function tkpe_reset_results() {

		clearTimeout(suggestionTimer);


		/*
		 * Abort pending requests.
		 */
		if (suggestionRequest) {
			suggestionRequest.abort();
			suggestionRequest = null;
		}

		if (searchRequest) {
			searchRequest.abort();
			searchRequest = null;
		}

		if (filterRequest) {
			filterRequest.abort();
			filterRequest = null;
		}


		/*
		 * Clear search suggestions.
		 */
		$('#tkpe-search-suggestions')
			.empty()
			.prop('hidden', true);


		/*
		 * Clear product tables.
		 */
		$('#tkpe-bulk-products').empty();
		$('#tkpe-quick-products').empty();


		/*
		 * Hide both tabs.
		 */
		$('#tkpe-bulk-tab').prop('hidden', true);
		$('#tkpe-quick-tab').prop('hidden', true);


		/*
		 * Always make Bulk Edit the default tab.
		 */
		$('.tkpe-tab-button').removeClass('is-active');

		$('.tkpe-tab-button[data-tab="bulk"]')
			.addClass('is-active');

	}


	/**
	 * Filter products.
	 *
	 * @param {Object} filters Filter values.
	 */
	function tkpe_filter_products(filters) {

		if (filterRequest) {
			filterRequest.abort();
			filterRequest = null;
		}

		tkpe_show_loading();

		filterRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_filter_products',
				nonce: tkpeAdmin.nonce,

				category: filters.category,
				type: filters.type,
				stock_status: filters.stock_status,
				status: filters.status
			},

			success: function (response) {

				if (!response.success) {
					return;
				}

				tkpe_render_products(
					response.data.products
				);

			},

			complete: function () {
				filterRequest = null;
			}

		});
	}


	/**
	 * Reset filters and results.
	 */
	$('#tkpe-reset-filters').on('click', function (event) {

		event.preventDefault();

		/*
		 * Reset the actual filter form.
		 */
		$('#tkpe-filter-form')[0].reset();


		/*
		 * Clear the search field as well because
		 * Reset means returning the whole UI to
		 * its initial state.
		 */
		$('#tkpe-search').val('');


		/*
		 * Reset displayed results.
		 */
		tkpe_reset_results();

	});


	/**
	 * Render product tables.
	 *
	 * Bulk Edit is always the default tab.
	 *
	 * @param {Array} products Products.
	 */
	function tkpe_render_products(products) {

		tkpe_render_bulk_products(products);
		tkpe_render_quick_products(products);


		/*
		 * Always activate Bulk Edit when a new
		 * result set has been rendered.
		 */
		$('.tkpe-tab-button').removeClass('is-active');

		$('.tkpe-tab-button[data-tab="bulk"]')
			.addClass('is-active');


		/*
		 * Show Bulk Edit by default.
		 */
		$('#tkpe-bulk-tab').prop('hidden', false);
		$('#tkpe-quick-tab').prop('hidden', true);

	}


	/**
	 * Render bulk table.
	 *
	 * Variable products are excluded.
	 *
	 * @param {Array} products Products.
	 */
	function tkpe_render_bulk_products(products) {

		var $body = $('#tkpe-bulk-products');

		$body.empty();

		$.each(products, function (index, product) {

			if ('variable' === product.type) {
				return;
			}

			var $row = $('<tr>');

			var $checkbox = $('<input>', {
				type: 'checkbox',
				class: 'tkpe-product-checkbox',
				value: product.id
			});

			$row.append(
				$('<td>').append($checkbox)
			);

			$row.append(
				tkpe_product_cell(product)
			);

			$row.append(
				$('<td>', {
					text: product.type_label
				})
			);

			$row.append(
				$('<td>', {
					text: product.status_label
				})
			);

			$row.append(
				tkpe_stock_cell(product)
			);

			$row.append(
				tkpe_price_cell(product)
			);

			$body.append($row);

		});

	}


	/**
	 * Render quick table.
	 *
	 * @param {Array} products Products.
	 */
	function tkpe_render_quick_products(products) {

		var $body = $('#tkpe-quick-products');

		$body.empty();

		$.each(products, function (index, product) {

			var $row = $('<tr>');

			$row.attr(
				'data-product-id',
				product.id
			);

			$row.append(
				tkpe_product_cell(product)
			);

			$row.append(
				$('<td>', {
					text: product.type_label
				})
			);

			$row.append(
				$('<td>', {
					text: product.status_label
				})
			);

			$row.append(
				tkpe_stock_cell(product)
			);

			$row.append(
				tkpe_price_cell(product)
			);

			var $actions = $('<td>', {
				class: 'tkpe-actions'
			});

			$actions.append(
				$('<button>', {
					type: 'button',
					class: 'button tkpe-view-product',
					text: 'View'
				}).data('product-id', product.id)
			);

			$actions.append(
				$('<button>', {
					type: 'button',
					class: 'button tkpe-edit-product',
					text: 'Edit'
				}).data('product-id', product.id)
			);

			$actions.append(
				$('<button>', {
					type: 'button',
					class: 'button tkpe-delete-product',
					text: 'Delete'
				}).data('product-id', product.id)
			);

			$row.append($actions);

			$body.append($row);

		});

	}


	/**
	 * Product cell.
	 *
	 * @param {Object} product Product.
	 * @return {jQuery} Product cell.
	 */
	function tkpe_product_cell(product) {

		var $cell = $('<td>', {
			class: 'tkpe-product-cell'
		});

		var $image = $('<img>', {
			src: product.image,
			alt: product.name,
			class: 'tkpe-product-image'
		});

		var $name = $('<span>', {
			class: 'tkpe-product-name',
			text: product.name
		});

		$cell
			.append($image)
			.append($name);

		return $cell;
	}


	/**
	 * Stock cell.
	 *
	 * @param {Object} product Product.
	 * @return {jQuery} Stock cell.
	 */
	function tkpe_stock_cell(product) {

		var stock_text = product.stock_label;

		if (
			product.manage_stock &&
			null !== product.stock_quantity
		) {

			stock_text += ' (' + product.stock_quantity + ')';

		}

		return $('<td>', {
			text: stock_text
		});
	}


	/**
	 * Price cell.
	 *
	 * @param {Object} product Product.
	 * @return {jQuery} Price cell.
	 */
	function tkpe_price_cell(product) {

		var $cell = $('<td>', {
			class: 'tkpe-price-cell'
		});

		if (product.regular_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-regular-price',
					text: 'Regular: ' + product.regular_price
				})
			);

		}

		if (product.sale_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-sale-price',
					text: 'Sale: ' + product.sale_price
				})
			);

		}

		if (
			!product.regular_price &&
			!product.sale_price
		) {

			$cell.text('—');

		}

		return $cell;
	}


	/**
	 * Show table loading state.
	 */
	function tkpe_show_loading() {

		/*
		 * Bulk Edit is always the loading/default tab.
		 */
		$('.tkpe-tab-button').removeClass('is-active');

		$('.tkpe-tab-button[data-tab="bulk"]')
			.addClass('is-active');

		$('#tkpe-bulk-tab').prop('hidden', false);
		$('#tkpe-quick-tab').prop('hidden', true);

		$('#tkpe-bulk-products').html(
			'<tr><td colspan="6" class="tkpe-loading">Loading products...</td></tr>'
		);

	}


	/**
	 * Switch between tabs.
	 */
	$(document).on(
		'click',
		'.tkpe-tab-button',
		function () {

			var tab = $(this).data('tab');

			$('.tkpe-tab-button').removeClass('is-active');

			$(this).addClass('is-active');

			if ('bulk' === tab) {

				$('#tkpe-bulk-tab').prop('hidden', false);
				$('#tkpe-quick-tab').prop('hidden', true);

			} else if ('quick' === tab) {

				$('#tkpe-bulk-tab').prop('hidden', true);
				$('#tkpe-quick-tab').prop('hidden', false);

			}

		}
	);


	/**
 * Refresh one product after Quick Edit update.
 *
 * @param {Object} event Event object.
 * @param {number} productId Product ID.
 */
	$(document).on(
		'tkpe:refresh-product',
		function (event, productId) {

			if (!productId) {
				return;
			}

			tkpe_load_selected_product(productId);
		}
	);

});