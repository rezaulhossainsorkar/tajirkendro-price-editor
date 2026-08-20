<?php
/**
 * KTPE product filters.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="tkpe-filter-panel">
	<div class="tkpe-filter-grid">

		<div class="tkpe-filter-field tkpe-search-field">
			<label for="tkpe-search">
				<?php esc_html_e( 'Search products', 'tajirkendro-price-editor' ); ?>
			</label>

			<input
				type="search"
				id="tkpe-search"
				class="regular-text"
				placeholder="<?php esc_attr_e( 'Search by product name, SKU, or keyword…', 'tajirkendro-price-editor' ); ?>"
				autocomplete="off"
			>
		</div>

		<div class="tkpe-filter-field">
			<label for="tkpe-category">
				<?php esc_html_e( 'Category', 'tajirkendro-price-editor' ); ?>
			</label>

			<select id="tkpe-category">
				<option value="">
					<?php esc_html_e( 'All categories', 'tajirkendro-price-editor' ); ?>
				</option>

				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->slug ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="tkpe-filter-field">
			<label for="tkpe-type">
				<?php esc_html_e( 'Product Type', 'tajirkendro-price-editor' ); ?>
			</label>

			<select id="tkpe-type">
				<option value="">
					<?php esc_html_e( 'All product types', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="simple">
					<?php esc_html_e( 'Simple', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="variable">
					<?php esc_html_e( 'Variable', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="grouped">
					<?php esc_html_e( 'Grouped', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="external">
					<?php esc_html_e( 'External/Affiliate', 'tajirkendro-price-editor' ); ?>
				</option>
			</select>
		</div>

		<div class="tkpe-filter-field">
			<label for="tkpe-stock-status">
				<?php esc_html_e( 'Stock Status', 'tajirkendro-price-editor' ); ?>
			</label>

			<select id="tkpe-stock-status">
				<option value="">
					<?php esc_html_e( 'All stock statuses', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="instock">
					<?php esc_html_e( 'In stock', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="outofstock">
					<?php esc_html_e( 'Out of stock', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="onbackorder">
					<?php esc_html_e( 'On backorder', 'tajirkendro-price-editor' ); ?>
				</option>
			</select>
		</div>

		<div class="tkpe-filter-field">
			<label for="tkpe-status">
				<?php esc_html_e( 'Product Status', 'tajirkendro-price-editor' ); ?>
			</label>

			<select id="tkpe-status">
				<option value="">
					<?php esc_html_e( 'All statuses', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="publish">
					<?php esc_html_e( 'Published', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="draft">
					<?php esc_html_e( 'Draft', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="pending">
					<?php esc_html_e( 'Pending', 'tajirkendro-price-editor' ); ?>
				</option>

				<option value="private">
					<?php esc_html_e( 'Private', 'tajirkendro-price-editor' ); ?>
				</option>
			</select>
		</div>

		<div class="tkpe-filter-field">
			<label for="tkpe-per-page">
				<?php esc_html_e( 'Products per page', 'tajirkendro-price-editor' ); ?>
			</label>

			<select id="tkpe-per-page">
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="30">30</option>
				<option value="50">50</option>
			</select>
		</div>

	</div>
</div>