<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seo_meta_data {

	var $return_data;
	private $tag_prefix;
	private static $cache;
	
	var $settings = array();
        
    public function __construct() {
    	$this->EE = get_instance(); // Make a local reference to the ExpressionEngine super object
    	
    	$query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Seo_meta_data'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
    }
    
    function meta_tags() {
    	//parameters
		$entry_id = trim(ee()->TMPL->fetch_param('entry_id'));
		
		$robots = trim($this->EE->TMPL->fetch_param('robots')) ?: "index,follow";
		$page_title = ee()->TMPL->fetch_param('page_title');
		$section = ee()->TMPL->fetch_param('section');
		$summary_field = ee()->TMPL->fetch_param('summary_field');
		$canon_segments = ee()->TMPL->fetch_param('canon_segments');
		$image = ee()->TMPL->fetch_param('image');
		
		$ovr_title = ee()->TMPL->fetch_param('title');
		$ovr_description = ee()->TMPL->fetch_param('description');
		$ovr_keywords = ee()->TMPL->fetch_param('keywords');
		$ovr_summary = ee()->TMPL->fetch_param('summary');
		$ovr_canon = ee()->TMPL->fetch_param('canonical');
		
		$meta_only = ee()->TMPL->fetch_param('meta_only');
		
		$twitter_account = str_replace('@@', '@', '@'.$this->settings['seo_meta_twitter_handle']);
		$default_image = $this->settings['seo_meta_default_image'];
		
		if ($meta_only == "yes" OR $meta_only == "y") {
		    if (is_numeric($entry_id)) { //if entry_id
    		    //seo meta data settings
    		    $results = ee()->db->select('meta_title, meta_description, meta_keywords, meta_h1, meta_h2, meta_robots, meta_canon')->from('seo_meta_data_content')->where(array('entry_id' => $entry_id, 'site_id' => 1))->limit(1)->get();
    		    if ($results->num_rows() == 0) {
    		        $seo_title = '';
    		        $seo_desc = '';
    		        $seo_keywords = '';
        		    $meta_h1 = '';
        		    $meta_h2 = '';
        		    $meta_canon = '';
        		    $robots = 'index,follow';
    		    } else {
    		        $seo_title = $results->row('meta_title');
    		        $seo_desc = $results->row('meta_description');
    		        $seo_keywords = $results->row('meta_keywords');
    		        $meta_h1 = $results->row('meta_h1');
        		    $meta_h2 = $results->row('meta_h2');
        		    $meta_canon = $results->row('meta_canon');
        		    if ($results->row('meta_robots') != '') {
        		        $robots = $results->row('meta_robots');
        		    } else {
        		        $robots = "index,follow";
        		    }
    		    }
		    } else {
		        return ee()->TMPL->no_results();
		    }
		    
		    $tagdata = $this->EE->TMPL->tagdata;
		    if ($tagdata == '') {
		        $tagdata = $this->settings['seo_meta_template'];
		    }
        
    		// Build array of our variables
    		$data = array(
    			"meta_title"            => $this->_trim($seo_title, $this->settings['seo_meta_title_length'], ''),
    			"meta_description"      => $this->_trim($seo_desc, 320, ''),
    			"meta_keywords"         => $this->_trim($seo_keywords, 255, ''),
    			"meta_h1"               => $meta_h1,
    			"meta_h2"               => $meta_h2,
    			"meta_robots"           => $robots,
    			"meta_canon"            => $meta_canon,
    		);
		} else {
    		//get site_name
    		$query = ee()->db->select('site_label')->from('sites')->where(array('site_id' => 1))->limit(1)->get();
    		$site_name = $query->row('site_label');
    		
    		//site url
    		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $site_url = $protocol . $_SERVER['HTTP_HOST'] . '/';
    		
    		if (is_numeric($entry_id)) { //if entry_id
    		    //seo meta data settings
    		    $results = ee()->db->select('meta_title, meta_description, meta_keywords, meta_h1, meta_h2, meta_robots, meta_canon')->from('seo_meta_data_content')->where(array('entry_id' => $entry_id, 'site_id' => 1))->limit(1)->get();
    		    if ($results->num_rows() == 0) {
    		        $seo_title = '';
    		        $seo_desc = '';
    		        $seo_keywords = '';
        		    $meta_h1 = '';
        		    $meta_h2 = '';
        		    $meta_canon = '';
    		    } else {
    		        $seo_title = $results->row('meta_title');
    		        $seo_desc = $results->row('meta_description');
    		        $seo_keywords = $results->row('meta_keywords');
    		        $meta_h1 = $results->row('meta_h1');
        		    $meta_h2 = $results->row('meta_h2');  
        		    if ($results->row('meta_robots') != '') {
        		        $robots = $results->row('meta_robots');
        		    }
        		    $meta_canon = $results->row('meta_canon'); 
    		    }
    		    
    		    //channel data
    		    $query = ee()->db->select('title')->from('channel_titles')->where(array('entry_id' => $entry_id))->limit(1)->get();
    		    $q_title = $query->row('title');
    		    
    		    //summary field data
    		    $query_field = ee()->db->select('field_id')->from('channel_fields')->where(array('field_name' => $summary_field))->limit(1)->get();
    		    $field_id = $query_field->row('field_id');
    		    if (ee()->db->table_exists('channel_data_field_'.$field_id)) {
    		        $query_content = ee()->db->select('field_id_'.$field_id.' AS content')->from('channel_data_field_'.$field_id)->where(array('entry_id' => $entry_id))->limit(1)->get();
    		    } else {
    		        $query_content = ee()->db->select('field_id_'.$field_id.' AS content')->from('channel_data')->where(array('entry_id' => $entry_id))->limit(1)->get();
    		    }
    		    $summary_field_content = $query_content->row('content');
    		    
    		    //title
    		    if ($seo_title != '') {
    		        $title = $seo_title;
    		    } else if ($ovr_title != '') {
    		        $title = $ovr_title;
    		    } else if ($page_title != '') {
    		        $title = $page_title;
    		    } else {
    		        $title = $q_title;
    		    }
    		    
    		    //description
    		    if ($seo_desc != '') {
    		        $description = $seo_desc;
    		    } else if ($ovr_description != '') {
    		        $description = $ovr_description;
    		    } else if ($summary_field != '' AND $summary_field_content != '') {
    		        $description = $summary_field_content;
    		    } else {
    		        if ($page_title != '') {
    		            $description = $page_title.' | ';
    		        } else {
    		            $description = $title.' | ';
    		        }
    		        
    		        if ($section != '') {
    		            $description .= $section.' | ';
    		        }
    		        
    		        $description .= $site_name;
    		    }
    		    
    		    //keywords
    		    if ($seo_keywords != '') {
    		        $keywords = $seo_keywords;
    		    } else if ($ovr_keywords != '') {
    		        $keywords = $ovr_keywords;
    		    } else {
    		        if ($page_title != '') {
    		            $keywords = $page_title.', ';
    		        } else {
    		            $keywords = $title.', ';
    		        }
    		        
    		        if ($section != '') {
    		            $keywords .= $section.', ';
    		        }
    		        
    		        $keywords .= $site_name;
    		    }
    		    
    		    //summary
    		    if ($ovr_summary != '') {
    		        $summary = $ovr_summary;
    		    } else if ($summary_field != '' AND $summary_field_content != '') {
    		        $summary = $summary_field_content;
    		    } else {
    		        $summary = $description;
    		    }
    		} else { //no entry_id
    		    $meta_h1 = '';
    		    $meta_h2 = '';
    		    $meta_canon = '';
    		    
    		    //title
    		    if ($ovr_title != '') {
    		        $title = $ovr_title;
    		    } else if ($page_title != '') {
    		        $title = $page_title;
    		    } else {
    		        $title = '';
    		    }
    		    
    		    //description
    		    $description = '';
    		    if ($ovr_description != '') {
    		        $description = $ovr_description;
    		    } else {
    		        if ($page_title != '') {
    		            $description = $page_title.' | ';
    		        } else if ($title != '') {
    		            $description = $title.' | ';
    		        }
    		        
    		        if ($section != '') {
    		            $description .= $section.' | ';
    		        }
    		        
    		        $description .= $site_name;
    		    }
    		    
    		    //keywords
    		    $keywords = '';
    		    if ($ovr_keywords != '') {
    		        $keywords = $ovr_keywords;
    		    } else {
    		        if ($page_title != '') {
    		            $keywords = $page_title.', ';
    		        } else if ($title != '') {
    		            $keywords = $title.', ';
    		        }
    		        
    		        if ($section != '') {
    		            $keywords .= $section.', ';
    		        }
    		        
    		        $keywords .= $site_name;
    		    }
    		    
    		    //summary
    		    if ($ovr_summary != '') {
    		        $summary = $ovr_summary;
    		    } else {
    		        $summary = $description;
    		    }
    		}
    		
    		//image
    		if ($image == '') {
    		    $image = $default_image;
    		}
    		
    		if (strpos($image, 'http') === false) { 
    		    $image_start = $site_url;
    		} else { 
    		    $image_start = '';
    		}
    		
    		$image = $image_start.ltrim($image, '/');
    		
    		//canonical
    		$canon = $site_url;
    		if ($meta_canon != '') {
    		    $canon .= ltrim($meta_canon, "/");
    		} else if ($ovr_canon != '') {
    		    $pos = strpos($ovr_canon, 'http');
    		    if ($pos === false) {
                    $canon .= ltrim($ovr_canon, "/");
                } else {
                    $canon = ltrim($ovr_canon, "/");
                }
    		} else if (is_numeric($canon_segments)) {
    		    $canon .= $this->_segments($canon_segments);
    		} else {
    		    $canon .= $this->_segments(12);
    		}

    		$tagdata = $this->EE->TMPL->tagdata;
    		if ($tagdata == '') {
		        $tagdata = $this->settings['seo_meta_template'];
		    }
    
    		// Build array of our variables
    		$data = array(
    			"meta_title"            => $this->_trim($title, $this->settings['seo_meta_title_length'], ''),
    			"meta_description"      => $this->_trim($description, 320, ''),
    			"meta_keywords"         => $this->_trim($keywords, 255, ''),
    			"meta_h1"               => $meta_h1,
    			"meta_h2"               => $meta_h2,
    			"meta_robots"           => $robots,
    			"meta_twitter_account"  => $twitter_account,
    			"meta_summary"          => $this->_trim($summary, 200, ''),
    			"meta_image"            => $image,
    			"meta_canon"            => $canon
    		);
		}
		
		//Development mode override
		if (isset($this->settings['seo_meta_data_dev_mode']) AND$this->settings['seo_meta_data_dev_mode'] == "y") {
		    $data['meta_robots'] = 'noindex,nofollow';
		}
		
		// Construct $variables array for use in parse_variables method
		$variables = array();
		$variables[] = $data;
		
		return $this->EE->TMPL->parse_variables($tagdata, $variables);
    }
    
    public function tag_manager() {
        $member_id = ee()->session->userdata('member_id');
        $email = '';
        $name = '';
        if ($member_id == 0) { $member_id = ''; }
        if (is_numeric($member_id)) {
            $email = ee()->session->userdata('email');
            $name = ee()->session->userdata('screen_name');
        }
        $head = <<<EOD
<script>
  dataLayer = [{
    'userId': '$member_id',
    'email': '$email',
    'screenName': "$name"
  }];
</script>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{gtm_id}');</script>
<!-- End Google Tag Manager -->
EOD;

        $body = <<<EOD
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={gtm_id}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
EOD;

        
        $tag = trim($this->EE->TMPL->fetch_param('tag')) ?: "head";
        
        if (isset($this->settings['seo_meta_data_tag_manager_id']) AND $this->settings['seo_meta_data_tag_manager_id'] != '' AND isset($this->settings['seo_meta_data_dev_mode']) AND $this->settings['seo_meta_data_dev_mode'] == 'n') {
            $gtm_id = "GTM-".str_replace("GTM-", "", $this->settings['seo_meta_data_tag_manager_id']);
            
            if ($tag == "head" OR $tag == "header") {
                return str_replace("{gtm_id}", $gtm_id, $head);
            } else if ($tag == "body") {
                return str_replace("{gtm_id}", $gtm_id, $body);
            }
        }
        
        return;
    }
    
    public function robot_redirector() {
        if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])) { 
            $changes_made = 0;
            $segment_cnt = 0;
            $redirect = "";
            
            $segments = ee()->uri->segment_array();

            foreach($segments AS $segment) {
                $mod = str_replace("_", "-", $segment);
                $redirect .= "/".$mod;
                if ($mod != $segment) {
                    $changes_made = 1;
                }
                $segment_cnt++;
            }
            
            if ($changes_made == 0) {
                $redirect = '';
                
                if (ee()->uri->segment(1) != '' AND $segment_cnt > 1) {
                    $redirect = "/".ee()->uri->segment(1);
                } 
            }
            
            ee()->functions->redirect($redirect, 301);
        }
    }
    
    public function social_links() {
        $tagdata = $this->EE->TMPL->tagdata;
        $data = array();
        $i = 0;
        
        foreach($this->settings['seo_meta_social_links']['rows'] AS $row) {
            $i++;
            $url = '<a href="'.$row['seo_meta_social_url'].'" target="_blank"';
            if ($row['seo_meta_social_title'] == "Quora") {
                $url .= ' rel="nofollow"';
            }
            $url .=' title="'.$row['seo_meta_social_title'].'">'.$row['seo_meta_social_icon'].'</a>';
            
            $data[] = array(
                'social_title'      => $row['seo_meta_social_title'],
                'social_url'        => $row['seo_meta_social_url'],
                'social_icon'       => $row['seo_meta_social_icon'],
                'social_link'   => $url
            );
        }
        
        if ($i > 0) {
            $variables = array();
    		$variables[] = $data;
    		
    		return $this->EE->TMPL->parse_variables( $tagdata, $data );
        } else {
            return ee()->TMPL->no_results();
        }
    }
    
    private function _segments($limit) {
    	if (is_numeric($limit)) { $limit = ceil($limit); }
    	if ($limit != '' and is_numeric($limit) and $limit > 0 and $limit < 12) {
    	  for ($i=1;$i<=$limit;$i++) { 
    		  if (ee()->uri->segment($i) != '') { 
    			  if ($i == 1) { 
    				  $output = ee()->uri->segment($i); 
    			  } else { 
    				  $output .= ee()->uri->slash_segment($i, 'leading'); 
    			  } 
    		  } 
    	  }
    	  return $output;
    	} else {
    		return ltrim(ee()->uri->uri_string(), '/');
    	}
    }
    
    private function _trim($string, $length, $append) {
        if (is_array($string)) { $string = ''; }
        $output = htmlspecialchars_decode(strip_tags($string));
    	$output = str_replace("&gt;", '', $output);
    	$output = str_replace("&lt;", '', $output);
    	$output = str_replace("&quot;", '', $output);
    	$output = str_replace("&#8216;", "'", $output);
	    $output = str_replace("&#8217;", "'", $output);
    	$output = str_replace("&amp;#8216;", "'", $output);
    	$output = str_replace("&amp;#8217;", "'", $output);
    	$output = str_replace(PHP_EOL, '', $output);
    	$output = str_replace("  ", " ", $output);
    	$output = substr($output, 0, $length);
    	if ($append != '') {
    	    $output = $output." ".$append;
    	}
    	$output = trim($output);
    	
    	return $output;
    }
}

?>