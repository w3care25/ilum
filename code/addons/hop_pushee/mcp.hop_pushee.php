<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/settings_helper.php';
require_once PATH_THIRD.'hop_pushee/helper.php';

class Hop_pushee_mcp
{
	private $_base_url;
	private $_notif_perpage = 20;
	private $_notif_page = 1;

	public function __construct()
	{
		$this->_base_url = ee('CP/URL')->make('addons/settings/hop_pushee');
	}

	private function build_nav()
	{
		$sidebar = ee('CP/Sidebar')->make();
		$sd_div = $sidebar->addHeader(lang('sidenav_notifications'));
		$sd_div_list = $sd_div->addBasicList();
		$sd_div_list->addItem(lang('sidenav_history'), ee('CP/URL', 'addons/settings/hop_pushee/local_activity_log'));
		$sd_div_list->addItem(lang('sidenav_all_notifications'), ee('CP/URL', 'addons/settings/hop_pushee/notifications'));
		$sd_div_list->addItem(lang('sidenav_notification_preview'), ee('CP/URL', 'addons/settings/hop_pushee/notification_preview'));

		$sd_div = $sidebar->addHeader(lang('sidenav_settings'));
		$sd_div_list = $sd_div->addBasicList();
		$sd_div_list->addItem(lang('sidenav_settings'), ee('CP/URL', 'addons/settings/hop_pushee/settings'));
		$sd_div_list->addItem(lang('sidenav_notifications_manual'), ee('CP/URL', 'addons/manual/hop_pushee'));
		
	}

	//--------------------------------------------------------------------------
	//	INDEX PAGE
	//--------------------------------------------------------------------------

	function index()
	{
		ee()->functions->redirect(ee('CP/URL', 'addons/settings/hop_pushee/local_activity_log')->compile());
	}

	//--------------------------------------------------------------------------
	//	SETTINGS PAGE
	//--------------------------------------------------------------------------

