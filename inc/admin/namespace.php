<?php

namespace HM\Platform\Audit_Log\Admin;

function bootstrap() {

	add_submenu_page( 'tools.php', __( 'Audit Log', 'audit-log' ), __( 'Audit Log', 'audit-log' ), 'manage_options', 'audit-log', __NAMESPACE__ . '\\output_tools_page' );
}

function output_tools_page() {
	require_once __DIR__ . '/class-list-table.php';
	include __DIR__ . '/tools-page.php';
}
