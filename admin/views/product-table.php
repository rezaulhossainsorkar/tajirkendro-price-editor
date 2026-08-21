<?php
/**
 * TKPE product table.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tkpe-products-section">

	<div class="tkpe-table-toolbar">
		<div
			id="tkpe-results-summary"
			class="tkpe-results-summary"
			aria-live="polite"
		></div>

		<div
			id="tkpe-loading"
			class="tkpe-loading"
			hidden
			aria-live="polite"
		>
			<span class="spinner is-active"></span>
			<span>
				<?php esc_html_e( 'Loading products…', 'tajirkendro-price-editor' ); ?>
			</span>
		</div>
	</div>

	<div class="tkpe-table-wrapper">

		<table class="wp-list-table widefat fixed striped tkpe-product-table">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input
							type="checkbox"
							id="tkpe-select-all"
							aria-label="<?php esc_attr_e( 'Select all products on this page', 'tajirkendro-price-editor' ); ?>"
						>
					</td>

					<th scope="col">
						<?php esc_html_e( 'Product', 'tajirkendro-price-editor' ); ?>
					</th>

					<th scope="col">
						<?php esc_html_e( 'Category', 'tajirkendro-price-editor' ); ?>
					</th>

					<th scope="col">
						<?php esc_html_e( 'Type', 'tajirkendro-price-editor' ); ?>
					</th>

					<th scope="col">
						<?php esc_html_e( 'Status', 'tajirkendro-price-editor' ); ?>
					</th>

					<th scope="col">
						<?php esc_html_e( 'Stock', 'tajirkendro-price-editor' ); ?>
					</th>

					<th scope="col">
						<?php esc_html_e( 'Price', 'tajirkendro-price-editor' ); ?>
					</th>
				</tr>
			</thead>

			<tbody id="tkpe-product-rows">
				<tr class="tkpe-empty-row">
					<td colspan="7">
						<?php esc_html_e( 'Loading products…', 'tajirkendro-price-editor' ); ?>
					</td>
				</tr>
			</tbody>
		</table>

	</div>

	<div
		id="tkpe-empty-state"
		class="tkpe-empty-state"
		hidden
	>
		<p>
			<?php esc_html_e( 'No products found.', 'tajirkendro-price-editor' ); ?>
		</p>
	</div>

	<nav
		id="tkpe-pagination"
		class="tkpe-pagination"
		aria-label="<?php esc_attr_e( 'Product pagination', 'tajirkendro-price-editor' ); ?>"
	></nav>

</div>