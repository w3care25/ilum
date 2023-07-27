<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/config.php';

class Hop_pushee_settings_helper
{
	private static $_settings_table_name = 'hop_pushee_settings';
	private static $_settings;
	private static $_notifications_table_name = 'hop_pushee_notifications';

	private static function _get_default_settings()
	{
		return array(
			'onesignal_app_id'					=> '',
			'onesignal_api_key'					=> '',
			'custom_field_triggers'				=> array(),
			'custom_category_segment_fields'	=> array(),
			'entry_statuses_trigger'			=> array(),
			'notification_icon_url'				=> '',
			'notification_content_template'		=> '',
			'allow_multiple_notifications'		=> 'n',
		);
	}

	public static function get_settings()
	{
		if (! isset(self::$_settings))
		{
			$settings = array();

			//Get the actual saved settings
			$query = ee()->db->get(self::$_settings_table_name);

			foreach ($query->result_array() as $row)
			{
				if (in_array($row["setting_name"], array('custom_field_triggers', 'custom_category_segment_fields', 'entry_statuses_trigger')))
				{
					$settings[$row["setting_name"]] = unserialize($row["value"]);
				}
				else
				{
					$settings[$row["setting_name"]] = $row["value"];
				}
			}

			self::$_settings = array_merge(self::_get_default_settings(), $settings);
		}

		return self::$_settings;
	}

	/**
	 * Save Add-on settings into database
	 * @param  array  $settings [description]
	 * @return array			[description]
	 */
	public static function save_settings($settings = array())
	{
		//be sure to save all settings possible
		$_tmp_settings = array_merge(self::_get_default_settings(), $settings);

		//No way to do INSERT IF NOT EXISTS so...
		foreach ($_tmp_settings as $setting_name => $setting_value)
		{
			if (in_array($setting_name, array('custom_field_triggers', 'custom_category_segment_fields', 'entry_statuses_trigger')))
			{
				$setting_value = serialize($setting_value);
			}
			$query = ee()->db->get_where(self::$_settings_table_name, array('setting_name'=>$setting_name), 1, 0);
			if ($query->num_rows() == 0) {
			  // A record does not exist, insert one.
			  $query = ee()->db->insert(self::$_settings_table_name, array('setting_name' => $setting_name, 'value' => $setting_value));
			} else {
			  // A record does exist, update it.
			  $query = ee()->db->update(self::$_settings_table_name, array('value' => $setting_value), array('setting_name'=>$setting_name));
			}
		}

		self::$_settings = $_tmp_settings;
	}

	/**
	 * Checks if a notification was already sent for that entry
	 *
	 * @param integer $entry_id
	 * @return bool
	 */
	public static function notification_for_entry_exists($entry_id)
	{
		$query_notif = ee()->db->select('entry_id')
			->from(self::$_notifications_table_name)
			->where('entry_id', $entry_id)
			->get();
		
		return $query_notif->num_rows() > 0;
	}

	public static function add_notification_for_entry($entry_id, $notification_id, $notes)
	{
		$query = ee()->db->insert(
			self::$_notifications_table_name,
			array(
				'entry_id'			=> $entry_id,
				'date_sent'			=> ee()->localize->now,
				'notification_id'	=> $notification_id,
				'notes'				=> $notes
			)
		);
	}
}