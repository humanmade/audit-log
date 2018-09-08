<?php

/**
 * Plugin Name: Audit Log
 * Description: Offsite append-only audit log.
 * Author: Human Made | Joe Hoyle
 */

namespace HM\Platform\Audit_Log;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/hooks/namespace.php';
require_once __DIR__ . '/inc/admin/namespace.php';
require_once __DIR__ . '/inc/rest_api/namespace.php';
require_once __DIR__ . '/inc/rest_api/class-rest-controller.php';

bootstrap();

add_action( 'plugins_loaded', __NAMESPACE__ . '\\Hooks\\bootstrap' );
add_action( 'admin_menu', __NAMESPACE__ . '\\Admin\\bootstrap' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\REST_API\\bootstrap' );
