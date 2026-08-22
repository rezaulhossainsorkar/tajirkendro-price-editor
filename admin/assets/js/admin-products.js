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
	 * Get the currently active tab.
	 *
	 * Bulk Edit is used only as a fallback for the
	 * initial state.
	 *
	 * @return {string} Current tab.
	 */
	function tkpe_get_current_tab() {

		var tab = $(
			'.tkpe-tab-button.is-active'
		).data('tab');

		if ('quick' === tab) {
			return 'quick';
		}

		return 'bulk';
	}


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
		 * The current tab is preserved.
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
	 * The currently active tab is preserved.
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
		 * Keep whichever tab the user is currently on.
		 */
		var currentTab = tkpe_get_current_tab();

		if ('quick' === currentTab) {

			$('#tkpe-bulk-tab').prop('hidden', true);
			$('#tkpe-quick-tab').prop('hidden', false);

		} else {

			$('#tkpe-bulk-tab').prop('hidden', false);
			$('#tkpe-quick-tab').prop('hidden', true);

		}

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
		 * Clear the search field.
		 */
		$('#tkpe-search').val('');


		/*
		 * Reset displayed results while
		 * preserving the current tab.
		 */
		tkpe_reset_results();

	});


	/**
	 * Render product tables.
	 *
	 * The currently active tab remains active.
	 *
	 * @param {Array} products Products.
	 */
	function tkpe_render_products(products) {

		/*
		 * Remember the current tab before rendering.
		 */
		var currentTab = tkpe_get_current_tab();


		/*
		 * Render both table datasets.
		 */
		tkpe_render_bulk_products(products);
		tkpe_render_quick_products(products);


		/*
		 * Restore the user's current tab.
		 */
		if ('quick' === currentTab) {

			$('.tkpe-tab-button').removeClass('is-active');

			$('.tkpe-tab-button[data-tab="quick"]')
				.addClass('is-active');

			$('#tkpe-bulk-tab').prop('hidden', true);
			$('#tkpe-quick-tab').prop('hidden', false);

		} else {

			$('.tkpe-tab-button').removeClass('is-active');

			$('.tkpe-tab-button[data-tab="bulk"]')
				.addClass('is-active');

			$('#tkpe-bulk-tab').prop('hidden', false);
			$('#tkpe-quick-tab').prop('hidden', true);

		}

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


		/*
		 * Variable products use variation prices
		 * instead of a single parent price.
		 */
		if ('variable' === product.type) {

			$cell.append(
				tkpe_variation_price_preview(product)
			);

			return $cell;
		}


		/*
		 * Regular product price.
		 */
		if (product.regular_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-regular-price',
					text: 'Regular: ' + product.regular_price
				})
			);

		}


		/*
		 * Sale product price.
		 */
		if (product.sale_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-sale-price',
					text: 'Sale: ' + product.sale_price
				})
			);

		}


		/*
		 * Product has no price.
		 */
		if (
			! product.regular_price &&
			! product.sale_price
		) {

			$cell.text('—');

		}


		return $cell;
	}


	/**
	 * Build variable-product variation price preview.
	 *
	 * @param {Object} product Product.
	 * @return {jQuery} Variation price preview.
	 */
	function tkpe_variation_price_preview(product) {

		var $wrapper = $('<div>', {
			class: 'tkpe-variation-price-preview'
		});


		var $trigger = $('<button>', {
			type: 'button',
			class: 'button-link tkpe-show-variation-prices',
			text: 'Show variation prices'
		});


		var $card = $('<div>', {
			class: 'tkpe-variation-price-card',
			hidden: true
		});


		var $title = $('<strong>', {
			class: 'tkpe-variation-price-title',
			text: 'Variation prices'
		});


		var $list = $('<div>', {
			class: 'tkpe-variation-price-list'
		});


		/*
		 * No variations.
		 */
		if (
			! $.isArray(product.variations) ||
			! product.variations.length
		) {

			$list.append(
				$('<div>', {
					class: 'tkpe-no-variation-prices',
					text: 'No variation prices available.'
				})
			);

		} else {

			$.each(
				product.variations,
				function (index, variation) {

					var $item = $('<div>', {
						class: 'tkpe-variation-price-item'
					});


					var $attributes = $('<div>', {
						class: 'tkpe-variation-attributes'
					});


					var attributeText = [];


					$.each(
						variation.attributes || [],
						function (attributeIndex, attribute) {

							attributeText.push(
								attribute.name +
								': ' +
								attribute.value
							);

						}
					);


					if (attributeText.length) {

						$attributes.text(
							attributeText.join(' / ')
						);

					} else {

						$attributes.text(
							'Variation #' + variation.id
						);

					}


					var $prices = $('<div>', {
						class: 'tkpe-variation-prices'
					});


					if (variation.regular_price) {

						$prices.append(
							$('<span>', {
								class: 'tkpe-variation-regular-price',
								text:
									'Regular: ' +
									variation.regular_price
							})
						);

					}


					if (variation.sale_price) {

						$prices.append(
							$('<span>', {
								class: 'tkpe-variation-sale-price',
								text:
									'Sale: ' +
									variation.sale_price
							})
						);

					}


					if (
						! variation.regular_price &&
						! variation.sale_price
					) {

						$prices.append(
							$('<span>', {
								class: 'tkpe-variation-no-price',
								text: 'No price'
							})
						);

					}


					$item
						.append($attributes)
						.append($prices);


					$list.append($item);

				}
			);

		}


		$card
			.append($title)
			.append($list);


		$wrapper
			.append($trigger)
			.append($card);


		/*
		 * Show card.
		 */
		$wrapper.on(
			'mouseenter',
			function () {

				$card.prop('hidden', false);

			}
		);


		/*
		 * Hide card.
		 */
		$wrapper.on(
			'mouseleave',
			function () {

				$card.prop('hidden', true);

			}
		);


		/*
		 * Keyboard accessibility.
		 */
		$trigger.on(
			'focus',
			function () {

				$card.prop('hidden', false);

			}
		);


		$trigger.on(
			'blur',
			function () {

				setTimeout(
					function () {

						if (
							! $wrapper.find(':focus').length
						) {

							$card.prop('hidden', true);

						}

					},
					100
				);

			}
		);


		return $wrapper;
	}


	/**
	 * Show table loading state.
	 *
	 * The currently active tab determines which
	 * table is displayed while loading.
	 */
	function tkpe_show_loading() {

		var currentTab = tkpe_get_current_tab();


		/*
		 * Quick Edit is currently active.
		 */
		if ('quick' === currentTab) {

			$('.tkpe-tab-button').removeClass('is-active');

			$('.tkpe-tab-button[data-tab="quick"]')
				.addClass('is-active');

			$('#tkpe-bulk-tab').prop('hidden', true);
			$('#tkpe-quick-tab').prop('hidden', false);

			$('#tkpe-quick-products').html(
				'<tr><td colspan="6" class="tkpe-loading">Loading products...</td></tr>'
			);

			return;
		}


		/*
		 * Bulk Edit is currently active.
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
	 * Refresh the current table using the existing
	 * search/filter configuration.
	 */
	function tkpe_refresh_current_tab() {

		var search = $.trim(
			$('#tkpe-search').val()
		);

		var category = $('#tkpe-category').val();
		var type = $('#tkpe-type').val();
		var stockStatus = $('#tkpe-stock-status').val();
		var status = $('#tkpe-status').val();


		/*
		 * Search takes priority.
		 */
		if (search) {

			$('#tkpe-search-form').trigger('submit');

			return;
		}


		/*
		 * Otherwise use the current filters.
		 */
		if (
			category ||
			type ||
			stockStatus ||
			status
		) {

			$('#tkpe-filter-form').trigger('submit');

			return;
		}

	}


	/**
	 * Switch between tabs.
	 *
	 * Each time a tab is opened, refresh the table
	 * using the current search/filter configuration.
	 */
	$(document).on(
		'click',
		'.tkpe-tab-button',
		function () {

			var tab = $(this).data('tab');


			/*
			 * Activate the clicked tab immediately.
			 */
			$('.tkpe-tab-button').removeClass('is-active');

			$(this).addClass('is-active');


			/*
			 * Show the selected tab.
			 */
			if ('bulk' === tab) {

				$('#tkpe-bulk-tab').prop('hidden', false);
				$('#tkpe-quick-tab').prop('hidden', true);

			} else if ('quick' === tab) {

				$('#tkpe-bulk-tab').prop('hidden', true);
				$('#tkpe-quick-tab').prop('hidden', false);

			}


			/*
			 * Refresh the currently opened tab.
			 *
			 * The active tab has already been changed,
			 * so AJAX loading will preserve this tab.
			 */
			tkpe_refresh_current_tab();

		}
	);


	/**
	 * Toggle variation price card.
	 */
	$(document).on(
		'click',
		'.tkpe-show-variation-prices',
		function (event) {

			event.preventDefault();
			event.stopPropagation();

			var $preview = $(this).closest(
				'.tkpe-variation-price-preview'
			);


			/*
			 * Close any other open variation cards.
			 */
			$('.tkpe-variation-price-preview.is-open')
				.not($preview)
				.removeClass('is-open');


			/*
			 * Toggle this card.
			 */
			$preview.toggleClass('is-open');

		}
	);


	/**
	 * Close variation price card when clicking
	 * outside the trigger/card.
	 */
	$(document).on(
		'click',
		function (event) {

			if (
				$(event.target).closest(
					'.tkpe-variation-price-preview'
				).length
			) {
				return;
			}

			$('.tkpe-variation-price-preview.is-open')
				.removeClass('is-open');

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