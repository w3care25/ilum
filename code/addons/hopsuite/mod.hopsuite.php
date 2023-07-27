<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hopsuite/helper.php';

class Hopsuite
{
	var $return_data	= '';

	private $facebook_page_id;
	private $facebook_will_fetch = FALSE;
	private $facebook_count;
	private $twitter_username;
	private $twitter_search_query;
	private $twitter_include_rts = TRUE;
	private $twitter_count;
	private $twitter_will_fetch = FALSE;
	private $instagram_user_id;
	private $instagram_count;
	private $instagram_will_fetch = FALSE;

	/**
	 * Displays a simple list of
	 * @return [type] [description]
	 */
	function simple()
	{
		ee()->load->library('logger');
		$this->_process_parameters();

		$timeline = Hopsuite_helper::_get_timeline(array(
			'twitter_screen_name'	=> $this->twitter_screen_name,
			'twitter_search_query'	=> $this->twitter_search_query,
			'twitter_count'			=> $this->twitter_count,
			'facebook_page_id'		=> $this->facebook_page_id,
			'facebook_count'		=> $this->facebook_count,
			'instagram_user_id'		=> $this->instagram_user_id,
			'instagram_count'		=> $this->instagram_count
		));

		$tag_data = '<li class="hopsuite_item hopsuite_{social_network}"><span class="hopsuite_content">{text_url}</span> <span class="hopsuite_credit"><span class="hopsuite_social_network">{social_network}</span> <span class="hopsuite_joiner">on</span> <span class="hopsuite_post_date" data-date="{date}">{date format="%Y-%m-%d %H:%i:%s"}</span></span></li>';

		if ($timeline != null && count($timeline) != 0)
		{
			$this->return_data = '<ul class="hopsuite_list">'.$this->_process_tag_pair($timeline, $tag_data).'</ul>';
		}
		else
		{
			$this->return_data = "";
		}

		return $this->return_data;
	}

	/**
	 * Tag pair with accessible tags for social posts data
	 * @return string Parsed tag content
	 */
	function timeline()
	{
		ee()->load->library('logger');
		$this->_process_parameters();

		$timeline = Hopsuite_helper::_get_timeline(array(
			'twitter_screen_name'	=> $this->twitter_screen_name,
			'twitter_search_query'	=> $this->twitter_search_query,
			'twitter_include_rts'	=> $this->twitter_include_rts,
			'twitter_count'			=> $this->twitter_count,
			'facebook_page_id'		=> $this->facebook_page_id,
			'facebook_count'		=> $this->facebook_count,
			'instagram_user_id'		=> $this->instagram_user_id,
			'instagram_count'		=> $this->instagram_count
		));

		if ($timeline != null && count($timeline) != 0)
		{
			$this->return_data = $this->_process_tag_pair($timeline);
		}
		else
		{
			$this->return_data = "";
		}

		return $this->return_data;
	}

	/**
	 * Process all tag parameters
	 */
	private function _process_parameters()
	{
		$this->twitter_screen_name = ee()->TMPL->fetch_param('twitter_username');
		$this->twitter_search_query = ee()->TMPL->fetch_param('twitter_search_query');
		$this->twitter_include_rts = ee()->TMPL->fetch_param('twitter_include_rts', 'yes');
		$this->twitter_include_rts = ($this->twitter_include_rts == 'no'?FALSE:TRUE);

		if ($this->twitter_screen_name || $this->twitter_search_query)
		{
			$this->twitter_will_fetch = TRUE;
		}
		$this->facebook_page_id = ee()->TMPL->fetch_param('facebook_feed_id');
		if ($this->facebook_page_id)
		{
			$this->facebook_will_fetch = TRUE;
		}
		$this->instagram_user_id = ee()->TMPL->fetch_param('instagram_user_id');
		if ($this->instagram_user_id)
		{
			$this->instagram_will_fetch = TRUE;
		}
		$this->_set_counts();
	}

