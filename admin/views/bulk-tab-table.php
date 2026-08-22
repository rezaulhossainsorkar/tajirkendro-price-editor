<?php
/**
 * TKPE bulk edit table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tkpe-product-tabs">

	<div class="tkpe-tab-navigation">

		<button
			type="button"
			class="tkpe-tab-button is-active"
			data-tab="bulk"
		>
			<?php esc_html_e( 'Bulk Edit', 'tajirkendro-price-editor' ); ?>
		</button>

		<button
			type="button"
			class="tkpe-tab-button"
			data-tab="quick"
		>
			<?php esc_html_e( 'Quick Edit', 'tajirkendro-price-editor' ); ?>
		</button>

	</div>


	<div
		id="tkpe-bulk-tab"
		class="tkpe-product-tab"
	>

		<div class="tkpe-tab-content">

			<div class="tkpe-bulk-rules">

				<h2>
					<?php esc_html_e( 'Bulk Pricing', 'tajirkendro-price-editor' ); ?>
				</h2>

				<p>
					<?php
					esc_html_e(
						'Configure a pricing rule and apply it to the selected products.',
						'tajirkendro-price-editor'
					);
					?>
				</p>


				<div class="tkpe-bulk-rule" style="display: flex; gap: 1rem; flex-wrap: wrap;">

					<div class="tkpe-bulk-field">

						<label for="tkpe-bulk-price-type">
							<?php esc_html_e( 'Price type', 'tajirkendro-price-editor' ); ?>
						</label>

						<select id="tkpe-bulk-price-type">

						<option value="regular">
							<?php esc_html_e( 'Regular price', 'tajirkendro-price-editor' ); ?>
						</option>

						<option value="sale">
							<?php esc_html_e( 'Sale price', 'tajirkendro-price-editor' ); ?>
						</option>

						<option value="both">
							<?php esc_html_e( 'Both prices', 'tajirkendro-price-editor' ); ?>
						</option>

						</select>

					</div>


					<div class="tkpe-bulk-field">

						<label for="tkpe-bulk-pricing-method">
							<?php esc_html_e( 'Pricing rule', 'tajirkendro-price-editor' ); ?>
						</label>

						<select id="tkpe-bulk-pricing-method">

							<option value="set">
								<?php esc_html_e( 'Set new price', 'tajirkendro-price-editor' ); ?>
							</option>

							<option value="increase_percentage">
								<?php esc_html_e( 'Increase by percentage', 'tajirkendro-price-editor' ); ?>
							</option>

							<option value="decrease_percentage">
								<?php esc_html_e( 'Decrease by percentage', 'tajirkendro-price-editor' ); ?>
							</option>

							<option value="increase_fixed">
								<?php esc_html_e( 'Increase by fixed amount', 'tajirkendro-price-editor' ); ?>
							</option>

							<option value="decrease_fixed">
								<?php esc_html_e( 'Decrease by fixed amount', 'tajirkendro-price-editor' ); ?>
							</option>

						</select>

					</div>


					<div class="tkpe-bulk-field">

						<label for="tkpe-bulk-pricing-value">
							<?php esc_html_e( 'Value', 'tajirkendro-price-editor' ); ?>
						</label>

						<input
							type="number"
							id="tkpe-bulk-pricing-value"
							step="0.01"
							min="0"
							placeholder="0.00"
						>

					</div>

				</div>


				<div class="tkpe-bulk-actions">

					<span class="tkpe-bulk-selected">

						<?php
						esc_html_e(
							'Selected:',
							'tajirkendro-price-editor'
						);
						?>

						<strong id="tkpe-bulk-selected-count">0</strong>

					</span>


					<button
						type="button"
						class="button button-primary"
						id="tkpe-apply-bulk-pricing"
					>
						<?php
						esc_html_e(
							'Apply to selected products',
							'tajirkendro-price-editor'
						);
						?>
					</button>

				</div>


				<div
					id="tkpe-bulk-result"
					class="tkpe-bulk-result"
					hidden
				></div>

			</div>


			<div class="tkpe-table-wrapper">

				<table class="widefat striped tkpe-product-table">

					<thead>

						<tr>

							<th class="check-column" style="padding:6px 0 20px !important;">

								<input style="margin-top:13px;"
									type="checkbox"
									id="tkpe-select-all"
								>

							</th>

							<th>
								<?php
								esc_html_e(
									'Product',
									'tajirkendro-price-editor'
								);
								?>
							</th>

							<th>
								<?php
								esc_html_e(
									'Type',
									'tajirkendro-price-editor'
								);
								?>
							</th>

							<th>
								<?php
								esc_html_e(
									'Status',
									'tajirkendro-price-editor'
								);
								?>
							</th>

							<th>
								<?php
								esc_html_e(
									'Stock',
									'tajirkendro-price-editor'
								);
								?>
							</th>

							<th>
								<?php
								esc_html_e(
									'Price',
									'tajirkendro-price-editor'
								);
								?>
							</th>

						</tr>

					</thead>

					<tbody id="tkpe-bulk-products">

					</tbody>

				</table>

			</div>

		</div>

	</div>

</div>


<script>
jQuery(document).ready(function ($) {

	'use strict';

	/**
	 * Refresh Bulk Edit table when the Bulk Edit tab
	 * is opened.
	 *
	 * The existing search/filter forms are responsible
	 * for making the AJAX request and rendering the table.
	 */
	$(document).on(
		'click',
		'.tkpe-tab-button[data-tab="bulk"]',
		function () {

			var search = $.trim(
				$('#tkpe-search').val()
			);

			var category = $('#tkpe-category').val();
			var type = $('#tkpe-type').val();
			var stockStatus = $('#tkpe-stock-status').val();
			var status = $('#tkpe-status').val();


			/*
			 * If a search value exists, refresh using
			 * the current search configuration.
			 */
			if (search) {

				$('#tkpe-search-form').trigger('submit');

				return;
			}


			/*
			 * Otherwise refresh using the current
			 * filter configuration.
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