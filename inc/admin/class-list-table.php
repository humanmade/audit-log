<?php
/**
 * Audit Log List Table.
 *
 * @package HM\Platform\Audit_Log
 */

namespace HM\Platform\Audit_Log\Admin;

use function HM\Platform\Audit_Log\get_items;
use WP_List_Table;

/**
 * List Table for displaying audit log items.
 */
class List_Table extends WP_List_Table {

	/**
	 * Get columns for the table.
	 *
	 * @return array Column definitions.
	 */
	public function get_columns() : array {
		return [
			'item_date' => __( 'Date', 'audit-log' ),
			'title'     => __( 'Title', 'audit-log' ),
			'user'      => __( 'User', 'audit-log' ),
			'ip'        => __( 'IP Address', 'audit-log' ),
			'object'    => __( 'Object', 'audit-log' ),
		];
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable column definitions.
	 */
	protected function get_sortable_columns() {
		return [
			'item_date' => [ 'date', false ],
		];
	}

	/**
	 * Get the default primary column name.
	 *
	 * @return string Primary column name.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Get default column value.
	 *
	 * @param array  $item        Item data.
	 * @param string $column_name Column name.
	 * @return string Column value.
	 */
	protected function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Handle row actions.
	 *
	 * @param array  $item        Item data.
	 * @param string $column_name Column name.
	 * @param string $primary     Primary column name.
	 * @return string Row actions HTML.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$detail_url = add_query_arg( 'item_id', urlencode( $item['Id'] ), admin_url( 'tools.php?page=audit-log' ) );

		$actions = [
			'view' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $detail_url ),
				esc_html__( 'View Details', 'audit-log' )
			),
		];

		return $this->row_actions( $actions );
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$previous = isset( $_GET['previous'] ) ? sanitize_text_field( wp_unslash( $_GET['previous'] ) ) : null;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$current = isset( $_GET['current'] ) ? sanitize_text_field( wp_unslash( $_GET['current'] ) ) : null;

		$this->_pagination_args = [
			'previous' => $previous,
			'current'  => $current,
		];

		$eq_filters = [];
		if ( ! empty( $_GET['name'] ) ) {
			$eq_filters['Name'] = trim( sanitize_text_field( wp_unslash( $_GET['name'] ) ) );
		}
		if ( ! empty( $_GET['user_ip'] ) ) {
			$eq_filters['User_Ip'] = trim( sanitize_text_field( wp_unslash( $_GET['user_ip'] ) ) );
		}
		if ( ! empty( $_GET['object_id'] ) ) {
			$eq_filters['Object_Id'] = trim( sanitize_text_field( wp_unslash( $_GET['object_id'] ) ) );
		}

		$from = null;
		$to = null;

		if ( ! empty( $_GET['start'] ) ) {
			$from = strtotime( sanitize_text_field( wp_unslash( $_GET['start'] ) ) );
		}
		if ( ! empty( $_GET['end'] ) ) {
			$to = strtotime( sanitize_text_field( wp_unslash( $_GET['end'] ) ) ) + ( 60 * 60 * 24 ) - 1;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';
		$descending = $order !== 'asc';

		$items = get_items( $this->_pagination_args['current'], $eq_filters, $from, $to, $descending );

		if ( is_wp_error( $items ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Audit Log error: ' . $items->get_error_message() );
		} else {
			$this->items = $items['items'];
			$this->_pagination_args['next'] = $items['has_more'];
		}
	}

	/**
	 * Render the title column.
	 *
	 * @param array $item Item data.
	 * @return string Column HTML.
	 */
	public function column_title( array $item ) : string {
		$detail_url = add_query_arg( 'item_id', urlencode( $item['Id'] ), admin_url( 'tools.php?page=audit-log' ) );
		$name = sprintf( '<a href="%s">%s</a>', esc_url( $detail_url ), esc_html( $item['Name'] ) );
		return $name . '<br />' . esc_html( $item['Description'] );
	}

	/**
	 * Render the user column.
	 *
	 * @param array $item Item data.
	 * @return string Column HTML.
	 */
	public function column_user( array $item ) : string {
		return sprintf(
			'<img src="%s" width=18 height=18 /> %s<br />%s',
			esc_url( $item['User_Avatar_Url'] ),
			esc_html( $item['User_Display_Name'] ),
			esc_html( $item['User_Email'] )
		);
	}

	/**
	 * Render the IP column.
	 *
	 * @param array $item Item data.
	 * @return string Column HTML.
	 */
	public function column_ip( array $item ) : string {
		return sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'user_ip', $item['User_Ip'] ) ), esc_html( $item['User_Ip'] ) );
	}

	/**
	 * Render the object column.
	 *
	 * @param array $item Item data.
	 * @return string Column HTML.
	 */
	public function column_object( array $item ) : string {
		return sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'object_id', $item['Object_Id'] ) ), esc_html( $item['Object_Id'] ) );
	}

	/**
	 * Render the item date column.
	 *
	 * @param array $item Item data.
	 * @return string Column HTML.
	 */
	public function column_item_date( array $item ) : string {
		return '<time datatime="' . esc_attr( $item['Date'] ) . '">' . esc_html( date( DATE_ATOM, strtotime( $item['Date'] ) ) ) . '<br />' . esc_html( human_time_diff( strtotime( $item['Date'] ) ) ) . ' ago</time>';
	}

	/**
	 * Display pagination.
	 *
	 * @param string $which Position of pagination (top or bottom).
	 * @return void
	 */
	protected function pagination( $which ) {
		$disable_first = ! $this->_pagination_args['current'];
		$disable_next = ! $this->_pagination_args['next'];
		$page_links = [];

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( [ 'current', 'previous' ] ) ),
				esc_html__( 'First page', 'audit-log' ),
				'&laquo;'
			);
		}

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url(
					add_query_arg(
						[
							'current'  => $this->_pagination_args['next'],
							'previous' => $this->_pagination_args['current'],
						]
					)
				),
				esc_html__( 'Next page', 'audit-log' ),
				'&rsaquo;'
			);
		}

		if ( ! $disable_first || ! $disable_next ) {
			$page_class = ' one-page';
		} else {
			$page_class = ' no-pages';
		}

		$output = "\n<span class='pagination-links'>" . implode( "\n", $page_links ) . '</span>';

		echo "<div class='tablenav-pages" . esc_attr( $page_class ) . "'>" . wp_kses_post( $output ) . '</div>';
	}

	/**
	 * Display extra table navigation.
	 *
	 * @param string $which Position of navigation (top or bottom).
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		if ( 'top' === $which ) {
			ob_start();

			$name_value = isset( $_GET['name'] ) ? sanitize_text_field( wp_unslash( $_GET['name'] ) ) : '';
			$user_ip_value = isset( $_GET['user_ip'] ) ? sanitize_text_field( wp_unslash( $_GET['user_ip'] ) ) : '';
			$object_id_value = isset( $_GET['object_id'] ) ? sanitize_text_field( wp_unslash( $_GET['object_id'] ) ) : '';
			$start_value = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : '';
			$end_value = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : '';
			?>
			<input type="text" value="<?php echo esc_attr( $name_value ); ?>" name="name" placeholder="<?php esc_attr_e( 'Type', 'audit-log' ); ?>" />
			<input type="text" value="<?php echo esc_attr( $user_ip_value ); ?>" name="user_ip" placeholder="<?php esc_attr_e( 'IP Address', 'audit-log' ); ?>" />
			<input type="text" value="<?php echo esc_attr( $object_id_value ); ?>" name="object_id" placeholder="<?php esc_attr_e( 'Object Id', 'audit-log' ); ?>" />
			<label>Start
				<input type="date" name="start" value="<?php echo esc_attr( $start_value ); ?>" max="<?php echo esc_attr( date( 'Y-m-j' ) ); ?>" />
			</label>
			<label>End
				<input type="date" name="end" value="<?php echo esc_attr( $end_value ); ?>" max="<?php echo esc_attr( date( 'Y-m-j' ) ); ?>" />
			</label>
			<?php

			$output = ob_get_clean();

			if ( $output ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped above.
				echo $output;
				submit_button( __( 'Filter' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );
			}
		}
		?>
		</div>
		<?php
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which Position of navigation (top or bottom).
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}
}
