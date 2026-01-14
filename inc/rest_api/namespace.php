<?php
/**
 * Audit Log REST API.
 *
 * @package HM\Platform\Audit_Log
 */

namespace HM\Platform\Audit_Log\REST_API;

/**
 * Bootstrap the REST API.
 *
 * @return void
 */
function bootstrap() {
	$controller = new REST_Controller;
	$controller->register_routes();
}
