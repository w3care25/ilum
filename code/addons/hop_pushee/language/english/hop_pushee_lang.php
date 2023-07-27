<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
	'hop_pushee_module_name'					=> 'Hop PushEE',
	'hop_pushee_module_description'				=> 'Send push notification via a third party service',

	'entry_id'								=> 'Entry ID',
	'entry_title'							=> 'Title',
	'from_addon'							=> 'From Add-on',
	'title'									=> 'Title',
	'content'								=> 'Content',
	'notification_link'						=> 'Notification Link',

	'sidenav_notifications'					=> 'Notifications',
	'sidenav_all_notifications'				=> 'OneSignal Log',
	'sidenav_history'						=> 'Local Activity Log',
	'sidenav_notification_preview'			=> 'Preview Notification',
	'sidenav_settings'						=> 'Settings',
	'sidenav_notifications_manual'			=> 'Documentation',

	'nav_settings'							=> 'Settings',
	'nav_notifications'						=> 'OneSignal Log',
	'nav_notification'						=> 'Notification Details ID: ',
	'nav_history'							=> 'Local Activity Log',
	'nav_notification_preview'				=> 'Preview a Notification',

	'settings_section_one_signal'			=> 'One Signal settings',
	'settings_section_integration'			=> 'Integration into ExpressionEngine',
	'settings_section_notification'			=> 'Notifications Format',

	'label_onesignal_app_id'				=> 'OneSignal App Id',
	'label_sub_onesignal_app_id'			=> 'Find it in your App Settings on your OneSignal dashboard',
	'label_onesignal_api_key'				=> 'OneSignal API Key',
	'label_sub_onesignal_api_key'			=> 'Find it in your App Settings on your OneSignal dashboard',
	'label_custom_field_triggers'			=> 'Push Notification Trigger Field(s)',
	'label_sub_custom_field_triggers'		=> 'Trigger fields can either be a select dropdown or a radio button field. They nees to have three values: <strong>Don\'t Send</strong>, <strong>Send</strong>, <strong>Sent</strong>. The field value must be set to Send to trigger a notification. If the notification is sent, the field value will be changed to Sent.',
	'label_custom_category_segment_fields'	=> 'Category -> User Segments',
	'label_sub_custom_category_segment_fields' => 'To send to a particular segment of your recipients, set up a Category Custom Field, and set the value of the field to the name of a OneSignal User Segment.<br/>If a notification is sent, it will send to all User Segment category(s) assigned to that entry.',
	'label_entry_statuses_trigger'			=> 'Entry Statuses Trigger',
	'label_sub_entry_statuses_trigger'		=> 'Select which status(es) should trigger a notification',
	'label_notification_icon_url'			=> 'Notification Icon URL',
	'label_sub_notification_icon_url'		=> 'The URL must be absolute. Please see the <a href="https://documentation.onesignal.com/docs/web-push-notification-icons#section-icon-requirements" target="_blank">official icon documentation</a> for more details.',
	'label_notification_content_default'	=> 'Default content for notifications',
	'label_sub_notification_content_default'=> 'OneSignal requires notifications to have content. If no content can be found from the entry, this default content will be used.',
	'placeholder_notification_content_default' => 'Click on the notification to read the article',
	'label_notification_content_template'	=> 'Content Template',
	'label_sub_notification_content_template' => 'Choose a template that will be parsed and used as the content for the notification. If empty, the notification will not be sent.',
	'label_allow_multiple_notifications'					=> 'Allow multiple notifications',
	'label_sub_allow_multiple_notifications'				=> 'By default, the add-on will not send a new notification for an entry that has already triggered a notification to be sent.',

	'settings_save'							=> 'Save',
	'settings_save_working'					=> 'Saving...',
	'settings_saved_success'				=> 'Settings have been saved successfully',

	'settings_notice_no_api_label'			=> 'OneSignal API Setup Required',
	'settings_notice_no_api_desc'			=> 'Please fill up the OneSignal fields.',

	'view_all_notif_list_desc'				=> 'This list contains all notifications sent from your OneSignal account, even ones that were not sent by Hop PushEE.',
	'view_all_notif_delete_notice'			=> 'OneSignal periodically deletes records of API notifications older than 30 days.',
	'view_history_list_desc'				=> 'This list contains all notifications sent by the add-on.',
	'view_no_local_notif_data_found'		=> 'Could not find any local history record of that notification. That means this notification was probably not triggered by the add-on.',
	'view_no_api_notif_data_found'			=> 'Could not retrieve details of this notification from OneSignal. It may have been deleted from their records.',

	'notif_preview_entry_not_found'			=> 'Entry not found',
	'notif_preview_entry_not_found_desc'	=> 'The entry you are trying to preview was not found in the database.',
	'notif_preview_template_not_found'		=> 'Template not found',
	'notif_preview_template_not_found_desc'	=> 'The template used to generate the preview was not found.',
	'notif_preview_note'					=> 'Note: Notifications are text-only. All HTML will be removed. Specific formatting will not display, and the actual display may vary slightly from browser to browser. But you can use emojis 😉',

	'' => ''
);