	function settings()
	{
		$this->build_nav();
		$settings = Hop_pushee_settings_helper::get_settings();
		ee()->load->helper('url');

		$vars = array(
			'cp_page_title' => lang('nav_settings'),
			'base_url' => ee('CP/URL', 'addons/settings/hop_pushee/settings')->compile(),
			'save_btn_text' => lang('settings_save'),
			'save_btn_text_working' => lang('settings_save_working'),
		);

		// Retrieve dropdown and radio buttons custom fields
		$query_custom_fields = ee()->db->select('field_id, field_name, field_label')
			->from('channel_fields')
			->where_in('field_type', array('select', 'radio'))
			->order_by('field_label', 'asc')
			->get();

		$custom_fields = array();
		$custom_fields_choices = array(); // That's for the form choices
		foreach($query_custom_fields->result() as $custom_field_row)
		{
			$custom_fields[$custom_field_row->field_name] = $custom_field_row;
			$custom_fields_choices[$custom_field_row->field_id] = $custom_field_row->field_label.' ('.$custom_field_row->field_name.')';
		}

		$query_custom_cat_fields = ee()->db->select('field_id, field_name, field_label')
			->from('category_fields')
			->order_by('field_label', 'asc')
			->get();

		$custom_cat_fields_choices = array();
		foreach($query_custom_cat_fields->result() as $custom_cat_field_row)
		{
			$custom_cat_fields_choices[$custom_cat_field_row->field_id] = $custom_cat_field_row->field_label.' ('.$custom_cat_field_row->field_name.')';
		}

		// Retrieve all statuses
		$query_statuses = ee()->db->select('status_id, status')
			->from('statuses')
			->order_by('status_order', 'asc')
			->get();

		$statuses_trigger = array();
		foreach ($query_statuses->result() as $status_row)
		{
			$statuses_trigger[$status_row->status_id] = ucfirst($status_row->status);
		}

		// Retrieve templates and template groups
		$query_templates = ee()->db->select('t.template_id, t.template_name, g.group_id, g.group_name')
			->from('templates AS t')
			->join('template_groups AS g', 't.group_id = g.group_id')
			->where('t.template_type', 'webpage')
			->order_by('g.group_name', 'asc')
			->order_by('t.template_name', 'asc')
			->get();

		$templates = array();
		foreach ($query_templates->result() as $template_row)
		{
			$templates[$template_row->template_id] = $template_row->group_name.'/'.$template_row->template_name;
		}

		// Using EE3 API to create config form
		$vars['sections'] = array(
			'settings_section_one_signal' => array(
				array(
					'title' => 'label_onesignal_app_id',
					'desc' => 'label_sub_onesignal_app_id',
					'fields' => array(
						'onesignal_app_id' => array('type' => 'text', 'value' => $settings['onesignal_app_id'])
					)
				),
				array(
					'title' => 'label_onesignal_api_key',
					'desc' => 'label_sub_onesignal_api_key',
					'fields' => array(
						'onesignal_api_key' => array('type' => 'text', 'value' => $settings['onesignal_api_key'])
					)
				),
			),
			'settings_section_integration' => array(
				array(
					'title' => 'label_custom_field_triggers',
					'desc' => 'label_sub_custom_field_triggers',
					'fields' => array(
						'custom_field_triggers' => array('type' => 'checkbox', 'choices' => $custom_fields_choices, 'value' => $settings['custom_field_triggers'])
					)
				),
				array(
					'title' => 'label_custom_category_segment_fields',
					'desc' => 'label_sub_custom_category_segment_fields',
					'fields' => array(
						'custom_category_segment_fields' => array('type' => 'checkbox', 'choices' => $custom_cat_fields_choices, 'value' => $settings['custom_category_segment_fields'])
					)
				),
				array(
					'title' => 'label_entry_statuses_trigger',
					'desc' => 'label_sub_entry_statuses_trigger',
					'fields' => array(
						'entry_statuses_trigger' => array('type' => 'checkbox', 'choices' => $statuses_trigger, 'value' => $settings['entry_statuses_trigger'])
					)
				),
				array(
					'title' => 'label_allow_multiple_notifications',
					'desc' => 'label_sub_allow_multiple_notifications',
					'fields' => array(
						'allow_multiple_notifications' => array('type' => 'yes_no', 'value' => $settings['allow_multiple_notifications'])
					)
				),
			),
			'settings_section_notification' => array(
				array(
					'title' => 'label_notification_icon_url',
					'desc' => 'label_sub_notification_icon_url',
					'fields' => array(
						'notification_icon_url' => array('type' => 'text', 'value' => $settings['notification_icon_url'], 'placeholder' => base_url().'images/icon.png')
					)
				),
				array(
					'title' => 'label_notification_content_template',
					'desc' => 'label_sub_notification_content_template',
					'fields' => array(
						'notification_content_template' => array('type' => 'radio', 'value' => $settings['notification_content_template'], 'choices' => $templates)
					)
				),
				array(
					'title' => '',
					'fields' => array(
						'action' => array('type' => 'hidden', 'value' => 'save_settings')
					)
				),
			)
		);

		if (ee()->input->post('action') == "save_settings")
		{
			$validator = ee('Validation')->make();

			$validator->setRules(array(
				
			));

			$result = $validator->validate($_POST);

			if ($result->isValid())
			{
				// Get back all values, store them in array and save them
				$fields = array();
				foreach ($vars['sections'] as $settings)
				{
					foreach ($settings as $setting)
					{
						foreach ($setting['fields'] as $field_name => $field)
						{
							$fields[$field_name] = ee()->input->post($field_name);
						}
					}
				}

				// We don't want to save that field, it's not a setting
				unset($fields['action']);

				Hop_pushee_settings_helper::save_settings($fields);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('preferences_updated'))
					->addToBody(lang('preferences_updated_desc'))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/hop_pushee/settings'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')
					->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();
			}
		}

		return array(
			'heading'		=> lang('nav_settings'),
			'body'			=> ee('View')->make('hop_pushee:settings')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/hop_pushee')->compile() => lang('hop_pushee_module_name')
			),
		);
	}

	//--------------------------------------------------------------------------
	//	NOTIFICATIONS PAGE
	//--------------------------------------------------------------------------

	function notifications()
	{
		$settings = Hop_pushee_settings_helper::get_settings();

		// If we don't have app id and api key, no need to stay there
		if ($settings['onesignal_app_id'] == '' || $settings['onesignal_api_key'] == '')
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asWarning()
				->withTitle(lang('settings_notice_no_api_label'))
				->addToBody(lang('settings_notice_no_api_desc'))
				->defer();

			ee()->functions->redirect(ee('CP/URL', 'addons/settings/hop_pushee/settings')->compile());
		}

		$this->build_nav();
		require_once PATH_THIRD.'hop_pushee/api_helper.php';

		$api_helper = new Hop_pushee_api_helper($settings['onesignal_app_id'], $settings['onesignal_api_key']);

		if (ee()->input->get('page'))
		{
			$this->_notif_page = intval(ee()->input->get('page'));
		}
		$offset = ($this->_notif_page - 1) * $this->_notif_perpage;
		$notifications_result = $api_helper->get_notifications($this->_notif_perpage, $offset);

		$vars = array();

		$table = ee('CP/Table', array('sortable' => FALSE));

		$table->setColumns(
			array(
				'details',
				'from_addon' => array('encode' => FALSE),
				'date',
				'icon' => array('encode' => FALSE),
				'title',
				'content',
				'notification_link' => array('encode' => FALSE),
			)
		);
		// $table->setNoResultsText('no_notifications_retrieved');

		$data = array();
		if (array_key_exists('notifications', $notifications_result))
		{
			$notification_ids = array();
			foreach ($notifications_result['notifications'] as $notification)
			{
				// var_dump($notification);
				$notification_ids[] = $notification['id'];
				// Get english title when possible
				$title = '';
				if (array_key_exists('en', $notification['headings']))
				{
					$title = $notification['headings']['en'];
				}
				else
				{
					// Get first value of the array
					$title = reset($notification['headings']);
				}
				// Get english content when possible
				$content = '';
				if (array_key_exists('en', $notification['contents']))
				{
					$content = $notification['contents']['en'];
				}
				else
				{
					// Get first value of the array
					$content = reset($notification['contents']);
				}
				$data[] = array(
					array(
						'href' => ee('CP/URL', 'addons/settings/hop_pushee/notification/'.$notification['id']),
						'content' => lang('details'),
						'id' => $notification['id']
					),
					'<abbr title="No local history record found">No</abbr>',
					ee()->localize->format_date('%Y-%m-%d %h:%i %A', $notification['queued_at']),
					'<img style="max-height: 30px;" src="'.$notification['firefox_icon'].'" alt=""/>',
					$title,
					$content,
					'<a href="'.$notification['url'].'" target="_blank">'.$notification['url'].'</a>',
				);
			}

			$pagination = ee('CP/Pagination', $notifications_result['total_count']);
			$pagination->perPage($this->_notif_perpage);
			$pagination->currentPage($this->_notif_page);
			$vars['pagination'] = $pagination->render(ee('CP/URL')->make('addons/settings/hop_pushee/notifications'));

			// Fetch notifications from our history table when they exist
			$query_notif_history = ee()->db->select('*')
				->from('hop_pushee_notifications')
				->where_in('notification_id', $notification_ids)
				->get();

			foreach ($query_notif_history->result() as $notif_history_row)
			{
				foreach ($data as $data_id => $data_row)
				{
					if ($data_row[0]['id'] == $notif_history_row->notification_id)
					{
						$data_row[1] = 'Yes';
						$data[$data_id] = $data_row;
					}
				}
			}
		}

		$table->setData($data);
		$vars['table'] = $table->viewData();

		return array(
			'heading'		=> lang('nav_notifications'),
			'body'			=> ee('View')->make('hop_pushee:notifications')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/hop_pushee')->compile() => lang('hop_pushee_module_name')
			),
		);
	}

	//--------------------------------------------------------------------------
	//	LOCAL HISTORY PAGE
	//--------------------------------------------------------------------------

	function local_activity_log()
	{
		$this->build_nav();
		$vars = array();

		$table = ee('CP/Table', array('sortable' => FALSE));

		$table->setColumns(
			array(
				'details',
				'entry_id',
				'entry_title',
				'date',
				'id',
			)
		);

		if (ee()->input->get('page'))
		{
			$this->_notif_page = intval(ee()->input->get('page'));
		}
		$offset = ($this->_notif_page - 1) * $this->_notif_perpage;

		$pagination = ee('CP/Pagination', ee()->db->count_all('hop_pushee_notifications'));
		$pagination->perPage($this->_notif_perpage);
		$pagination->currentPage($this->_notif_page);
		$vars['pagination'] = $pagination->render(ee('CP/URL')->make('addons/settings/hop_pushee/local_activity_log'));

		$query_history = ee()->db->select('n.*, ct.title')
			->from('hop_pushee_notifications AS n')
			->join('channel_titles AS ct', 'ct.entry_id = n.entry_id')
			->order_by('date_sent', 'desc')
			->limit($this->_notif_perpage, $offset)
			->get();

		$data = array();
		foreach ($query_history->result() as $history_row)
		{
			$details_link = array(
				'href' => ee('CP/URL', 'addons/settings/hop_pushee/notification/'.$history_row->local_history_id),
				'content' => lang('details')
			);

			$data[] = array(
				$details_link,
				array(
					'href' => ee('CP/URL', 'publish/edit/entry/'.$history_row->entry_id),
					'content' => $history_row->entry_id
				),
				$history_row->title,
				ee()->localize->format_date('%Y-%m-%d %h:%i %A', $history_row->date_sent),
				$history_row->notification_id
			);
		}

		$table->setData($data);
		$vars['table'] = $table->viewData();

		return array(
			'heading'		=> lang('nav_history'),
			'body'			=> ee('View')->make('hop_pushee:history')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/hop_pushee')->compile() => lang('hop_pushee_module_name')
			),
		);
	}

	//--------------------------------------------------------------------------
	//	SINGLE NOTIFICATION PAGE
	//--------------------------------------------------------------------------

	function notification()
	{
		$this->build_nav();
		$vars = array();

		require_once PATH_THIRD.'hop_pushee/api_helper.php';

		// Fetch notification from API (notification can be deleted though)
		$settings = Hop_pushee_settings_helper::get_settings();
		$api_helper = new Hop_pushee_api_helper($settings['onesignal_app_id'], $settings['onesignal_api_key']);

		ee()->load->helper('url');
		$uri_string = uri_string();
		$segments = explode('/', $uri_string);
		$notification_id = end($segments);
		$notification = NULL;

		if (ctype_digit($notification_id))
		{
			// That means this is a local history notification id
			$query_history = ee()->db->select('*')
				->from('hop_pushee_notifications')
				->where('local_history_id', intval($notification_id))
				->get();
		}
		else
		{
			// That means this is a One Signal notification id
			$query_history = ee()->db->select('*')
				->from('hop_pushee_notifications')
				->where('notification_id', $notification_id)
				->get();
			
			$notification = $api_helper->get_notification($notification_id);
		}

		if ($query_history->num_rows() > 0)
		{
			$query_history_rows = $query_history->result();
			$notification_history = $query_history_rows[0];
			$vars['notification_history'] = $notification_history;

			// Pre-parse the notes for better formatting
			$notification_history_notes = "<span>".str_replace(array("\n", ":"), array("\n<span>", ":</span>"), $notification_history->notes);
			$vars['notification_history_notes'] = substr($notification_history_notes, 0, -6); //remove the <span> added at the end of the string

			// Try to find the entry
			$query_entry = ee()->db->select('entry_id, title, url_title')
				->from('channel_titles')
				->where('entry_id', $notification_history->entry_id)
				->get();
			if ($query_entry->num_rows() > 0)
			{
				$query_entry_rows = $query_entry->result();
				$entry = $query_entry_rows[0];
				$vars['notification_entry'] = $entry;
			}

			// Try to fetch the notification from OneSignal if we don't have it
			if (!$notification && $notification_history->notification_id != '')
			{
				$notification = $api_helper->get_notification($notification_history->notification_id);
			}
		}

		$vars['notification'] = $notification;
		$vars['notification_id'] = $notification_id;

		return array(
			'heading'		=> lang('nav_notification').$notification_id,
			'body'			=> ee('View')->make('hop_pushee:notification')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/hop_pushee')->compile() => lang('hop_pushee_module_name')
			),
		);
	}

	//--------------------------------------------------------------------------
	//	NOTIFICATION PREVIEW PAGE
	//--------------------------------------------------------------------------

	function notification_preview()
	{
		$this->build_nav();

		// Check if a POST request was submitted
		if (ee()->input->post('entry_id_preview'))
		{
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/hop_pushee/notification_preview/'.ee()->input->post('entry_id_preview'))->compile());
		}

		ee()->load->helper('url');
		$uri_string = uri_string();
		$vars = array();
		$settings = Hop_pushee_settings_helper::get_settings();
		$segments = explode('/', $uri_string);
		$entry_id = end($segments);

		if (ctype_digit($entry_id))
		{
			$query_template = ee()->db->select('t.template_data, t.template_name, g.group_name')
				->from('templates AS t')
				->join('template_groups AS g', 't.group_id = g.group_id')
				->where('template_id', $settings['notification_content_template'])
				->get();

			$query_entry = ee()->db->select('entry_id, title, url_title, channel_id')
				->from('channel_titles')
				->where('entry_id', $entry_id)
				->get();

			if ($query_entry->num_rows() > 0)
			{
				$vars['entry_id'] = $entry_id;
				$entry_rows = $query_entry->result();
				$entry_row = $entry_rows[0];

				$vars['entry'] = $entry_row;
			}

			if ($query_template->num_rows() > 0 && $query_entry->num_rows() > 0)
			{
				$vars['notification_content'] = Hop_pushee_helper::parse_notification_template($settings['notification_content_template'], $entry_id);
				$icon_url = trim(Hop_pushee_helper::get_icon_cache());
				if ($icon_url == '' || $icon_url == NULL)
				{
					$icon_url = $settings['notification_icon_url'];
				}
				$vars['notif_icon_url'] = $icon_url;
				$notif_title = trim(Hop_pushee_helper::get_title_cache());
				if ($notif_title == '' || $notif_title == NULL)
				{
					$notif_title = $entry_row->title;
				}
				$vars['notif_title'] = $notif_title;
				$notif_url = trim(Hop_pushee_helper::get_url_cache());
				if ($notif_url == '' || $notif_url == NULL)
				{
					// Construct entry URL like {comment_url_title_auto_path} does
					$query_channel = ee()->db->select('channel_id, channel_name, comment_url')
						->from('channels')
						->where('channel_id', $entry_row->channel_id)
						->get();

					if ($query_channel->num_rows() == 0)
					{
						// Channel not found, that should not be possible, but we never know
						return;
					}
					$query_channel_rows = $query_channel->result();
					$channel_row = $query_channel_rows[0];

					// The comment URL can contain the {base_url} tag, so we need to replace it
					$notif_url = str_replace('{base_url}', base_url(), $channel_row->comment_url.'/'.$entry_row->url_title);
					$notif_url = reduce_double_slashes($notif_url);
				}
				$vars['notif_url'] = $notif_url;
			}
			else
			{
				if ($query_entry->num_rows() == 0)
				{
					ee('CP/Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('notif_preview_entry_not_found'))
						->addToBody(lang('notif_preview_entry_not_found_desc'))
						->now();
				}
				else
				{
					ee('CP/Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('notif_preview_template_not_found'))
						->addToBody(lang('notif_preview_template_not_found_desc'))
						->now();
				}
			}
		}

		return array(
			'heading'		=> lang('nav_notification_preview'),
			'body'			=> ee('View')->make('hop_pushee:notification_preview')->render($vars),
			'breadcrumb'	=> array(
			  ee('CP/URL', 'addons/settings/hop_pushee')->compile() => lang('hop_pushee_module_name')
			),
		);
	}

}
// END CLASS
