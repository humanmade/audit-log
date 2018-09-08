<?php

namespace HM\Platform\Audit_Log\REST_API;

function bootstrap() {
	$controller = new REST_Controller;
	$controller->register_routes();
}
