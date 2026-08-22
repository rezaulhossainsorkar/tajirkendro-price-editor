jQuery(document).ready(function ($) {

	'use strict';

	var editorRequest = null;
	var updateRequest = null;
	var deleteRequest = null;


	/**
	 * Pricing methods.
	 */
	var pricingMethods = [
		{
			value: 'set',
			label: 'Set new price'
		},
		{
			value: 'increase_percentage',
			label: 'Increase by percentage'
		},
		{
			value: 'decrease_percentage',
			label: 'Decrease by percentage'
		},
		{
			value: 'increase_fixed',
			label: 'Increase by fixed amount'
		},
		{
			value: 'decrease_fixed',
			label: 'Decrease by fixed amount'
		}
	];


	/**
	 * View product.
	 */
	$(document).on(
		'click',
		'.tkpe-view-product',
		function () {

			var productId = $(this).data('product-id');

			tkpe_view_product(productId);

		}
	);


	/**
	 * Edit product.
	 */
	$(document).on(
		'click',
		'.tkpe-edit-product',
		function () {

			var productId = $(this).data('product-id');
			var $row = $(this).closest('tr');

			tkpe_open_editor(
				productId,
				$row
			);

		}
	);


	/**
	 * Delete product.
	 */
	$(document).on(
		'click',
		'.tkpe-delete-product',
		function () {

			var productId = $(this).data('product-id');
			var $row = $(this).closest('tr');

			tkpe_delete_product(
				productId,
				$row
			);

		}
	);


	/**
	 * View product.
	 *
	 * @param {number} productId Product ID.
	 */
	function tkpe_view_product(productId) {

		tkpe_close_view_modal();

		var $overlay = $('<div>', {
			class: 'tkpe-view-overlay'
		});

		var $modal = $('<div>', {
			class: 'tkpe-view-modal'
		});

		$modal.append(
			$('<div>', {
				class: 'tkpe-view-loading',
				text: 'Loading product...'
			})
		);

		$overlay.append($modal);

		$('body').append($overlay);

		$.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_get_product_editor',
				nonce: tkpeAdmin.nonce,
				product_id: productId
			},

			success: function (response) {

				if (!response.success) {

					$modal.html(
						$('<div>', {
							class: 'tkpe-error',
							text: response.data.message
						})
					);

					return;
				}

				tkpe_render_product_view(
					$modal,
					response.data.product
				);

			},

			error: function () {

				$modal.html(
					$('<div>', {
						class: 'tkpe-error',
						text: 'Unable to load the product.'
					})
				);

			}

		});

	}


	/**
	 * Render product view modal.
	 *
	 * @param {jQuery} $modal Modal.
	 * @param {Object} product Product.
	 */
	function tkpe_render_product_view($modal, product) {

		var $header = $('<div>', {
			class: 'tkpe-view-header'
		});

		$header.append(
			$('<h2>', {
				text: product.name
			})
		);

		$header.append(
			$('<button>', {
				type: 'button',
				class: 'button-link tkpe-close-view',
				text: '×'
			})
		);

		var $content = $('<div>', {
			class: 'tkpe-view-content'
		});

		if (product.sku) {

			$content.append(
				$('<p>').append(
					$('<strong>', {
						text: 'SKU: '
					}),
					document.createTextNode(product.sku)
				)
			);

		}

		$content.append(
			$('<p>').append(
				$('<strong>', {
					text: 'Type: '
				}),
				document.createTextNode(product.type)
			)
		);

		$content.append(
			$('<p>').append(
				$('<strong>', {
					text: 'Regular price: '
				}),
				document.createTextNode(
					product.regular_price || '—'
				)
			)
		);

		$content.append(
			$('<p>').append(
				$('<strong>', {
					text: 'Sale price: '
				}),
				document.createTextNode(
					product.sale_price || '—'
				)
			)
		);

		if (
			'variable' === product.type &&
			$.isArray(product.variations)
		) {

			var $variations = $('<div>', {
				class: 'tkpe-view-variations'
			});

			$variations.append(
				$('<h3>', {
					text: 'Variations'
				})
			);

			$.each(
				product.variations,
				function (index, variation) {

					var attributes = [];

					$.each(
						variation.attributes,
						function (attributeIndex, attribute) {

							attributes.push(
								attribute.name + ': ' + attribute.value
							);

						}
					);

					var $variation = $('<div>', {
						class: 'tkpe-view-variation'
					});

					$variation.append(
						$('<strong>', {
							text: attributes.join(', ')
						})
					);

					$variation.append(
						$('<span>', {
							text:
								'Regular: ' +
								(variation.regular_price || '—') +
								' | Sale: ' +
								(variation.sale_price || '—')
						})
					);

					$variations.append($variation);

				}
			);

			$content.append($variations);

		}

		$modal.empty()
			.append($header)
			.append($content);

	}


	/**
	 * Close view modal.
	 */
	$(document).on(
		'click',
		'.tkpe-close-view',
		function () {

			tkpe_close_view_modal();

		}
	);


	/**
	 * Close modal when clicking overlay.
	 */
	$(document).on(
		'click',
		'.tkpe-view-overlay',
		function (event) {

			if (
				$(event.target).hasClass(
					'tkpe-view-overlay'
				)
			) {

				tkpe_close_view_modal();

			}

		}
	);


	/**
	 * Close view modal.
	 */
	function tkpe_close_view_modal() {

		$('.tkpe-view-overlay').remove();

	}


	/**
	 * Open editor.
	 *
	 * @param {number} productId Product ID.
	 * @param {jQuery} $row Product row.
	 */
	function tkpe_open_editor(productId, $row) {

		if ($row.next('.tkpe-editor-row').length) {
			return;
		}

		if (editorRequest) {
			editorRequest.abort();
		}

		var $editorRow = $('<tr>', {
			class: 'tkpe-editor-row'
		});

		var $editorCell = $('<td>', {
			colspan: 6,
			class: 'tkpe-editor-cell'
		});

		$editorCell.append(
			$('<div>', {
				class: 'tkpe-editor-loading',
				text: 'Loading editor...'
			})
		);

		$editorRow.append($editorCell);

		$row.after($editorRow);

		editorRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_get_product_editor',
				nonce: tkpeAdmin.nonce,
				product_id: productId
			},

			success: function (response) {

				if (!response.success) {

					$editorCell.html(
						$('<div>', {
							class: 'tkpe-error',
							text: response.data.message
						})
					);

					return;
				}

				tkpe_render_editor(
					$editorCell,
					response.data.product
				);

			},

			error: function () {

				$editorCell.html(
					$('<div>', {
						class: 'tkpe-error',
						text: 'Unable to load the editor.'
					})
				);

			},

			complete: function () {

				editorRequest = null;

			}

		});

	}


	/**
	 * Render editor.
	 *
	 * @param {jQuery} $container Editor container.
	 * @param {Object} product Product.
	 */
	function tkpe_render_editor($container, product) {

		var $editor = $('<div>', {
			class: 'tkpe-editor',
			'data-product-id': product.id,
			'data-product-type': product.type
		});

		$editor.append(
			$('<h3>', {
				text: 'Edit: ' + product.name
			})
		);

		if ('variable' === product.type) {

			tkpe_render_variable_editor(
				$editor,
				product
			);

		} else {

			tkpe_render_simple_editor(
				$editor,
				product
			);

		}

		$container.empty().append($editor);

	}


	/**
	 * Render simple product editor.
	 *
	 * @param {jQuery} $editor Editor.
	 * @param {Object} product Product.
	 */
	function tkpe_render_simple_editor($editor, product) {

		var $regular = tkpe_price_rule_fields(
			'regular',
			product.regular_price
		);

		var $sale = tkpe_price_rule_fields(
			'sale',
			product.sale_price
		);

		var $actions = tkpe_editor_actions();

		$editor
			.append(
				$('<div>', {
					class: 'tkpe-editor-price-section'
				})
				.append(
					$('<h4>', {
						text: 'Regular Price'
					}),
					$regular
				)
			)
			.append(
				$('<div>', {
					class: 'tkpe-editor-price-section'
				})
				.append(
					$('<h4>', {
						text: 'Sale Price'
					}),
					$sale
				)
			)
			.append($actions);

	}


	/**
	 * Render variable product editor.
	 *
	 * Each variation has its own pricing configuration.
	 *
	 * @param {jQuery} $editor Editor.
	 * @param {Object} product Product.
	 */
	function tkpe_render_variable_editor($editor, product) {

		var $description = $('<p>', {
			class: 'tkpe-editor-description',
			text:
				'Configure pricing independently for each variation.'
		});

		var $variations = $('<div>', {
			class: 'tkpe-editor-variations'
		});

		$.each(
			product.variations || [],
			function (index, variation) {

				var $variation = $('<div>', {
					class: 'tkpe-editor-variation',
					'data-variation-id': variation.id
				});

				var attributes = [];

				$.each(
					variation.attributes,
					function (attributeIndex, attribute) {

						attributes.push(
							attribute.name + ': ' + attribute.value
						);

					}
				);

				$variation.append(
					$('<h4>', {
						text: attributes.join(' / ')
					})
				);

				$variation.append(
					tkpe_price_rule_fields(
						'regular',
						variation.regular_price
					)
				);

				$variation.append(
					tkpe_price_rule_fields(
						'sale',
						variation.sale_price
					)
				);

				$variations.append($variation);

			}
		);

		$editor
			.append($description)
			.append($variations)
			.append(tkpe_editor_actions());

	}


	/**
	 * Create price rule fields.
	 *
	 * @param {string} priceType Price type.
	 * @param {string} currentPrice Current price.
	 * @return {jQuery} Fields.
	 */
	function tkpe_price_rule_fields(priceType, currentPrice) {

		var $wrapper = $('<div>', {
			class: 'tkpe-price-rule',
			'data-price-type': priceType
		});

		var $method = $('<select>', {
			class: 'tkpe-price-method'
		});

		$method.append(
			$('<option>', {
				value: '',
				text: 'Do not change'
			})
		);

		$.each(
			pricingMethods,
			function (index, pricingMethod) {

				$method.append(
					$('<option>', {
						value: pricingMethod.value,
						text: pricingMethod.label
					})
				);

			}
		);

		var $value = $('<input>', {
			type: 'number',
			step: '0.01',
			min: '0',
			class: 'tkpe-price-value',
			value: ''
		});

		var $current = $('<span>', {
			class: 'tkpe-current-price',
			text:
				'Current: ' +
				(currentPrice || '—')
		});

		$wrapper
			.append(
				$('<label>', {
					text:
						(
							'regular' === priceType
								? 'Regular price'
								: 'Sale price'
						)
				})
			)
			.append($current)
			.append($method)
			.append($value);

		return $wrapper;

	}


	/**
	 * Editor actions.
	 *
	 * @return {jQuery} Actions.
	 */
	function tkpe_editor_actions() {

		var $actions = $('<div>', {
			class: 'tkpe-editor-actions'
		});

		$actions.append(
			$('<button>', {
				type: 'button',
				class: 'button tkpe-cancel-edit',
				text: 'Cancel'
			})
		);

		$actions.append(
			$('<button>', {
				type: 'button',
				class: 'button button-primary tkpe-apply-edit',
				text: 'Apply'
			})
		);

		return $actions;

	}


	/**
	 * Cancel editor.
	 */
	$(document).on(
		'click',
		'.tkpe-cancel-edit',
		function () {

			$(this)
				.closest('.tkpe-editor-row')
				.remove();

		}
	);


	/**
	 * Apply editor.
	 */
	$(document).on(
		'click',
		'.tkpe-apply-edit',
		function () {

			var $editor = $(this).closest('.tkpe-editor');
			var productId = $editor.data('product-id');
			var productType = $editor.data('product-type');
			var $button = $(this);

			$button.prop('disabled', true);

			if ('variable' === productType) {

				tkpe_apply_variable_editor(
					productId,
					$editor,
					$button
				);

			} else {

				tkpe_apply_simple_editor(
					productId,
					$editor,
					$button
				);

			}

		}
	);


	/**
	 * Apply simple editor.
	 *
	 * @param {number} productId Product ID.
	 * @param {jQuery} $editor Editor.
	 * @param {jQuery} $button Apply button.
	 */
	function tkpe_apply_simple_editor(
		productId,
		$editor,
		$button
	) {

		var pricing = {};

		$editor
			.find('.tkpe-price-rule')
			.each(function () {

				var priceType = $(this).data('price-type');

				var method = $(this)
					.find('.tkpe-price-method')
					.val();

				var value = $(this)
					.find('.tkpe-price-value')
					.val();

				pricing[priceType] = {
					method: method,
					value: value
				};

			});

		tkpe_update_product(
			productId,
			{
				pricing: pricing
			},
			$editor,
			$button
		);

	}


	/**
	 * Apply variable editor.
	 *
	 * @param {number} productId Product ID.
	 * @param {jQuery} $editor Editor.
	 * @param {jQuery} $button Apply button.
	 */
	function tkpe_apply_variable_editor(
		productId,
		$editor,
		$button
	) {

		var variations = [];

		$editor
			.find('.tkpe-editor-variation')
			.each(function () {

				var $variation = $(this);

				var variationId = $variation.data(
					'variation-id'
				);

				var pricing = {};

				$variation
					.find('.tkpe-price-rule')
					.each(function () {

						var priceType = $(this)
							.data('price-type');

						var method = $(this)
							.find('.tkpe-price-method')
							.val();

						var value = $(this)
							.find('.tkpe-price-value')
							.val();

						pricing[priceType] = {
							method: method,
							value: value
						};

					});

				variations.push({
					id: variationId,
					pricing: pricing
				});

			});

		tkpe_update_product(
			productId,
			{
				variations: variations
			},
			$editor,
			$button
		);

	}


	/**
	 * Update product.
	 *
	 * IMPORTANT:
	 * This does NOT refresh the product table.
	 *
	 * @param {number} productId Product ID.
	 * @param {Object} data Update data.
	 * @param {jQuery} $editor Editor.
	 * @param {jQuery} $button Apply button.
	 */
	function tkpe_update_product(
		productId,
		data,
		$editor,
		$button
	) {

		if (updateRequest) {
			updateRequest.abort();
		}

		var $editorRow = $editor.closest(
			'.tkpe-editor-row'
		);

		var $productRow = $editorRow.prev(
			'tr[data-product-id="' + productId + '"]'
		);

		var ajaxData = {
			action: 'tkpe_update_product',
			nonce: tkpeAdmin.nonce,
			product_id: productId
		};

		$.extend(
			ajaxData,
			data
		);

		updateRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: ajaxData,

			success: function (response) {

				if (!response.success) {

					window.alert(
						response.data.message ||
						'Unable to update the product.'
					);

					return;
				}


				/*
				 * The server returns the actual saved
				 * WooCommerce product.
				 */
				var product = response.data.product;


				/*
				 * Update ONLY the current Quick Edit
				 * row's price cell.
				 */
				if ($productRow.length) {

					tkpe_update_price_cell(
						$productRow,
						product
					);

				}


				/*
				 * Close only this editor.
				 */
				$editorRow.remove();

			},

			error: function (xhr, status) {

				/*
				 * Ignore an intentionally aborted request.
				 */
				if ('abort' === status) {
					return;
				}

				window.alert(
					'Unable to update the product.'
				);

			},

			complete: function () {

				$button.prop(
					'disabled',
					false
				);

				updateRequest = null;

			}

		});

	}


	/**
	 * Update only one Quick Edit row's price cell.
	 *
	 * No table refresh happens here.
	 *
	 * @param {jQuery} $row Product row.
	 * @param {Object} product Updated product.
	 */
	function tkpe_update_price_cell($row, product) {

		var $priceCell = $row.find(
			'.tkpe-price-cell'
		);

		if (!$priceCell.length) {
			return;
		}

		var $newPriceCell = tkpe_build_price_cell(
			product
		);

		$priceCell.replaceWith(
			$newPriceCell
		);

	}


	/**
	 * Build Quick Edit price cell.
	 *
	 * This intentionally lives in Quick Edit JS
	 * so this file does not depend on the private
	 * functions inside admin-products.js.
	 *
	 * @param {Object} product Product.
	 * @return {jQuery} Price cell.
	 */
	function tkpe_build_price_cell(product) {

		var $cell = $('<td>', {
			class: 'tkpe-price-cell'
		});

		if (product.regular_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-regular-price',
					text:
						'Regular: ' +
						product.regular_price
				})
			);

		}

		if (product.sale_price) {

			$cell.append(
				$('<div>', {
					class: 'tkpe-sale-price',
					text:
						'Sale: ' +
						product.sale_price
				})
			);

		}

		if (
			! product.regular_price &&
			! product.sale_price
		) {

			$cell.text('—');

		}

		return $cell;

	}


	/**
	 * Delete product.
	 *
	 * @param {number} productId Product ID.
	 * @param {jQuery} $row Product row.
	 */
	function tkpe_delete_product(
		productId,
		$row
	) {

		if (
			! window.confirm(
				'Are you sure you want to delete this product?'
			)
		) {
			return;
		}

		if (deleteRequest) {
			deleteRequest.abort();
		}

		var $button = $row.find(
			'.tkpe-delete-product'
		);

		$button.prop(
			'disabled',
			true
		);

		deleteRequest = $.ajax({

			url: tkpeAdmin.ajaxUrl,

			type: 'POST',

			data: {
				action: 'tkpe_delete_product',
				nonce: tkpeAdmin.nonce,
				product_id: productId
			},

			success: function (response) {

				if (!response.success) {

					window.alert(
						response.data.message
					);

					$button.prop(
						'disabled',
						false
					);

					return;
				}

				$row.fadeOut(
					150,
					function () {
						$row.remove();
					}
				);

			},

			error: function () {

				window.alert(
					'Unable to delete the product.'
				);

				$button.prop(
					'disabled',
					false
				);

			},

			complete: function () {

				deleteRequest = null;

			}

		});

	}

});