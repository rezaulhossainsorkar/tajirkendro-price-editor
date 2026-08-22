<?php
/**
 * TKPE search and filter interface.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filter_options = tkpe_get_filter_options();
?>

<div class="tkpe-controls">

	<div class="tkpe-filter-section">

		<form id="tkpe-filter-form">

			<div class="tkpe-filter-grid">

				<div class="tkpe-filter-field">

					<label for="tkpe-category">
						<?php esc_html_e( 'Category', 'tajirkendro-price-editor' ); ?>
					</label>

					<select id="tkpe-category" name="category">

						<option value="">
							<?php esc_html_e( 'All Categories', 'tajirkendro-price-editor' ); ?>
						</option>

						<?php foreach ( $filter_options['categories'] as $category ) : ?>

							<option value="<?php echo esc_attr( $category->term_id ); ?>">
								<?php echo esc_html( $category->name ); ?>
							</option>

						<?php endforeach; ?>

					</select>

				</div>


				<div class="tkpe-filter-field">

					<label for="tkpe-type">
						<?php esc_html_e( 'Product Type', 'tajirkendro-price-editor' ); ?>
					</label>

					<select id="tkpe-type" name="type">

						<option value="">
							<?php esc_html_e( 'All Product Types', 'tajirkendro-price-editor' ); ?>
						</option>

						<?php foreach ( $filter_options['types'] as $type_key => $type_name ) : ?>

							<option value="<?php echo esc_attr( $type_key ); ?>">
								<?php echo esc_html( $type_name ); ?>
							</option>

						<?php endforeach; ?>

					</select>

				</div>


				<div class="tkpe-filter-field">

					<label for="tkpe-stock-status">
						<?php esc_html_e( 'Stock Status', 'tajirkendro-price-editor' ); ?>
					</label>

					<select id="tkpe-stock-status" name="stock_status">

						<option value="">
							<?php esc_html_e( 'All Stock Statuses', 'tajirkendro-price-editor' ); ?>
						</option>

						<?php foreach ( $filter_options['stock_statuses'] as $status_key => $status_name ) : ?>

							<option value="<?php echo esc_attr( $status_key ); ?>">
								<?php echo esc_html( $status_name ); ?>
							</option>

						<?php endforeach; ?>

					</select>

				</div>


				<div class="tkpe-filter-field">

					<label for="tkpe-status">
						<?php esc_html_e( 'Product Status', 'tajirkendro-price-editor' ); ?>
					</label>

					<select id="tkpe-status" name="status">

						<option value="">
							<?php esc_html_e( 'All Statuses', 'tajirkendro-price-editor' ); ?>
						</option>

						<?php foreach ( $filter_options['statuses'] as $status_key => $status_name ) : ?>

							<option value="<?php echo esc_attr( $status_key ); ?>">
								<?php echo esc_html( $status_name ); ?>
							</option>

						<?php endforeach; ?>

					</select>

				</div>

			</div>

		</form>

	</div>


	<div class="tkpe-controls-actions">

		<div class="tkpe-filter-actions">

			<button
				type="submit"
				form="tkpe-filter-form"
				class="button button-primary"
			>
				<?php esc_html_e( 'Apply Filters', 'tajirkendro-price-editor' ); ?>
			</button>

			<button
				type="button"
				id="tkpe-reset-filters"
				class="button"
			>
				<?php esc_html_e( 'Reset', 'tajirkendro-price-editor' ); ?>
			</button>

		</div>


		<div class="tkpe-search-section">

			<form id="tkpe-search-form" class="tkpe-search-form">

				<div class="tkpe-search-field">

					<label for="tkpe-search">
						<?php esc_html_e( 'Search Products', 'tajirkendro-price-editor' ); ?>
					</label>

					<div class="tkpe-search-input-wrapper">

						<input
							type="search"
							id="tkpe-search"
							name="search"
							placeholder="<?php esc_attr_e( 'Search product name or SKU...', 'tajirkendro-price-editor' ); ?>"
							autocomplete="off"
						>

						<div
							id="tkpe-search-suggestions"
							class="tkpe-search-suggestions"
							hidden
						></div>

					</div>

				</div>

				<button
					type="submit"
					class="button button-primary"
				>
					<?php esc_html_e( 'Search', 'tajirkendro-price-editor' ); ?>
				</button>

			</form>

		</div>

	</div>

</div>