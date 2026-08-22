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