	/**
	 * Process count tag parameters for each social network
	 */
	private function _set_counts()
	{
		$divided_by = 0;

		$total_count = -1;
		$total_count_str = ee()->TMPL->fetch_param('total_count');
		if ($total_count_str != "" && is_numeric($total_count_str))
		{
			$total_count = intval($total_count_str);
		}

		$twitter_count = 0;
		if ($this->twitter_will_fetch)
		{
			$divided_by++;
			$twitter_count_str = ee()->TMPL->fetch_param('twitter_count');
			if ($twitter_count_str != "" && is_numeric($twitter_count_str))
			{
				$twitter_count = intval($twitter_count_str);
			}
		}

		$facebook_count = 0;
		if ($this->facebook_will_fetch)
		{
			$divided_by++;
			$facebook_count_str = ee()->TMPL->fetch_param('facebook_count');
			if ($facebook_count_str != "" && is_numeric($facebook_count_str))
			{
				$facebook_count = intval($facebook_count_str);
			}
		}

		$instagram_count = 0;
		if ($this->instagram_will_fetch)
		{
			$divided_by++;
			$instagram_count_str = ee()->TMPL->fetch_param('instagram_count');
			if ($instagram_count_str != '' && is_numeric($instagram_count_str))
			{
				$instagram_count = intval($instagram_count_str);
			}
		}


		if ($facebook_count == 0 && $twitter_count == 0 && $instagram_count == 0)
		{
			if ($total_count == -1)
			{
				$this->facebook_count = 0;
				$this->twitter_count = 0;
				$this->instagram_count = 0;
				// Let's fetch 5 posts for each network
				if ($this->facebook_will_fetch)
				{
					$this->facebook_count = 5;
				}
				if ($this->twitter_will_fetch)
				{
					$this->twitter_count = 5;
				}
				if ($this->instagram_will_fetch)
				{
					$this->instagram_count = 5;
				}
				return;
			}

			$single_network_count = floor($total_count/$divided_by);
			if ($this->twitter_will_fetch)
			{
				$this->twitter_count = $single_network_count;
			}
			if ($this->facebook_will_fetch)
			{
				$this->facebook_count = $single_network_count;
			}
			if ($this->instagram_will_fetch)
			{
				$this->instagram_count = $single_network_count;
			}
			return;
		}

		if ($this->facebook_will_fetch && $facebook_count == 0)
		{
			$facebook_count = 5;
		}
		if ($this->twitter_will_fetch && $twitter_count == 0)
		{
			$twitter_count = 5;
		}
		if ($this->instagram_will_fetch && $instagram_count == 0)
		{
			$instagram_count = 5;
		}

		$this->twitter_count = $twitter_count;
		$this->facebook_count = $facebook_count;
		$this->instagram_count = $instagram_count;
	}

	/**
	 * Process the tag or tag pair using the data we got
	 * @param  array $timeline Our timeline with twitter/facebook posts
	 * @return string		   processed template with social posts data
	 */
	private function _process_tag_pair($timeline, $tag_data = NULL)
	{
		if ($tag_data == NULL)
		{
			$tag_data = ee()->TMPL->tagdata;
		}
		$this->return_data = "";
		$timeline_tags = array();
		$facebook_count = 0;
		$twitter_count = 0;
		$instagram_count = 0;
		foreach($timeline as $post)
		{
			//Convert post to tag array
			$social_post = $this->_setup_tags($post);
			if ($social_post['social_network'] == "Facebook")
			{
				$facebook_count++;
				$social_post['facebook_count'] = $facebook_count;
			}
			else if ($social_post['social_network'] == "Twitter")
			{
				$twitter_count++;
				$social_post['twitter_count'] = $twitter_count;
			}
			else if ($social_post['social_network'] == "Instagram")
			{
				$instagram_count++;
				$social_post['instagram_count'] = $instagram_count;
			}

			$timeline_tags[] = $social_post;
		}
		//Let EE do the job
		return ee()->TMPL->parse_variables($tag_data, $timeline_tags);
	}

