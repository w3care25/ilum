<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>


<div class="box">
	<div class="tbl-ctrls">
		<h1><?php echo lang('nav_history'); ?></h1>
		<p><?php echo lang('view_history_list_desc'); ?></p>

		<?php
		$this->embed('ee:_shared/table', $table);
		if (isset($pagination)) echo $pagination;
		?>
	</div>
</div>