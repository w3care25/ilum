<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/config.php';

class Hop_pushee_helper
{

	/**
	 * Generate the notification content text from an entry and a template
	 *
	 * @param integer $template_id
	 * @param integer $entry_id
	 * @return mixed The notification content string or NULL if failed
	 */
	public static function parse_notification_template($template_id, $entry_id)
	{
		ee()->load->library('logger');

		$query_template = ee()->db->select('t.template_data, t.template_name, g.group_name')
			->from('templates AS t')
			->join('template_groups AS g', 't.group_id = g.group_id')
			->where('template_id', $template_id)
			->get();

		$query_entry = ee()->db->select('*')
			->from('channel_titles')
			->where('entry_id', $entry_id)
			->get();

		if ($query_entry->num_rows() == 0)
		{
			ee()->logger->developer('Hop PushEE: Error when trying to generate notification content from entry: Entry '.$entry_id.' not found');
			return NULL;
		}

		if ($query_template->num_rows() == 0)
		{
			ee()->logger->developer('Hop PushEE: Error when trying to generate notification content from entry: Template '.$template_id.' not found');
			return NULL;
		}

		$template_rows = $query_template->result();
		$template_row = $template_rows[0];
		$entry_rows = $query_entry->result();
		$entry_row = $entry_rows[0];

		if ( ! class_exists('EE_Template'))
		{
			ee()->load->library('template');
		}
		$template_parser = new EE_Template();

		// This is mandatory, TMPL needs to be set for modules and plugins to work when parsing the template
		// ee()->TMPL = $template_parser;
		// ee()->remove('TMPL');
		if (isset(ee()->TMPL))
		{
			// If the template context has been setup already, keep it for later
			$old_parser = ee()->TMPL;
		}
		ee()->set('TMPL', $template_parser);

		// Use EE API to parse the template
		// This works, but we need to be able to specify which entry to parse with the template
		// $template_parser->run_template_engine($template_row->group_name, $template_row->template_name);
		// $parsed_template = $template_parser->template;
		// $parsed_template = $template_parser->final_template;

		// Instead, we're by-passing some of the EE API methods to run our modified template content
		// EE API method run_template_engine() -> [fetch_and_parse() and parse_globals()]
		// EE API method fetch_and_parse() -> [fetch_template(), parse() and run template_post_parse hook]
		// By-pass the first steps, directly run parse() and then parse_globals()
		// TODO: Run template_post_parse hook ?
		// Add the exp:channel:entries tag to parse the template with our current entry
		$template_str = '{exp:channel:entries entry_id="'.$entry_id.'" status="'.$entry_row->status.'" dynamic="no"}'.$template_row->template_data.'{/exp:channel:entries}';
		$template_parser->parse($template_str);
		$parsed_template = $template_parser->parse_globals($template_parser->final_template);

		if (isset($old_parser))
		{
			ee()->set('TMPL', $old_parser);
		}
		else
		{
			ee()->remove('TMPL');
		}

		return trim($parsed_template);
	}

	/**
	 * Add icon URL to the temporary cache
	 *
	 * @param string $icon_url
	 * @return void
	 */
	public static function set_icon_cache($icon_url)
	{
		ee()->session->set_cache('hop_pushee', 'notification_icon', $icon_url);
	}

	/**
	 * Retrieve icon URL from the temporary cache
	 *
	 * @return void
	 */
	public static function get_icon_cache()
	{
		return ee()->session->cache('hop_pushee', 'notification_icon');
	}

	/**
	 * Add notification title to the temporary cache
	 *
	 * @param string $title
	 * @return void
	 */
	public static function set_title_cache($title)
	{
		ee()->session->set_cache('hop_pushee', 'notification_title', $title);
	}

	/**
	 * Retrieve the notification title from the temporary cache
	 *
	 * @return void
	 */
	public static function get_title_cache()
	{
		return ee()->session->cache('hop_pushee', 'notification_title');
	}

	/**
	 * Add notification URL to the temporary cache
	 *
	 * @param string $url
	 * @return void
	 */
	public static function set_url_cache($url)
	{
		ee()->session->set_cache('hop_pushee', 'notification_url', $url);
	}

	/**
	 * Retrieve the notification url from the temporary cache
	 *
	 * @return void
	 */
	public static function get_url_cache()
	{
		return ee()->session->cache('hop_pushee', 'notification_url');
	}
}