<?php
/**
 * Audit Log Admin.
 *
 * @package HM\Platform\Audit_Log
 */

namespace HM\Platform\Audit_Log\Admin;

use function HM\Platform\Audit_Log\get_item;

/**
 * Bootstrap the admin functionality.
 *
 * @return void
 */
function bootstrap() {

	add_submenu_page( 'tools.php', __( 'Audit Log', 'audit-log' ), __( 'Audit Log', 'audit-log' ), 'manage_options', 'audit-log', __NAMESPACE__ . '\\output_page' );
}

/**
 * Output the appropriate page based on context.
 *
 * @return void
 */
function output_page() {
	// Check if viewing a specific item
	if ( ! empty( $_GET['item_id'] ) ) {
		output_item_page();
	} else {
		output_list_page();
	}
}

/**
 * Output the list page.
 *
 * @return void
 */
function output_list_page() {
	require_once __DIR__ . '/class-list-table.php';
	include __DIR__ . '/tools-page.php';
}

/**
 * Output the item detail page.
 *
 * @return void
 */
function output_item_page() {
	// Decode URL parameters first, then sanitize
	$item_id = isset( $_GET['item_id'] ) ? sanitize_text_field( wp_unslash( $_GET['item_id'] ) ) : '';

	if ( empty( $item_id ) ) {
		wp_die( 'No item ID provided.' );
	}

	$item = get_item( $item_id );

	if ( is_wp_error( $item ) ) {
		wp_die( 'Item not found.' );
	}

	include __DIR__ . '/item-page.php';
}
