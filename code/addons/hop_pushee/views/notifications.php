<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>


<div class="box">
	<div class="tbl-ctrls">
		<h1><?php echo lang('nav_notifications'); ?></h1>
		<p><?php echo lang('view_all_notif_list_desc'); ?></p>
		<div class="alert inline warn">
			<p><?php echo lang('view_all_notif_delete_notice'); ?></p>
		</div>

		<?php
		$this->embed('ee:_shared/table', $table);
		if (isset($pagination)) echo $pagination;
		?>
	</div>
</div>