<?php

namespace HM\Platform\Audit_Log\Hooks;

use function HM\Platform\Audit_Log\insert_item;
use WP_Post;

function bootstrap() {
	if ( ! apply_filters( 'hm_platform_audit_log_add_default_hooks', true ) ) {
		return;
	}
	add_action( 'wp_insert_post', __NAMESPACE__ . '\\on_wp_insert_post', 10, 3 );
}

function on_wp_insert_post( int $post_id, WP_Post $post, bool $update ) {
	$object_name = get_post_type_labels( get_post_type( $post->post_type ) )->singular_name ?: $post->post_type;
	if ( $update ) {
		$name = 'UpdatedPost';
	} else {
		$name = 'CreatedPost';
	}

	insert_item( $name, sprintf( '%s %s %s', $update ? 'Updated' : 'Created', $object_name, $post->post_title ?: $post->post_name ), $post );
}