	/**
	 * This is getting specific data from social posts and set it up as an array for template tags
	 * @param  array $post array containing item timestamp and item data
	 * @return array	   array of tags ready to be parsed
	 */
	private function _setup_tags($post)
	{
		$tags = array(
			'text'				=> "",
			'text_url'			=> "",
			'date'				=> "",
			'social_network'	=> "",
			'likes_count'		=> 0, // Fb and Insta only
			'comments_count'	=> 0, // Fb and Insta only
			'retweets_count'	=> 0,
			'favorites_count'	=> 0,
			'from'				=> "",
			'screen_name'		=> "", // Twi and Insta only
			'profile_picture'	=> "", // Twi and Insta only
			'profile_url'		=> "",
			'picture'			=> "",
			'post_link'			=> "",
			// Specific Twitter variables
			'retweet_url'		=> "",
			'favorite_url'		=> "",
			'reply_url'			=> "",
			// Specific Facebook variables
			'shares_count'		=> 0,
			// Specific Instagram variables
			'picture_hd'		=> "",
		);
		if (array_key_exists("tweet", $post))
		{
			$tweet = $post["tweet"];
			// Avoid error if tweet data isn't correct
			if (isset($tweet->created_at) && isset($tweet->text))
			{
				$tweet_date = new DateTime($tweet->created_at);

				//Replace shortened urls to full ones
				$tweet_text = $tweet->text;
				$tweet_text_url = $tweet->text;
				foreach ($tweet->entities->urls as $tweet_url)
				{
					$tweet_text = str_replace($tweet_url->url, $tweet_url->expanded_url, $tweet_text);
					$tweet_text_url = str_replace($tweet_url->url, '<a href="'.$tweet_url->expanded_url.'">'.$tweet_url->display_url.'</a>', $tweet_text_url);
				}
				//Media are also shortened sometime so we'll change them too
				if (isset($tweet->entities->media) && is_array($tweet->entities->media))
				{
					//media is an array of medias, get the first picture of it
					foreach ($tweet->entities->media as $tweet_media)
					{
						$tweet_text = str_replace($tweet_media->url, $tweet_media->media_url_https, $tweet_text);
						$tweet_text_url = str_replace($tweet_media->url, '<a href="'.$tweet_media->expanded_url.'">'.$tweet_media->display_url.'</a>', $tweet_text_url);
					}
				}

				$tags['text']			= $tweet_text;
				//$tags['text_url']	 = preg_replace('!(http|ftp|scp)(s)?:\/\/[a-zA-Z0-9.?%=\-&_/]+!', "<a href=\"\\0\">\\0</a>", $tweet_text);
				$tags['text_url']		= $tweet_text_url;
				$tags['date']			= $tweet_date->getTimestamp();
				$tags['social_network']	= "Twitter";
				$tags['retweets_count']	= $tweet->retweet_count;
				$tags['favorites_count']= $tweet->favorite_count;
				$tags['post_url']		= 'https://twitter.com/'.$tweet->user->screen_name.'/status/'.$tweet->id_str;

				$tags['retweet_url']	= 'https://twitter.com/intent/retweet?tweet_id='.$tweet->id;
				$tags['favorite_url']	= 'https://twitter.com/intent/favorite?tweet_id='.$tweet->id;
				$tags['reply_url']		= 'https://twitter.com/intent/tweet?in_reply_to='.$tweet->id;

				//User data
				$tags['from']			= $tweet->user->screen_name;
				$tags['screen_name']	= $tweet->user->name;
				$tags['profile_picture']= $tweet->user->profile_image_url_https;
				$tags['profile_url']	= 'https://twitter.com/'.$tweet->user->screen_name;

				if (isset($tweet->entities->media) && is_array($tweet->entities->media))
				{
					//media is an array of medias, get the first picture of it
					foreach ($tweet->entities->media as $tweet_media)
					{
						if ($tweet_media->type = "photo")
						{
							$tags['picture'] = $tweet_media->media_url_https;
							break;
						}
					}
				}
			} //ENDIF isset($tweet->created_at) && isset($tweet->text)
		}
		else if (array_key_exists("facebook", $post))
		{
			$facebook = $post['facebook'];

			if (isset($facebook->created_time))
			{
				$facebook_date = new DateTime($facebook->created_time);
			}
			else
			{
				$facebook_date = new DateTime();
			}

			$facebook_text = "";
			$facebook_and_link = "";
			if (isset($facebook->message))
			{
				$facebook_text = $facebook->message;
				$facebook_and_link = $facebook->message;
			}

			if (isset($facebook->link) && $facebook->link != "")
			{
				$facebook_and_link .= " ".$facebook->link;
			}
			$tags['text']				= $facebook_text;
			$tags['text_url']			= preg_replace('!(http|ftp|scp)(s)?:\/\/[a-zA-Z0-9.?%=\-&_/]+!', "<a href=\"\\0\">\\0</a>", $facebook_and_link);
			$tags['date']				= $facebook_date->getTimestamp();
			$tags['social_network']		= "Facebook";
			if (isset($facebook->shares))
			{
				$tags['shares_count']	= $facebook->shares->count;
			}
			if (isset($facebook->link))
			{
				$tags['post_url']		= $facebook->link;
			}

			$tags['likes_count']		= $facebook->likes->summary->total_count;
			$tags['comments_count']		= $facebook->comments->summary->total_count;

			//User data
			$tags['from']				= $facebook->from->name;
			//We don't have that in Facebook data...
			//$tags['profile_picture']	= $facebook->from->
			$tags['profile_picture']	= '';
			$tags['profile_url']		= 'https://www.facebook.com/'.$facebook->from->id;

			//Media attached
			if (isset($facebook->picture))
			{
				$tags['picture']		= $facebook->picture;
			}
		}
		elseif (array_key_exists('instagram', $post))
		{
			$tags['social_network'] = 'Instagram';
			$insta_post = $post['instagram'];
			$post_date = new DateTime();
			$post_date->setTimestamp($insta_post->created_time);
			$tags['date'] = $post_date->getTimestamp();

			$tags['post_url']		= $insta_post->link;
			$tags['screen_name']	= $insta_post->user->full_name;
			$tags['from']			= $insta_post->user->username;
			$tags['profile_picture']= $insta_post->user->profile_picture;
			$tags['profile_url']	= 'https://www.instagram.com/'.$insta_post->user->username;
			$tags['text']			= $insta_post->caption->text;
			$tags['text_url']		= $insta_post->caption->text;
			$tasg['comments_count']	= $insta_post->comments->count;
			$tags['likes_count']	= $insta_post->likes->count;
			$tags['picture']		= $insta_post->images->low_resolution->url;
			$tags['picture_hd']		= $insta_post->images->standard_resolution->url;
		}

		return $tags;
	}
}
