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
					<?php esc_html_e( 'Bulk Edit Rules', 'tajirkendro-price-editor' ); ?>
				</h2>

				<p>
					<?php esc_html_e( 'Bulk pricing rules will be added here.', 'tajirkendro-price-editor' ); ?>
				</p>

			</div>


			<div class="tkpe-table-wrapper">

				<table class="widefat striped tkpe-product-table">

					<thead>

						<tr>

							<th class="check-column">
								<input
									type="checkbox"
									id="tkpe-select-all"
								>
							</th>

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

						</tr>

					</thead>

					<tbody id="tkpe-bulk-products">

					</tbody>

				</table>

			</div>

		</div>

	</div>

</div>