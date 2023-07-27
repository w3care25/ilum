<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/settings_helper.php';
require_once PATH_THIRD.'hop_pushee/api_helper.php';
require_once PATH_THIRD.'hop_pushee/helper.php';

class Hop_pushee_ext
{
	public $version = HOP_PUSHEE_VERSION;

	/**
	 * Undocumented function
	 *
	 * @param ChannelEntry $entry The model object
	 * @param array $values The model data as an array
	 * @return void
	 */
	public function after_channel_entry_save($entry, $values)
	{
		ee()->load->library('logger');
		ee()->load->helper('string');
		ee()->load->helper('url');

		// EE3 "bug": changing the custom field value will trigger the hook again
		// Check/Set a variable in session cache to be sure to not run all the process twice
		if (ee()->session->cache(__CLASS__, 'entry_process', NULL) == $entry->entry_id)
		{
			// We already processed that entry, no need to go further
			return;
		}
		// Set the session cache variable right now, as process has began
		ee()->session->set_cache(__CLASS__, 'entry_process', $entry->entry_id);

		// For debugging
		// ee()->logger->developer('Hop PushEE: Processing entry '.$entry->entry_id.' for push notification.');

		// echo '<pre>';
		// debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50);
		// echo '</pre>';

		$settings = Hop_pushee_settings_helper::get_settings();
		$custom_field_triggers = $settings['custom_field_triggers'];
		$custom_cat_field_segment = $settings['custom_category_segment_fields'];

		// Check if entry status is OK
		if ($entry->status_id == NULL)
		{
			// For some reason, entry status id can be null (EE3),
			//  in that case, we have to use the status label and query for it to get its id
			$query_status = ee()->db->select('status_id')
				->from('statuses')
				->where('status', $entry->status)
				->get();
			$status_row = $query_status->row();
			$status_id = $status_row->status_id;
		}
		else
		{
			$status_id = $entry->status_id;
		}
		if (!in_array($status_id, $settings['entry_statuses_trigger']))
		{
			ee()->logger->developer('Hop PushEE: Entry '.$entry->entry_id.' status is set to not trigger notification.');
			return;
		}

		// Check that any of the trigger field is set to "Send"
		$triggered = FALSE;
		$custom_field_name = '';
		foreach ($values as $field_name => $field_value)
		{
			// if field in field_triggers and proper value is set
			if (substr($field_name, 0, 9) == 'field_id_')
			{
				// That's a custom field, check if it's a triggering field
				$field_id = $entry->getCustomField($field_name)->getId();
				if (in_array($field_id, $custom_field_triggers))
				{
					// Almost there, check the field value
					if ($field_value === 'Send')
					{
						$triggered = TRUE;
						$custom_field_name = $field_name;
						// We know we need to send the notification, no need to loop further
						break;
					}
				}
			}
		}

		// No need to go further if entry not triggered
		if (!$triggered)
		{
			ee()->logger->developer('Hop PushEE: No trigger field found or trigger field set to not send notification for entry '.$entry->entry_id.' ');
			return;
		}

		// Check if we already sent a notification
		if ($settings['allow_multiple_notifications'] != 'y' && Hop_pushee_settings_helper::notification_for_entry_exists($entry->entry_id))
		{
			ee()->logger->developer('Hop PushEE: Entry '.$entry->entry_id.' already has a notification sent.');
			return;
		}

		// Check categories for segments
		$onesignal_segments = array();
		foreach ($entry->Categories as $category)
		{
			foreach ($category->getCustomFields() as $custom_field)
			{
				if (in_array($custom_field->getId(), $custom_cat_field_segment))
				{
					if ($custom_field->getData() != '')
					{
						$onesignal_segments[] = $custom_field->getData();
					}
				}
			}
		}

		if (count($onesignal_segments) == 0)
		{
			ee()->logger->developer('Hop PushEE: Entry '.$entry->entry_id.' has triggered a notification but no user segment found.');
			return;
		}

		// Use OneSignal API to send notifications out
		$api_helper = new Hop_pushee_api_helper($settings['onesignal_app_id'], $settings['onesignal_api_key']);

		// -------- Create notification data --------

		// Notification content
		$notification_content = Hop_pushee_helper::parse_notification_template($settings['notification_content_template'], $entry->entry_id);
		if ($notification_content == '')
		{
			// If the notification content is empty, notification isn't sent
			ee()->logger->developer('Hop PushEE: Entry '.$entry->entry_id.' has triggered a notification but the notification content was empty.');
			return;
		}
		$content = array(
			'en' => $notification_content
		);

		// Notification title
		$notif_title = trim(Hop_pushee_helper::get_title_cache());
		if ($notif_title == '' || $notif_title == NULL)
		{
			$notif_title = $entry->title;
		}
		$headings = array(
			'en' => $notif_title
		);

		// Entry URL
		$notif_url = trim(Hop_pushee_helper::get_url_cache());
		if ($notif_url == '' || $notif_url == NULL)
		{
			// Construct entry URL like {comment_url_title_auto_path} does
			$query_channel = ee()->db->select('channel_id, channel_name, comment_url')
				->from('channels')
				->where('channel_id', $entry->channel_id)
				->get();

			if ($query_channel->num_rows() == 0)
			{
				// Channel not found, that should not be possible, but we never know
				return;
			}
			$query_channel_rows = $query_channel->result();
			$channel_row = $query_channel_rows[0];

			// The comment URL can contain the {base_url} tag, so we need to replace it
			$notif_url = str_replace('{base_url}', base_url(), $channel_row->comment_url.'/'.$entry->url_title);
			$notif_url = reduce_double_slashes($notif_url);
		}

		// Icon URL - If not in cache (fetched from the template), use the default one set in the settings
		$icon_url = trim(Hop_pushee_helper::get_icon_cache());
		if ($icon_url == '' || $icon_url == NULL)
		{
			$icon_url = $settings['notification_icon_url'];
		}

		$data = array(
			// 'included_segments' => array('All'),
			'included_segments'	=> $onesignal_segments,
			// 'data' => array("foo" => "bar"),
			'contents'			=> $content,
			'headings'			=> $headings,
			'url'				=> $notif_url,
			'chrome_web_icon'	=> $icon_url,
			'firefox_icon'		=> $icon_url,
		);

		// Send data to One Signal
		$result = $api_helper->push_notification($data);
		// var_dump($result);
		/* result is 
			array (size=2)
				'id' => string '73252d6d-e962-4616-986c-f0fc8b08e773' (length=36)
				'recipients' => int 1
		*/
		// Is recipients is = 0, no notification is created, id will be empty

		if ($result)
		{
			// Save notification into our database
			$notes = 'Sending notification to segments: ';
			foreach($onesignal_segments as $segment)
			{
				$notes .= $segment.', ';
			}
			$notes = substr($notes, 0, -2) . "\n";
			$notes .= 'Notification title: '.$entry->title."\n";
			$notes .= 'OneSignal notification id: '.$result['id']."\n";
			$notes .= 'Recipients: '.$result['recipients']."\n";
			Hop_pushee_settings_helper::add_notification_for_entry($entry->entry_id, $result['id'], $notes);

			// Update the custom field value of the entry
			// This triggers the extension hook again in EE3 but not EE4
			$entry_model = ee('Model')->get('ChannelEntry')->filter('entry_id', $entry->entry_id)->first();
			if ($entry_model)
			{
				$entry_model->{$custom_field_name} = 'Sent';
				$entry_model->save();
			}
		}
	}
}