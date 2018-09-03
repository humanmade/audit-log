<?php

namespace HM\Platform\Audit_Log;

use Exception;
use function HM\Platform\get_aws_sdk;
use WP_Error;

function bootstrap() {
	register_shutdown_function( __NAMESPACE__ . '\\send_buffered_items' );
}

/**
 *  Add an audit log item.
 *
 * Most global state fields are auto-discovered, so only a subset of params are required. Added items are buffered on script-execution
 * end (after a call to fastcgi_finish_request) to not slow down response times.
 *
 * @param string $name The name/type for the item. E.g. 'CreatedPost'
 * @param string $description A human readable description of what happened. Should be in the past simple passive. E.g "Hello World was updated"
 * @param string options $object The object that has changed / been created. Used as a reference for the log entry.
 * @param array optional $event Arbitrary array of data detailing the change event.
 * @return void
 */
function insert_item( string $name, string $description, $object = '', array $event = [] ) {
	$user = wp_get_current_user();
	if ( ! $user ) {
		$user = (object) [
			'user_email'   => '',
			'user_login'   => '',
			'display_name' => '',
			'ID'           => 0,
		];
	}
	$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
	$request_id = '';

	if ( is_object( $object ) ) {
		if ( is_a( $object, 'WP_Post' ) ) {
			$object = 'WP_Post::' . $object->ID;
		} elseif ( is_a( $object, 'WP_User' ) ) {
			$object = 'WP_User::' . $object->ID;
		} if ( is_a( $object, 'WP_Term' ) ) {
			$object = 'WP_Term::' . $object->term_id;
		} else {
			$object = (string) $object;
		}
	} else {
		$object = (string) $object;
	}

	buffer_send_item(
		time(),
		$name,
		$description,
		$user->display_name,
		$user->user_email,
		$user->ID,
		$user->user_login,
		$ip_address,
		get_avatar_url( $user->ID ),
		$object,
		get_current_blog_id(),
		home_url(),
		$request_id,
		$event
	);

	// If we are a CLI process, don't buffer items
	// as they can be very long running and cause
	// large memory usage.
	if ( php_sapi_name() === 'cli' ) {
		send_buffered_items();
	}
}

/**
 * Queue an item to be sent to the audit log on script end.
 */
function buffer_send_item( int $date, string $name, string $description, string $user_display_name, string $user_email, int $user_id, string $user_username, string $user_ip, string $user_avatar_url, string $object_id, int $site_id, string $site_url, string $request_id, array $event = [] ) {
	global $hm_platform_audit_log_buffered_items;

	$body = [
		'Date'              => date( DATE_ISO8601, $date ),
		'Name'              => $name ?: '-',
		'Description'       => $description ?: '-',
		'User_Display_Name' => $user_display_name ?: '-',
		'User_Email'        => $user_email ?: '-',
		'User_Id'           => $user_id ?: 0,
		'User_Username'     => $user_username ?: '-',
		'User_Ip'           => $user_ip ?: '-',
		'User_Avatar_Url'   => $user_avatar_url ?: '-',
		'Site_Url'          => $site_url ?: '-',
		'Site_Id'           => $site_id ?: 0,
		'Request_Id'        => $request_id ?: '-',
		'Object_Id'         => $object_id ?: '-',
		'Event'             => json_encode( $event ) ?: '-',
	];

	$body = apply_filters( 'hm_platform_audit_log_send_item', $body );

	$hm_platform_audit_log_buffered_items[] = $body;
}

function get_ses_queue_url() : string {
	$url = defined( 'AUDIT_LOG_SQS_QUEUE_URL' ) ? AUDIT_LOG_SQS_QUEUE_URL : '';
	return apply_filters( 'hm_platform_audit_log_sqs_queue_url', $url );
}

function get_dynamodb_table() : string {
	$url = defined( 'AUDIT_LOG_DYNAMO_DB_TABLE' ) ? AUDIT_LOG_DYNAMO_DB_TABLE : '';
	return apply_filters( 'hm_platform_audit_log_dynamodb_table', $url );
}

