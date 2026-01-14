<?php
/**
 * Audit Log Item Detail Page
 *
 * @var array $item The audit log item data
 */

// Ensure item is set and not an error.
if ( is_wp_error( $item ) || empty( $item ) ) {
	wp_die( 'Invalid audit log item.' );
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Audit Log Item', 'audit-log' ); ?></h1>

	<table class="widefat striped" style="margin-top: 20px;">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'ID', 'audit-log' ); ?></th>
				<td><code><?php echo esc_html( $item['Id'] ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Date', 'audit-log' ); ?></th>
				<td>
					<time datetime="<?php echo esc_attr( $item['Date'] ); ?>">
						<?php echo esc_html( date( DATE_RFC3339, strtotime( $item['Date'] ) ) ); ?>
					</time>
					<br />
					<small><?php echo esc_html( human_time_diff( strtotime( $item['Date'] ) ) ); ?> ago</small>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Type', 'audit-log' ); ?></th>
				<td><?php echo esc_html( $item['Name'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Object', 'audit-log' ); ?></th>
				<td><code><?php echo esc_html( $item['Object_Id'] ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Description', 'audit-log' ); ?></th>
				<td><?php echo esc_html( $item['Description'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'User', 'audit-log' ); ?></th>
				<td>
					<img src="<?php echo esc_url( $item['User_Avatar_Url'] ); ?>" alt="" width="32" height="32" style="border-radius: 50%; margin-right: 8px; vertical-align: middle;" />
					<span><?php echo esc_html( $item['User_Display_Name'] ); ?></span>
					<br />
					<small><?php echo esc_html( $item['User_Email'] ); ?></small>
					<br />
					<small><?php echo esc_html( $item['User_Username'] ); ?> (ID: <?php echo esc_html( $item['User_Id'] ); ?>)</small>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'IP Address', 'audit-log' ); ?></th>
				<td><code><?php echo esc_html( $item['User_Ip'] ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Site', 'audit-log' ); ?></th>
				<td>
					<?php echo esc_html( $item['Site_Url'] ); ?> (ID: <?php echo esc_html( $item['Site_Id'] ); ?>)
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Event Data', 'audit-log' ); ?></th>
				<td>
					<?php
					$event = json_decode( $item['Event'] ?? '{}', true ) ?: [];
					$event_output = empty( $event ) ? '(empty)' : json_encode( $event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
					?>
					<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow: auto;"><code><?php echo esc_html( $event_output ); ?></code></pre>
				</td>
			</tr>
		</tbody>
	</table>
</div>
