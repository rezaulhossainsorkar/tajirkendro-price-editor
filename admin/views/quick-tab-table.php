<?php
/**
 * TKPE quick edit table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div
	id="tkpe-quick-tab"
	class="tkpe-product-tab"
	hidden
>

	<div class="tkpe-tab-content">

		<div class="tkpe-table-wrapper">

			<table class="widefat striped tkpe-product-table">

				<thead>

					<tr>

						<th>
							<?php esc_html_e( 'Product', 'tajirkendro-price-editor' ); ?>
						</th>

						<th>
							<?php esc_html_e( 'Type', 'tajirkendro-price-editor' ); ?>
						</th>

						<th>
							<?php esc_html_e( 'Status', 'tajirkendro-price-editor' ); ?>
						</th>

						<th>
							<?php esc_html_e( 'Stock', 'tajirkendro-price-editor' ); ?>
						</th>

						<th>
							<?php esc_html_e( 'Price', 'tajirkendro-price-editor' ); ?>
						</th>

						<th>
							<?php esc_html_e( 'Actions', 'tajirkendro-price-editor' ); ?>
						</th>

					</tr>

				</thead>

				<tbody id="tkpe-quick-products">

				</tbody>

			</table>

		</div>

	</div>

</div>

<script>
jQuery(document).ready(function ($) {

	'use strict';

	$(document).on(
		'click',
		'.tkpe-tab-button[data-tab="quick"]',
		function () {

			var search = $.trim(
				$('#tkpe-search').val()
			);

			var category = $('#tkpe-category').val();
			var type = $('#tkpe-type').val();
			var stockStatus = $('#tkpe-stock-status').val();
			var status = $('#tkpe-status').val();


			/*
			 * If a search value exists,
			 * refresh using the current search.
			 */
			if (search) {

				$('#tkpe-search-form').trigger('submit');

				return;
			}


			/*
			 * Otherwise refresh using the
			 * current filter configuration.
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
	);

});
</script>