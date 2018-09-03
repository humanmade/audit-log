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

bootstrap();
Hooks\bootstrap();

add_action( 'admin_menu', __NAMESPACE__ . '\\Admin\\bootstrap' );
