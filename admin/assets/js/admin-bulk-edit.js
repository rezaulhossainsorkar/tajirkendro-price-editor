jQuery(document).ready(function ($) {

	'use strict';


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

});