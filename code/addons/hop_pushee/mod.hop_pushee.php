<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/settings_helper.php';
require_once PATH_THIRD.'hop_pushee/helper.php';

class Hop_pushee
{
	/**
	 * Allows the user to set the notification icon directly in the notification content template.
	 * That way, the user has more control over the icon.
	 * {exp:hop_pushee:set_icon}http://site.com/icon.png{/exp:hop_pushee:set_icon}
	 *
	 * @return void
	 */
	public function set_icon()
	{
		$icon_url = ee()->TMPL->tagdata;

		// Set that into cache, so we can retrieve it from the add-on extension
		Hop_pushee_helper::set_icon_cache($icon_url);

		return '';
	}

	/**
	 * Allows the user to set the notification title directly from the notification content template
	 * {exp:hop_pushee:set_title}Notification Title{/exp:hop_pushee:set_title}
	 *
	 * @return void
	 */
	public function set_title()
	{
		$title = ee()->TMPL->tagdata;

		// Set that into cache, so we can retrieve it from the add-on extension
		Hop_pushee_helper::set_title_cache($title);

		return '';
	}

	/**
	 * Allows the user to set the notification URL directly from the notification content template
	 * {exp:hop_pushee:set_url}http://site.com/news/article{/exp:hop_pushee:set_url}
	 *
	 * @return void
	 */
	public function set_url()
	{
		$url = ee()->TMPL->tagdata;

		// Set that into cache, so we can retrieve it from the add-on extension
		Hop_pushee_helper::set_url_cache($url);

		return '';
	}
}