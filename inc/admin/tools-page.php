<?php

namespace HM\Platform\Audit_Log\Admin;

?>
<div class="wrap">
	<h2><?php echo get_admin_page_title() ?></h2>

	<form id="posts-filter" method="get">
		<?php
		$list_table = new List_Table();
		$list_table->prepare_items();
		$list_table->display();
		?>
		<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ) ?>" />
	</form>
</div>
