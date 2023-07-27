<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>


<style>
.hop_pushee-box{
	padding: 10px 10px 0;
}

.hop_pushee-box strong{
	font-style: normal;
}
.hop_pushee-box hr{
	background-color: #e3e3e3;
	border: 0;
	height: 1px;
}
.hop_pushee-box .notification-notes{
	line-height: 24px;
	margin-bottom: 20px;
}
.hop_pushee-box .notification-notes span{
	min-width: 300px;
	display: inline-block;
}
<?php if (version_compare(APP_VER, '4', '>=')) { ?>
.hop_pushee-box h1{
	border-bottom: 1px dotted #ccc;
	font-size: 18px;
	padding: 4px 0 10px;
	margin: 0 0 10px 0;
}
<?php } elseif (version_compare(APP_VER, '3', '>=') && version_compare(APP_VER, '4', '<')) { ?>
.hop_pushee-box h1{
	border-bottom: 1px dotted #cdcdcd;
	margin: 0 0 10px 0;
	padding: 4px 0 10px;
	color: gray;
	font-size: 18px;
}
<? } ?>
</style>


<div class="box">
	<div class="md-wrap hop_pushee-box">
		<h1><?php echo lang('nav_notification').$notification_id; ?></h1>
		<?php if(isset($notification_history)){ ?>

		<p><strong>Date sent:</strong> <?php echo ee()->localize->format_date('%Y-%m-%d %h:%i %A', $notification_history->date_sent); ?></p>
		<p>
			<strong>Entry ID:</strong>
			<?php echo $notification_history->entry_id ?>
		</p>
		<p>
			<?php if (isset($notification_entry)){ ?>
			<strong>Title:</strong>
			<?php echo '<a href="'.ee('CP/URL', 'publish/edit/entry/'.$notification_history->entry_id).'"><em>'.$notification_entry->title.'</em></a>'; ?>
			<?php } else { ?>
			(the entry doesn't exist anymore)
			<?php } ?>
		</p>
		<hr>
		<p><strong>Notes:</strong></p>
		<pre class="notification-notes"><?php echo $notification_history_notes; ?></pre>
		<hr>

		<?php } else { ?>

		<p><?php echo lang('view_no_local_notif_data_found'); ?></p>

		<?php } ?>

		<p><strong>Details from OneSignal</strong></p>

		<?php if ($notification) { ?>

		<table>
			<thead>
				<tr>
					<th>Attribute</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($notification as $notification_attr => $notification_val)
				{
					echo '<tr>';
					echo '<td>'.$notification_attr.'</td>';
					echo '<td>';
					if (is_array($notification_val))
					{
						echo '<pre>';
						print_r($notification_val);
						echo '</pre>';
					}
					else
					{
						echo $notification_val;
					}
					echo '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>

		<?php } else { ?>

		<p><?php echo lang('view_no_api_notif_data_found'); ?></p>

		<?php } ?>
	</div>
</div>