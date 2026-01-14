<?php
/**
 * Audit Log Tools Page Template.
 *
 * @package HM\Platform\Audit_Log
 */

namespace HM\Platform\Audit_Log\Admin;

?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form id="posts-filter" method="get">
		<?php
		$list_table = new List_Table();
		$list_table->prepare_items();
		$list_table->display();

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		?>
		<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
	</form>
</div>
