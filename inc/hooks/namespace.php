<?php
/**
 * Audit Log Hooks.
 *
 * @package HM\Platform\Audit_Log
 */

namespace HM\Platform\Audit_Log\Hooks;

use function HM\Platform\Audit_Log\insert_item;
use WP_Post;
use WP_Term;

/**
 * Todo:
 * Widget:
 * - Update
 * - Create
 * Customizer:
 * - Update
 * User
 * - Exported / Downloaded Data
 * Term Relationships:
 * - Created
 * - Deleted
 * Plugin:
 * - Activate
 * - Deactive
 * - Delete
 * - Install
 * Meta
 * - Create
 * - Update
 * - Delete
 */

/**
 * Bootstrap the hooks.
 *
 * @return void
 */
function bootstrap() {
	if ( ! apply_filters( 'hm_platform_audit_log_add_default_hooks', true ) ) {
		return;
	}
	add_action( 'wp_insert_post', __NAMESPACE__ . '\\on_wp_insert_post', 10, 3 );
	add_action( 'delete_post', __NAMESPACE__ . '\\on_delete_post' );
	add_action( 'create_term', __NAMESPACE__ . '\\on_create_term', 10, 3 );
	add_action( 'edit_term', __NAMESPACE__ . '\\on_edit_term', 10, 3 );
	add_action( 'delete_term', __NAMESPACE__ . '\\on_delete_term', 10, 4 );
	add_action( 'user_register', __NAMESPACE__ . '\\on_user_register' );
	add_action( 'profile_update', __NAMESPACE__ . '\\on_profile_update', 10 );
}

/**
 * Handle post insert/update.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 * @return void
 */
function on_wp_insert_post( int $post_id, WP_Post $post, bool $update ) {
	$object_name = get_post_type_labels( get_post_type( $post->post_type ) )->singular_name ?: $post->post_type;
	if ( $update ) {
		$name = 'UpdatedPost';
	} else {
		$name = 'CreatedPost';
	}

	insert_item( $name, sprintf( '%s %s %s', $update ? 'Updated' : 'Created', $object_name, $post->post_title ?: $post->post_name ), $post );
}

/**
 * Handle post deletion.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function on_delete_post( int $post_id ) {
	$post = get_post( $post_id );
	$object_name = get_post_type_labels( get_post_type( $post->post_type ) )->singular_name ?: $post->post_type;
	insert_item( 'DeletedPost', sprintf( 'Deleted %s %s', $object_name, $post->post_title ?: $post->post_name ), $post );
}

/**
 * Handle term creation.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 * @return void
 */
function on_create_term( int $term_id, int $tt_id, string $taxonomy ) {
	$term = get_term( $term_id, $taxonomy );
	$object_name = get_taxonomy_labels( get_taxonomy( $taxonomy ) )->singular_name ?: $taxonomy;
	insert_item( 'CreatedTerm', sprintf( 'Created %s %s', $object_name, $term->name ), $term );
}

/**
 * Handle term edit.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 * @return void
 */
function on_edit_term( int $term_id, int $tt_id, string $taxonomy ) {
	$term = get_term( $term_id, $taxonomy );
	$object_name = get_taxonomy_labels( get_taxonomy( $taxonomy ) )->singular_name ?: $taxonomy;
	insert_item( 'UpdatedTerm', sprintf( 'Updated %s %s', $object_name, $term->name ), $term );
}

/**
 * Handle term deletion.
 *
 * @param int     $term_id  Term ID.
 * @param int     $tt_id    Term taxonomy ID.
 * @param string  $taxonomy Taxonomy slug.
 * @param WP_Term $term     Term object.
 * @return void
 */
function on_delete_term( int $term_id, int $tt_id, string $taxonomy, WP_Term $term ) {
	$object_name = get_taxonomy_labels( get_taxonomy( $taxonomy ) )->singular_name ?: $taxonomy;
	insert_item( 'DeletedTerm', sprintf( 'Deleted %s %s', $object_name, $term->name ), $term );
}

/**
 * Handle user registration.
 *
 * @param int $user_id User ID.
 * @return void
 */
function on_user_register( int $user_id ) {
	$user = get_userdata( $user_id );
	insert_item( 'CreatedUser', sprintf( 'Created user %s', $user->user_login ), $user );
}

/**
 * Handle profile update.
 *
 * @param int $user_id User ID.
 * @return void
 */
function on_profile_update( int $user_id ) {
	$user = get_userdata( $user_id );
	insert_item( 'UpdatedUser', sprintf( 'Updated user %s', $user->user_login ), $user );
}
