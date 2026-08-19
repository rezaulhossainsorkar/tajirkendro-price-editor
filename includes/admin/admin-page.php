<?php
/**
 * KTPE admin page.
 *
 * @package TajirKendro_Price_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Render KTPE admin page.
 */
function ktpe_render_admin_page() {

	require KTPE_PLUGIN_PATH . 'admin/views/header.php';
	require KTPE_PLUGIN_PATH . 'admin/views/filters.php';
	require KTPE_PLUGIN_PATH . 'admin/views/product-table.php';
	require KTPE_PLUGIN_PATH . 'admin/views/price-editor.php';
	require KTPE_PLUGIN_PATH . 'admin/views/footer.php';

}