<?php

namespace HM\Platform\Audit_Log\CLI;

use WP_CLI;

function bootstrap() : void {
	require_once __DIR__ . '/class-command.php';
	WP_CLI::add_command( 'audit-log', Command::class );
}
