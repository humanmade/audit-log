<?php

namespace HM\Platform\Audit_Log\CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;
use function HM\Platform\Audit_Log\get_items;
use function HM\Platform\Audit_Log\get_item;

class Command extends WP_CLI_Command {
	/**
	 * List audit log items.
	 *
	 * ## OPTIONS
	 *
	 * [--after=<date>]
	 * : Return items after this date. Any strtotime compatible format.
	 *
	 * [--before=<date>]
	 * : Return items before this date. Any strtotime compatible format.
	 *
	 * [--object=<object_id>]
	 * : Filter by object id.
	 *
	 * [--type=<name>]
	 * : Filter by audit item type/name.
	 *
	 * [--user_id=<id>]
	 * : Filter by user id.
	 *
	 * [--user_ip=<ip>]
	 * : Filter by user IP address.
	 *
	 * [--start-after=<item_id>]
	 * : Continue after the specified item id (useful for pagination).
	 *
	 * [--ascending]
	 * : Sort oldest first. Default is newest first.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to display. Available fields: id, date, type, object, user, user_id, user_ip, description, site_id. Default: id, date, type, object, user, description.
	 *
	 * [--format=<format>]
	 * : Render format. table, json, csv, yaml, ids. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     # List latest items
	 *     wp audit-log list
	 *
	 *     # Filter by type and date
	 *     wp audit-log list --type=UpdatedPost --after="2023-01-01"
	 *
	 *     # Show custom fields
	 *     wp audit-log list --fields=id,date,type,user_ip
	 *
	 * @subcommand list
	 */
	public function list_( array $args, array $assoc_args ) : void {
		$after  = isset( $assoc_args['after'] ) ? strtotime( $assoc_args['after'] ) : null;
		$before = isset( $assoc_args['before'] ) ? strtotime( $assoc_args['before'] ) : null;

		if ( isset( $assoc_args['after'] ) && $after === false ) {
			WP_CLI::error( 'Invalid --after date value.' );
		}

		if ( isset( $assoc_args['before'] ) && $before === false ) {
			WP_CLI::error( 'Invalid --before date value.' );
		}

		$eq_filters = [];

		if ( ! empty( $assoc_args['object'] ) ) {
			$eq_filters['Object_Id'] = $assoc_args['object'];
		}

		if ( ! empty( $assoc_args['user_id'] ) ) {
			$eq_filters['User_Id'] = $assoc_args['user_id'];
		}

		if ( ! empty( $assoc_args['type'] ) ) {
			$eq_filters['Name'] = $assoc_args['type'];
		}

		if ( ! empty( $assoc_args['user_ip'] ) ) {
			$eq_filters['User_Ip'] = $assoc_args['user_ip'];
		}

		$previous_item = $assoc_args['start-after'] ?? null;
		$descending    = empty( $assoc_args['ascending'] );

		$result = get_items( $previous_item, $eq_filters, $after ?: null, $before ?: null, $descending );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$items = $result['items'] ?? [];

		if ( ! $items ) {
			WP_CLI::log( 'No audit log items found.' );
			return;
		}

		$rows = array_map( function ( array $item ) {
			return [
				'id'          => $item['Id'] ?? '',
				'date'        => $item['Date'] ?? '',
				'type'        => $item['Name'] ?? '',
				'object'      => $item['Object_Id'] ?? '',
				'user'        => trim( ( $item['User_Display_Name'] ?? '' ) . ' <' . ( $item['User_Email'] ?? '' ) . '>' ),
				'user_id'     => $item['User_Id'] ?? '',
				'user_ip'     => $item['User_Ip'] ?? '',
				'description' => $item['Description'] ?? '',
				'site_id'     => $item['Site_Id'] ?? '',
			];
		}, $items );

		// Default fields if not specified
		$default_fields = [ 'id', 'date', 'type', 'object', 'user', 'description' ];
		$fields = ! empty( $assoc_args['fields'] ) ? explode( ',', $assoc_args['fields'] ) : $default_fields;
		$fields = array_map( 'trim', $fields );

		Utils\format_items( $assoc_args['format'] ?? 'table', $rows, $fields );

		if ( ! empty( $result['has_more'] ) ) {
			WP_CLI::line( sprintf( 'More items available. Use --start-after=%s to continue.', $result['has_more'] ) );
		}
	}

	/**
	 * Get a specific audit log item by ID.
	 *
	 * ## ARGUMENTS
	 *
	 * <id>
	 * : The ID of the audit log item to retrieve.
	 *
	 * [--format=<format>]
	 * : Render format. json, yaml. Default: json.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get item details
	 *     wp audit-log get 2024-01-15T10:30:45+00:00
	 *
	 *     # Get as YAML
	 *     wp audit-log get 2024-01-15T10:30:45+00:00 --format=yaml
	 *
	 * @subcommand get
	 */
	public function get( array $args, array $assoc_args ) : void {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Please provide an item ID.' );
		}

		$item_id = $args[0];
		$item = get_item( $item_id );

		if ( is_wp_error( $item ) ) {
			WP_CLI::error( $item->get_error_message() );
		}

		// Prepare the full item data with all fields as key-value pairs (transposed for display)
		$output_rows = [];
		$item_data = [
			'id'                => $item['Id'] ?? '',
			'date'              => $item['Date'] ?? '',
			'type'              => $item['Name'] ?? '',
			'object'            => $item['Object_Id'] ?? '',
			'description'       => $item['Description'] ?? '',
			'user_id'           => $item['User_Id'] ?? '',
			'user_email'        => $item['User_Email'] ?? '',
			'user_display_name' => $item['User_Display_Name'] ?? '',
			'user_username'     => $item['User_Username'] ?? '',
			'user_ip'           => $item['User_Ip'] ?? '',
			'user_avatar_url'   => $item['User_Avatar_Url'] ?? '',
			'site_id'           => $item['Site_Id'] ?? '',
			'site_url'          => $item['Site_Url'] ?? '',
		];

		foreach ( $item_data as $key => $value ) {
			$output_rows[] = [
				'field' => $key,
				'value' => $value,
			];
		}

		Utils\format_items( $assoc_args['format'] ?? 'table', $output_rows, [ 'field', 'value' ] );

		// Display event separately for better readability
		$event_data = json_decode( $item['Event'] ?? '{}', true ) ?: [];
		if ( ! empty( $event_data ) ) {
			WP_CLI::line( '' );
			WP_CLI::line( 'Event:' );
			WP_CLI::line( json_encode( $event_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}
	}
}
