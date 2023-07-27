<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hopsuite/config.php';
require_once PATH_THIRD."hopsuite/libraries/TwitterAPIWrapper.php";
require_once PATH_THIRD."hopsuite/libraries/FacebookAPIWrapper.php";
require_once PATH_THIRD."hopsuite/libraries/InstagramAPIWrapper.php";

class Hopsuite_helper
{
	private static $_settings_table_name = "hopsuite_settings";
	private static $_settings;

	private static function _get_default_settings()
	{
		return array(
			'cache_ttl'					=> '5',
			'facebook_app_token'		=> '',
			'facebook_app_id'			=> '',
			'facebook_app_secret'		=> '',
			'twitter_token'				=> '',
			'twitter_token_secret'		=> '',
			'twitter_consumer_key'		=> '',
			'twitter_consumer_secret'	=> '',
			'instagram_access_token'	=> ''
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
				$settings[$row["setting_name"]] = $row["value"];
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
	 * Get the social timeline with given parameters
	 * Will load cache if exist, if not, load from social networks using APIs
	 * @param  array $timeline_settings Array containing all settings needed for generating a timeline
	 * @return array					   An array containing all post/tweets, ordered by date, most recent first
	 */
	public static function _get_timeline($timeline_settings)
	{
		$twitter_screen_name = (array_key_exists('twitter_screen_name', $timeline_settings))?$timeline_settings['twitter_screen_name']:NULL;
		$twitter_search_query = (array_key_exists('twitter_search_query', $timeline_settings))?$timeline_settings['twitter_search_query']:NULL;
		$twitter_include_rts = (array_key_exists('twitter_include_rts', $timeline_settings))?$timeline_settings['twitter_include_rts']:FALSE;
		$twitter_count = (array_key_exists('twitter_count', $timeline_settings))?$timeline_settings['twitter_count']:NULL;
		$facebook_page_id = (array_key_exists('facebook_page_id', $timeline_settings))?$timeline_settings['facebook_page_id']:NULL;
		$facebook_count = (array_key_exists('facebook_count', $timeline_settings))?$timeline_settings['facebook_count']:NULL;
		$instagram_user_id = (array_key_exists('instagram_user_id', $timeline_settings))?$timeline_settings['instagram_user_id']:NULL;
		$instagram_count = (array_key_exists('instagram_count', $timeline_settings))?$timeline_settings['instagram_count']:NULL;

		$cache_key = "";

		//Api limit
		if ($facebook_count > 25)
		{
			$facebook_count = 25;
		}

		if ($twitter_count > 200)
		{
			$twitter_count = 200;
			//no kidding
		}
		
		if ($instagram_count > 50)
		{
			// Sandbox mode: limit is 20
			// TODO: no idea what the live mode limit is
			$instagram_count = 50;
		}

		//Parameters validation
		$get_twitter = FALSE;
		if ($twitter_count > 0
			&& (
				($twitter_screen_name != NULL && $twitter_screen_name != '')
				|| ($twitter_search_query != NULL && $twitter_search_query != '')
			)
		)
		{
			$get_twitter = TRUE;
		}
		$get_facebook = FALSE;
		if ($facebook_count > 0 && $facebook_page_id != NULL && $facebook_page_id != '')
		{
			$get_facebook = TRUE;
		}
		$get_instagram = FALSE;
		if ($instagram_count > 0 && $instagram_user_id != NULL && $instagram_user_id != '')
		{
			$get_instagram = TRUE;
		}


		if (!$get_facebook && !$get_twitter && !$get_instagram)
		{
			return "";
		}

		//Creating unique cache key for this configuration
		$cache_key = md5(serialize(func_get_args()));

		if ($timeline_cache = ee()->cache->get('/'.__CLASS__.'/'.$cache_key))
		{
			ee()->TMPL->log_item(__CLASS__ . ': Fetching social timeline from cache');
			//Cache found, return it
			return $timeline_cache;
		}
		else
		{
			//No cache, let's use APIs !

			//Add-on settings
			$settings = self::get_settings();

			//Our posts will be stored in there
			$timeline = array();
			$timeline_facebook = array();
			$timeline_twitter = array();
			$timeline_instagram = array();

			// Verify that we have app id and app secret
			if ($get_facebook && $settings['facebook_app_id'] != "" && $settings['facebook_app_secret'] != "")
			{
				//Let's get those Facebook posts
				// Facebook ID can be a list of ids separated by |
				$facebook_page_ids = explode('|', $facebook_page_id);
				foreach ($facebook_page_ids as $fb_page_id)
				{
					if ($fb_page_id != '')
					{
						$data = self::_get_facebook_timeline($settings['facebook_app_id'], $settings['facebook_app_secret'], $fb_page_id, $facebook_count);

						if (!isset($data->error))
						{
							ee()->TMPL->log_item(__CLASS__ . ': Successfully fetched Facebook posts ('.count($data->data).' posts)');
							foreach ($data->data as $post)
							{
								if (isset($post->created_time))
								{
									$data_post = new DateTime($post->created_time);
								}
								else
								{
									$data_post = new DateTime();
								}
								$post_timeline = array(
									'timestamp' => $data_post->getTimestamp(),
									'facebook'  => $post
								);
								// We don't care about how it's added, all posts are sorted at the end of the process
								$timeline_facebook[] = $post_timeline;
							}
						}
						else
						{
							// Error when trying to get Facebook posts
							// Log that so dev will know what's going on
							$error = $data->error;

							$message = 'Hopsuite: Error with Facebook API when fetching data for page '.$fb_page_id.': ';
							if (isset($error->code))
							{
								$message .= $error->code.' ';
							}
							if (isset($error->message))
							{
								$message .= $error->message;
							}
							ee()->logger->developer($message);

							ee()->TMPL->log_item(__CLASS__ . ': Error when fetching Facebook posts (see developer log for more)');
						}
					}
				} // END foreach facebook page ids
			} // END if facebook app id and app secret

			if ($get_twitter)
			{
				//Let's get those tweets

				$twit_token				= $settings['twitter_token'];
				$twit_token_secret		= $settings['twitter_token_secret'];
				$twit_consumer_key		= $settings['twitter_consumer_key'];
				$twit_consumer_secret	= $settings['twitter_consumer_secret'];

				//Get Twitter page posts
				$twit_settings = array (
					'oauth_access_token'		=> $twit_token,
					'oauth_access_token_secret' => $twit_token_secret,
					'consumer_key'				=> $twit_consumer_key,
					'consumer_secret'			=> $twit_consumer_secret
				);

				// Query to get user timeline
				if ($twitter_screen_name != NULL && $twitter_screen_name != "")
				{
					$params = array(
						"screen_name"	=> $twitter_screen_name,
						"count"			=> $twitter_count,
						"include_rts"	=> ($twitter_include_rts?'true':'false')
					);

					$twitter_api = new TwitterAPIWrapper($twit_settings);
					// https://dev.twitter.com/rest/reference/get/statuses/user_timeline
					$json = $twitter_api->get("statuses/user_timeline.json", $params );

					// Data is an array of Tweets
					$data = json_decode($json);

					if (isset($data->errors))
					{
						ee()->logger->developer('Hopsuite error when getting tweets : '. $data->errors[0]->code . ' - ' . $data->errors[0]->message);
						ee()->TMPL->log_item(__CLASS__ . ': Error when fetching tweets (see developer log for more)');
						$data = NULL;
					}
					else
					{
						ee()->TMPL->log_item(__CLASS__ . ': Successfully fetched tweets');
					}
				}
				//Query to search for tweets
				else
				{
					$params = array(
						"q"				=> $twitter_search_query,
						"count"			=> $twitter_count,
						"result_type"	=> 'recent'
						// "result_type"	=> 'popular'
					);

					$twitter_api = new TwitterAPIWrapper($twit_settings);
					// https://dev.twitter.com/rest/reference/get/search/tweets
					$json = $twitter_api->get("search/tweets.json", $params );

					// Adjustement to get an array of tweets
					$data = json_decode($json);
					if (isset($data->errors))
					{
						ee()->logger->developer('Hopsuite error when getting tweets : '. $data->errors[0]->code . ' - ' . $data->errors[0]->message);
						ee()->TMPL->log_item(__CLASS__ . ': Error when fetching tweets (see developer log for more)');
						$data = NULL;
					}
					else
					{
						ee()->TMPL->log_item(__CLASS__ . ': Successfully fetched tweets');
						$data = $data->statuses;
					}

				}

				if ($data != NULL)
				{
					foreach ($data as $tweet)
					{
						$date_tweet = new DateTime($tweet->created_at);
						$tweet_timeline = array(
							'timestamp'	=> $date_tweet->getTimestamp(),
							'tweet'	 	=> $tweet
						);
						$timeline_twitter[] = $tweet_timeline;
					}
				}
			}

			if ($get_instagram && $settings['instagram_access_token'] != "")
			{
				$access_token = $settings['instagram_access_token'];
				$params = array('count' => $instagram_count);
				$instagram_api = new InstagramAPIWrapper($access_token);
				$json = $instagram_api->get('users/'.$instagram_user_id.'/media/recent/', $params );

				// Data is an array of Tweets
				$data = json_decode($json);

				if (isset($data->meta) && isset($data->meta->code) && $data->meta->code != 200)
				{
					ee()->logger->developer('Hopsuite error when getting instagram posts : code '. $data->meta->code . ' - ' . $data->meta->error_message);
					ee()->TMPL->log_item(__CLASS__ . ': Error when fetching instagram posts (see developer log for more)');
					$data = NULL;
				}

				if ($data != NULL)
				{
					ee()->TMPL->log_item(__CLASS__ . ': Successfully fetched instagram posts');

					foreach($data->data as $post)
					{
						$date_post = new DateTime();
						$date_post->setTimestamp($post->created_time);
						$post_timeline = array(
							'timestamp'	=> $date_post->getTimestamp(),
							'instagram'	=> $post
						);
						$timeline_instagram[] = $post_timeline;
					}
				}
			}

			$timeline = array_merge($timeline_twitter, $timeline_facebook, $timeline_instagram);
			usort($timeline, function($a, $b){
				return $a['timestamp'] < $b['timestamp'];
			});

			//Our timeline is ready, save it in cache
			if (isset(ee()->cache))
			{
				ee()->cache->save('/'.__CLASS__.'/'.$cache_key, $timeline, $settings['cache_ttl']*60);
			}

			return $timeline;
		}// endif no cache found
	}

	public static function _get_facebook_timeline($facebook_app_id, $facebook_app_secret, $facebook_page_id, $facebook_count)
	{
		//Get Facebook page posts
		// Note: we specify the fields to have access to number of comments and likes (yes, if you don't do that, you don't have the counts...)
		$post_params = array(
			"format"		=> "json",
			"limit"			=> $facebook_count,
			"fields"		=> 'comments.limit(1).summary(true),likes.limit(1).summary(true),message,picture,link,from,shares,created_time',
		);

		// See doc about access tokens and API calls https://developers.facebook.com/docs/facebook-login/access-tokens#apptokens
		// $api_params = array("access_token" => $facebook_token);
		$api_params = array("access_token" => $facebook_app_id.'|'.$facebook_app_secret);
		$facebook_api = new FacebookAPIWrapper($api_params);
		// This API call can be either /feed or /posts, not sure what's best
		// I think /feed might include external posts on a page
		// see https://developers.facebook.com/docs/graph-api/reference/v2.10/page/feed
		$result = $facebook_api->get($facebook_page_id."/posts", $post_params);

		$data = json_decode($result);

		return $data;
	}
}