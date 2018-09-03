<?php

namespace HM\Platform\Audit_Log\Admin;

use function HM\Platform\Audit_Log\get_items;
use WP_List_Table;

class List_Table extends WP_List_Table {
	function get_columns() : array {
		return [
			'item_date' => __( 'Date', 'audit-log' ),
			'title'  => __( 'Title', 'audit-log' ),
			'user'   => __( 'User', 'audit-log' ),
			'ip'     => __( 'IP Address', 'audit-log' ),
			'object' => __( 'Object', 'audit-log' ),
		];
	}

	protected function get_sortable_columns() {
		return [
			'item_date' => array( 'date', false ),
		];
	}

	function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$this->_pagination_args = [
			'previous' => sanitize_text_field( $_GET['previous'] ?? null ),
			'current' => sanitize_text_field( $_GET['current'] ?? null ),
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
		if ( ! empty( $_GET['start'] ) ) {
			$from = strtotime( sanitize_text_field( wp_unslash( $_GET['start'] ) ) );
		}
		if ( ! empty( $_GET['end'] ) ) {
			$to = strtotime( sanitize_text_field( wp_unslash( $_GET['end'] ) ) ) + ( 60 * 60 * 24 ) - 1;
		}

		$descending = ( $_GET['order'] ?? '' ) !== 'asc';

		$items = get_items( $this->_pagination_args['current'], $eq_filters, $from ?? null, $to ?? null, $descending );

		if ( is_wp_error( $items ) ) {
			print_r( $items );
		} else {
			$this->items = $items['items'];
			$this->_pagination_args['next'] = $items['has_more'];
		}
	}

	function column_title( array $item ) : string {
		$name = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'name', $item['Name'] ) ), esc_html( $item['Name'] ) );
		return $name . '<br />' . $item['Description'];
	}

	function column_user( array $item ) : string {
		return sprintf(
			'<img src="%s" width=18 height=18 /> %s<br />%s',
			$item['User_Avatar_Url'],
			$item['User_Display_Name'],
			$item['User_Email']
		);
	}

	function column_ip( array $item ) : string {
		return sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'user_ip', $item['User_Ip'] ) ), esc_html( $item['User_Ip'] ) );
	}

	function column_object( array $item ) : string {
		return sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'object_id', $item['Object_Id'] ) ), esc_html( $item['Object_Id'] ) );
	}

	function column_item_date( array $item ) : string {
		return '<time datatime="' . esc_attr( $item['Date'] ) . '">' . date( DATE_ATOM, strtotime( $item['Date'] ) ) . '<br />' . human_time_diff( strtotime( $item['Date'] ) ) . ' ago</time>';
	}

	protected function pagination( $which ) {
		$disable_first = ! $this->_pagination_args['current'];
		$disable_next = ! $this->_pagination_args['next'];
		$page_links = [];

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( [ 'current', 'previous' ] ) ),
				__( 'First page', 'audit-log' ),
				'&laquo;'
			);
		}

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( [ 'current' => $this->_pagination_args['next'], 'previous' => $this->_pagination_args['current'] ] ) ),
				__( 'Next page', 'audit-log' ),
				'&rsaquo;'
			);
		}

		if ( ! $disable_first || ! $disable_next ) {
			$page_class = ' one-page';
		} else {
			$page_class = ' no-pages';
		}

		$output = "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';

		echo "<div class='tablenav-pages'>$output</div>";
	}


	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
		<?php
		if ( 'top' === $which ) {
			ob_start();
			?>
			<input type="text" value="<?php echo esc_attr( $_GET['name'] ?? '' ) ?>" name="name" placeholder="<?php esc_attr_e( 'Type', 'audit-log' ) ?>" />
			<input type="text" value="<?php echo esc_attr( $_GET['user_ip'] ?? '' ) ?>" name="user_ip" placeholder="<?php esc_attr_e( 'IP Address', 'audit-log' ) ?>" />
			<input type="text" value="<?php echo esc_attr( $_GET['object_id'] ?? '' ) ?>" name="object_id" placeholder="<?php esc_attr_e( 'Object Id', 'audit-log' ) ?>" />
			<label>Start
				<input type="date" name="start" value="<?php echo esc_attr( $_GET['start'] ?? '' ) ?>" max="<?php echo date( 'Y-m-j' ) ?>" />
			</label>
			<label>End
				<input type="date" name="end" value="<?php echo esc_attr( $_GET['end'] ?? '' ) ?>" max="<?php echo date( 'Y-m-j' ) ?>" />
			</label>
			<?php

			$output = ob_get_clean();

			if ( $output ) {
				echo $output;
				submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			}
		}
		?>
		</div>
		<?php
	}

		/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
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
