<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>


<style>
.notification-preview {
	display: block;
	padding: 8px;
	background: #eee;
	max-width: 350px;
	color: #000;
}
.notification-preview strong {
	font-style: normal;
}
.notification-preview::after {
	display: table;
	content: '';
	clear: both;
}
.notification-icon {
	float:left;
	margin-right: 12px;
}
.notification-icon img {
	max-height: 50px;
}
.notification-title {
	margin: 3px 0 6px 0;
}
.notification-body {
	margin: 0 0 0 0;
}
</style>

<div class="box">
	<div class="mdd-wrap tbl-ctrls">
		<?php echo form_open(ee('CP/URL', 'addons/settings/hop_pushee/notification_preview')); ?>
			<fieldset class="tbl-search right">
				<input placeholder="Entry id" name="entry_id_preview" type="text" value="">
				<input class="btn submit" type="submit" value="Preview">
			</fieldset>
		</form>
		<h1><?php echo lang('nav_notification_preview'); ?></h1>

		<?= ee('CP/Alert')->getAllInlines() ?>
		<?php if (isset($entry_id)){ ?>
			<p>Previewing notification content for <a href="<?php echo ee('CP/URL', 'publish/edit/entry/'.$entry_id);?>"><?php echo $entry->title;?> [entry id: <?php echo $entry_id; ?>]</a></p>
			<p><?php echo lang('notif_preview_note'); ?></p>
			<?php if (isset($notification_content)){ ?>
			<a href="<?php echo $notif_url; ?>" class="notification-preview">
				<div class="notification-icon">
					<img src="<?php echo $notif_icon_url; ?>" alt="notification icon" />
				</div>
				<p class="notification-title"><strong><?php echo $notif_title; ?></strong></p>
				<p class="notification-body"><?php echo($notification_content); ?></p>
			</a>
			<p>&nbsp;</p>
			<?php } else { ?>
				<p>We couldn't parse the notification content template.</p>
			<?php } ?>
		<?php } else { ?>
			<p>Enter an entry id in the form above to preview the notification.</p>
		<?php } ?>
	</div>
</div>