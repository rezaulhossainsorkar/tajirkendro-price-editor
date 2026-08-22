jQuery(document).ready(function ($) {

	'use strict';

	var bulkRequest = null;


	/**
	 * Select all bulk-edit products.
	 */
	$(document).on(
		'change',
		'#tkpe-select-all',
		function () {

			var checked = $(this).prop('checked');

			$('#tkpe-bulk-products')
				.find('.tkpe-product-checkbox')
				.prop('checked', checked);

			tkpe_update_bulk_selection();
		}
	);


	/**
	 * Individual product selection.
	 */
	$(document).on(
		'change',
		'#tkpe-bulk-products .tkpe-product-checkbox',
		function () {

			tkpe_update_bulk_selection();
		}
	);


	/**
	 * Update bulk selection state.
	 */
	function tkpe_update_bulk_selection() {

		var $checkboxes = $(
			'#tkpe-bulk-products .tkpe-product-checkbox'
		);

		var total = $checkboxes.length;

		var selected = $checkboxes.filter(
			':checked'
		).length;


		$('#tkpe-select-all').prop(
			'checked',
			total > 0 && selected === total
		);


		$('#tkpe-select-all').prop(
			'indeterminate',
			selected > 0 && selected < total
		);


		$('#tkpe-bulk-selected-count').text(
			selected
		);
	}


	/**
	 * Apply bulk pricing.
	 */
	$(document).on(
		'click',
		'#tkpe-apply-bulk-pricing',
		function () {

			var $button = $(this);

			var productIds = [];


			/*
			 * Collect selected products.
			 */
			$('#tkpe-bulk-products')
				.find('.tkpe-product-checkbox:checked')
				.each(function () {

					productIds.push(
						parseInt(
							$(this).val(),
							10
						)
					);

				});


			/*
			 * Validate product selection.
			 */
			if (!productIds.length) {

				tkpe_show_bulk_result(
					'error',
					'Please select at least one product.'
				);

				return;
			}


			/*
			 * Get pricing values.
			 */
			var priceType = $(
				'#tkpe-bulk-price-type'
			).val();

			var method = $(
				'#tkpe-bulk-pricing-method'
			).val();

			var value = $(
				'#tkpe-bulk-pricing-value'
			).val();


			/*
			 * Validate price type.
			 *
			 * "both" is now a valid option.
			 */
			if (
				'regular' !== priceType &&
				'sale' !== priceType &&
				'both' !== priceType
			) {

				tkpe_show_bulk_result(
					'error',
					'Please select a valid price type.'
				);

				return;
			}


			/*
			 * Validate pricing method.
			 */
			if ('' === method) {

				tkpe_show_bulk_result(
					'error',
					'Please select a pricing rule.'
				);

				return;
			}


			/*
			 * Validate pricing value.
			 */
			if ('' === value) {

				tkpe_show_bulk_result(
					'error',
					'Please enter a pricing value.'
				);

				return;
			}


			if (isNaN(parseFloat(value))) {

				tkpe_show_bulk_result(
					'error',
					'Please enter a valid pricing value.'
				);

				return;
			}


			if (parseFloat(value) < 0) {

				tkpe_show_bulk_result(
					'error',
					'Please enter a valid pricing value.'
				);

				return;
			}


			/*
			 * Abort any previous request.
			 */
			if (bulkRequest) {
				bulkRequest.abort();
			}


			/*
			 * Disable the button while the request
			 * is being processed.
			 */
			$button.prop(
				'disabled',
				true
			);


			tkpe_show_bulk_result(
				'loading',
				'Applying pricing to selected products...'
			);


			/*
			 * Send bulk pricing request.
			 */
			bulkRequest = $.ajax({

				url: tkpeAdmin.ajaxUrl,

				type: 'POST',

				data: {
					action: 'tkpe_apply_bulk_pricing',
					nonce: tkpeAdmin.nonce,
					product_ids: productIds,
					price_type: priceType,
					method: method,
					value: value
				},


				success: function (response) {

					if (!response.success) {

						tkpe_show_bulk_result(
							'error',
							response.data.message ||
							'Unable to apply bulk pricing.'
						);

						return;
					}


					var updated = response.data.updated || [];
					var failed = response.data.failed || [];


					/*
					 * Update the visible table rows.
					 */
					tkpe_update_bulk_table_products(
						response.data.products || []
					);


					/*
					 * Display the result.
					 */
					tkpe_render_bulk_result(
						updated,
						failed
					);


					/*
					 * Refresh the table so filters,
					 * search and pagination remain in sync.
					 */
					$('#tkpe-filter-form').trigger(
						'submit'
					);

				},


				error: function (xhr, status) {

					if ('abort' === status) {
						return;
					}


					/*
					 * Try to use the server's error message
					 * when one is available.
					 */
					var message =
						'Unable to apply bulk pricing.';


					if (
						xhr.responseJSON &&
						xhr.responseJSON.data &&
						xhr.responseJSON.data.message
					) {

						message =
							xhr.responseJSON.data.message;
					}


					tkpe_show_bulk_result(
						'error',
						message
					);

				},


				complete: function () {

					$button.prop(
						'disabled',
						false
					);

					bulkRequest = null;

				}

			});

		}
	);


	/**
	 * Update table rows after bulk pricing.
	 *
	 * @param {Array} products Updated products.
	 */
	function tkpe_update_bulk_table_products(products) {

		$.each(
			products,
			function (index, product) {

				var $row = $(
					'#tkpe-bulk-products'
				).find(
					'tr[data-product-id="' +
					product.id +
					'"]'
				);


				if (!$row.length) {
					return;
				}


				/*
				 * Rebuild only the price cell.
				 *
				 * This assumes the table uses the same
				 * price-cell structure as Quick Edit.
				 */
				var $priceCell = $row.find(
					'.tkpe-price-cell'
				);


				if (
					$priceCell.length &&
					typeof tkpe_build_price_cell === 'function'
				) {

					$priceCell.replaceWith(
						tkpe_build_price_cell(product)
					);

				}

			}
		);

	}


	/**
	 * Render bulk result.
	 *
	 * @param {Array} updated Updated products.
	 * @param {Array} failed Failed products.
	 */
	function tkpe_render_bulk_result(
		updated,
		failed
	) {

		var $result = $(
			'#tkpe-bulk-result'
		);


		$result.empty().prop(
			'hidden',
			false
		);


		$result.append(
			$('<p>', {
				text:
					'Updated: ' +
					updated.length +
					' product(s).'
			})
		);


		if (!failed.length) {

			$result.removeClass(
				'tkpe-bulk-result-error'
			);

			$result.addClass(
				'tkpe-bulk-result-success'
			);

			return;
		}


		$result.removeClass(
			'tkpe-bulk-result-success'
		);

		$result.addClass(
			'tkpe-bulk-result-error'
		);


		$result.append(
			$('<p>', {
				text:
					'Could not update ' +
					failed.length +
					' product(s):'
			})
		);


		var $list = $('<ul>');


		$.each(
			failed,
			function (index, item) {

				$list.append(
					$('<li>').append(
						$('<strong>', {
							text: item.name + ': '
						}),
						document.createTextNode(
							item.message
						)
					)
				);

			}
		);


		$result.append($list);

	}


	/**
	 * Show a simple bulk result message.
	 *
	 * @param {string} type Message type.
	 * @param {string} message Message.
	 */
	function tkpe_show_bulk_result(
		type,
		message
	) {

		var $result = $(
			'#tkpe-bulk-result'
		);


		$result
			.removeClass(
				'tkpe-bulk-result-success ' +
				'tkpe-bulk-result-error ' +
				'tkpe-bulk-result-loading'
			)
			.addClass(
				'tkpe-bulk-result-' + type
			)
			.text(message)
			.prop(
				'hidden',
				false
			);

	}

});