function send_buffered_items() {
	global $hm_platform_audit_log_buffered_items;
	if ( ! $hm_platform_audit_log_buffered_items ) {
		return;
	}
	if ( ! get_ses_queue_url() ) {
		return null;
	}

	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}

	$client = get_aws_sdk()->createSqs( apply_filters( 'hm_platform_audit_log_sqs_client_args', [
		'http' => [
			'timeout' => 0.0001,
		],
	] ) );

	$queue_url = get_ses_queue_url();

	foreach ( $hm_platform_audit_log_buffered_items as $key => $body ) {
		unset( $hm_platform_audit_log_buffered_items[ $key ] );
		try {
			$response = $client->sendMessage([
				'MessageBody' => json_encode( $body ),
				'QueueUrl'    => $queue_url,
			]);
		} catch ( Exception $e ) {
			trigger_error( 'Unable to send item to audit log. ' . $e->getMessage() );
		}
	}
}

/**
 * Get items from the Audit Log.
 *
 * @param string $previous_item The Id of the previous item, for pagination.
 * @param array  $eq_filters A map of key => value pairs for equality filters.
 * @param integer $from_date Limit results to items after the specified date.
 * @param integer $to_date Limit results to items before the specified date.
 * @param boolean $descending Whether to get the items in descending order.
 * @return array [ 'item => [ [ 'Id  => string, Name => string ... ] ], 'has_more' => bool ]
 */
function get_items( string $previous_item = null, array $eq_filters = [], int $from_date = null, int $to_date = null, $descending = true ) {
	$client = get_aws_sdk()->createDynamoDB( apply_filters( 'hm_platform_audit_log_dynamodb_client_args', [] ) );

	$conditions = [
		'Site_Id' => [
			'AttributeValueList' => [ [ 'N' => (string) get_current_blog_id() ] ],
			'ComparisonOperator' => 'EQ',
		],
	];

	if ( $from_date || $to_date ) {
		$conditions['Id'] = [ 'AttributeValueList' => [] ];
		if ( $from_date ) {
			$conditions['Id']['AttributeValueList'][] = [ 'S' => date( DATE_ISO8601, $from_date ) ];
			$conditions['Id']['ComparisonOperator'] = 'GE';
		}
		if ( $to_date ) {
			$conditions['Id']['AttributeValueList'][] = [ 'S' => date( DATE_ISO8601, $to_date ) ];
			$conditions['Id']['ComparisonOperator'] = 'LE';
		}
		if ( $from_date && $to_date ) {
			$conditions['Id']['ComparisonOperator'] = 'BETWEEN';
		}
	}

	$query = [
		'TableName'     => get_dynamodb_table(),
		'KeyConditions' => $conditions,
		'ScanIndexForward' => ! $descending,
	];

	// If we are not doing any filter expressions we can set the limit to 100. The limit refers to the
	// pre-filter expression count, so if we have a filter-expression we don't want to add pre-filter
	// limits.
	if ( ! $eq_filters ) {
		$query['Limit'] = 100;
	}

	if ( $previous_item ) {
		$query['ExclusiveStartKey'] = [
			'Id' => [
				'S' => $previous_item
			],
			'Site_Id' => [
				'N' => (string) get_current_blog_id()
			],
		];
	}

	foreach ( $eq_filters as $key => $value ) {
		$query['QueryFilter'][ $key ] = [
			'AttributeValueList' => [ [ 'S' => (string) $value ] ],
			'ComparisonOperator' => 'EQ',
		];
	}

	try {
		$result = $client->getIterator( 'Query', $query );
	} catch ( Exception $e ) {
		return new WP_Error( 'aws-error', $e->getMessage() );
	}

	$items = [];
	$has_more = false;
	foreach ( $result as $item ) {
		if ( count( $items ) > 100 ) {
			$has_more = $item['Id']['S'];
			break;
		}
		$items[] = $item;
	}

	$items = array_map( function ( $item ) {
		return array_map( function ( $item ) {
			return array_values( $item )[0];
		}, $item );
	}, $items );

	return [
		'items' => $items,
		'has_more' => $has_more,
	];
}
