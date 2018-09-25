<?php

namespace HM\Platform\Audit_Log\REST_API;

use function HM\Platform\Audit_Log\get_items;
use WP_Rest_Controller;
use WP_REST_Server;

class REST_Controller extends WP_Rest_Controller {
	protected $namespace = 'audit-log/v1';
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'items',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'callback'            => [ $this, 'get_items' ],
					'args'                => [
						'before'  => [
							'type'   => 'string',
							'format' => 'date-time',
						],
						'after'  => [
							'type'   => 'string',
							'format' => 'date-time',
						],
						'object' => [
							'type' => 'string',
							'description' => 'Limit the results to only items with the specified object',
						],
						'type' => [
							'type' => 'string',
							'description' => 'Limit the results to only items with the specified type',
						],
						'user_id' => [
							'type' => 'string',
							'description' => 'Limit the results to only items with the specific user_id',
						],
						'user_ip' => [
							'type' => 'string',
							'description' => 'Limit the results to only items with the specified ip address',
						],
					],
				],
			]
		);
	}

	public function get_items_permissions_check( $request ) : bool {
		return current_user_can( 'manage_options' );
	}

	public function get_items( $request ) : array {
		$eq_filters = [];
		if ( ! empty( $request['object'] ) ) {
			$eq_filters['Object_Id'] = $request['object'];
		}
		if ( ! empty( $request['user_id'] ) ) {
			$eq_filters['User_Id'] = $request['user_id'];
		}
		if ( ! empty( $request['type'] ) ) {
			$eq_filters['Name'] = $request['type'];
		}
		if ( ! empty( $request['user_ip'] ) ) {
			$eq_filters['User_Ip'] = $request['user_ip'];
		}
		$items = get_items( null, $eq_filters, $request['after'] ?: null, $request['before'] ?: null );
		return array_map( function ( array $item ) use ( $request ) : array {
			return $this->prepare_item_for_response( $item, $request );
		}, $items['items'] );
	}

	public function prepare_item_for_response( $item, $request ) : array {
		return [
			'id'                => $item['Id'],
			'date'              => date( DATE_RFC3339, strtotime( $item['Date'] ) ),
			'object'            => $item['Object_Id'],
			'description'       => $item['Description'],
			'user_id'           => (int) $item['User_Id'],
			'user_email'        => $item['User_Email'],
			'user_avatar_url'   => $item['User_Avatar_Url'],
			'user_username'     => $item['User_Username'],
			'user_display_name' => $item['User_Display_Name'],
			'user_ip'           => $item['User_Ip'],
			'event'             => json_decode( $item['Event'] ),
			'type'              => $item['Name'],
			'site_id'           => (int) $item['Site_Id'],
			'site_url'          => $item['Site_Url'],
		];
	}
}
