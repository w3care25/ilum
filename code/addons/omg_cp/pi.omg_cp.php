<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

class Omg_cp {
	public $return_data = "";
	
	var $settings = array();

	public function __construct() {
		$this->EE = get_instance();
	}
	
	public function admin_edit_btn() {
	    $entry_id = trim($this->EE->TMPL->fetch_param('entry_id')) ?: '';
	    
	    if (is_numeric($entry_id)) {
	        $tagdata = $this->EE->TMPL->tagdata;
	        $data = array();
	        
	        $group_id = ee()->session->userdata('group_id');
	        
	        $query = ee()->db->select('settings')->from('extensions')->where(array('class' => 'Omg_cp_ext'))->limit(1)->get();
	        $this->settings = unserialize($query->row('settings'));
	        
	        if ($tagdata == '') {
	            $tagdata = $this->settings['admin_edit_btn_template'];
	        }
	        
	        $allow = 0;
	        if ($group_id == 1) { 
	            $allow = 1;
	        } else {
	            foreach($this->settings['admin_edit_btn'] AS $group) {
	                if ($group_id == $group) { $allow = 1; break; }
	            }
	        }
	        
	        
	        if ($allow == 1) {
    	        if ($group_id == 1) {
    	            $query = ee()->db->select('title')->from('channel_titles')->where(array('entry_id' => $entry_id))->limit(1)->get();
    	        } else {
    	            $query = ee()->db->select('t.title AS title')->from('channel_titles t')->join('channel_member_groups g', 'g.channel_id = t.channel_id')->where(array('t.entry_id' => $entry_id, 'g.group_id' => $group_id))->limit(1)->get();
    	        }
    	        if ($query->num_rows() > 0) {
    	            $data[0]['admin_edit_title'] = $query->row('title');
    	            $data[0]['admin_edit_url'] = '/'.SYSDIR.'/?/cp/publish/edit/entry/'.$entry_id;
            		
            		return $this->EE->TMPL->parse_variables( $tagdata, $data );
    	        }
	        }
	    }
	    
	    return ee()->TMPL->no_results();
	}
	
	public function ajax_table_history() {
	    // DB table to use
        $table = 'exp_store_orders';
        
        // Table's primary key
        $primaryKey = 'id';
        
        // Other variables
        $member_id = ee()->session->userdata('member_id');

        $columns = array(
        	array('db' => 'order_id', 'dt' => 0, //Order ID
            	'formatter' => function($d, $row) {
                    return '<a href="{account}/order/'.$row['order_hash'].'">'.$d.'</a>';
                }), 
        	array('db' => 'order_completed_date', 'dt' => 1, //Date
        		'formatter' => function($d, $row) {
        			return ee()->localize->human_time($d);
        		}),
        	array('db' => 'order_total', 'dt' => 2, //Total
        		'formatter' => function($d, $row) {
        			return '$'.number_format($d, 2);
        		}),
            array('db' => 'order_hash', 'dt' => '')
        );
        
        require(SYSPATH."/user/config/config.php");
        
        $sql_details = array(
        	'user' => $config['database']['expressionengine']['username'],
        	'pass' => $config['database']['expressionengine']['password'],
        	'db'   => $config['database']['expressionengine']['database'],
        	'host' => $config['database']['expressionengine']['hostname']
        );
        
        $where = "`member_id` = '$member_id' AND `order_paid_date` IS NOT NULL";
        
        require(PATH_THIRD."/omg_cp/datatables/class/ssp.class.php");
        
        return json_encode(
        	SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, '', $where)
        );
	}
    
    public function alert() {
        $data = array();
        $query = ee()->db->select('settings')->from('extensions')->where(array('class' => 'Omg_cp_ext'))->limit(1)->get();
	    $settings = unserialize($query->row('settings'));
        
	    $data[0]['alert_type'] = trim($this->EE->TMPL->fetch_param('type')) ?: 'default';
	    $data[0]['alert_dismiss'] = trim($this->EE->TMPL->fetch_param('dismiss')) ?: '';
	    if ($data[0]['alert_dismiss'] != 'yes' AND $data[0]['alert_dismiss'] != 'y') {
	        $data[0]['alert_dismiss'] = '';
	    }
	    $data[0]['alert_class'] = trim($this->EE->TMPL->fetch_param('class')) ?: '';
	    $data[0]['alert_name'] = trim($this->EE->TMPL->fetch_param('name')) ?: '';
	    $data[0]['alert_id'] = trim($this->EE->TMPL->fetch_param('id')) ?: '';
	    
	    $data[0]['alert_message'] = $this->EE->TMPL->tagdata;

	    $tagdata = $settings['alert_template'];
	    
	    $variables = array();
		$variables[] = $data;
		
		return $this->EE->TMPL->parse_variables( $tagdata, $data );
	}
	
	public function birthday_check() {
	    $birthday = trim($this->EE->TMPL->fetch_param('birthday'));
        
        if (is_numeric($birthday)) {
            if (ee()->localize->format_date('%j-%n', $birthday) == ee()->localize->format_date('%j-%n')) {
                return ee()->localize->format_date('%Y') - ee()->localize->format_date('%Y', $birthday);
    	    } else {
    	        return '';
    	    }
        } else {
            return '';
        }
	}
	
	public function can_access_cp() {
	    $redirect = trim($this->EE->TMPL->fetch_param('redirect')) ?: '/';
	    
	    if (ee()->session->userdata('can_access_cp') == 'n' && ee()->session->userdata('group_id') != 3) { 
	        ee()->functions->redirect($redirect);
	    } else {
	        return;
	    }
	}
    
    public function change_status() {
	    $entry_id = trim($this->EE->TMPL->fetch_param('entry_id')) ?: '';
	    $status_label = trim($this->EE->TMPL->fetch_param('status')) ?: '';
	    $return = trim($this->EE->TMPL->fetch_param('return')) ?: '';
	    
        if (is_numeric($entry_id)) {
            $query = ee()->db->select('status_id')->from('statuses')->where(array('status' => $status_label))->limit(1)->get();
            if ($query->num_rows() > 0) {
		        $status_id = $query->row('status_id');
			    
			    ee()->db->update('channel_titles', array('status' => $status_label, 'status_id' => $status_id), array('entry_id'=> $entry_id));
			    
			    if ($return != '') {
			        ee()->functions->redirect($return);
			    }
            }
        }
        
        return;
    }
	
	public function clean_email() {
	    $email = trim($this->EE->TMPL->fetch_param('email'));
	    $email = str_replace('@', '[at]', $email);
	    return $email;
	}
    
    public function detect_mobile() {
	    $test = trim($this->EE->TMPL->fetch_param('test')) ?: '';
        $detect = new Mobile_Detect;
        $result = '';
	    
	    switch ($test) {
            case 'mobile':
                if ( $detect->isMobile() ) {
                    $result = 1;
                }
                break;
            case 'tablet':
                if ( $detect->isTablet() ) {
                    $result = 1;
                }
                break;
            case 'phone':
                if ( $detect->isMobile() && !$detect->isTablet() ) {
                    $result = 1;
                }
                break;
        }
        
        return $result;
	}
	
	public function file_icon() {
	    //parameters
	    $mime = trim($this->EE->TMPL->fetch_param('mime'));
        $extension = trim($this->EE->TMPL->fetch_param('extension'));
        $class = trim($this->EE->TMPL->fetch_param('class')) ?: '';
        $fa_style = trim($this->EE->TMPL->fetch_param('fa_style')) ?: 'fas';
        
        if ($extension == "docx" or $extension == "docm" or $extension == "dotx" or $extension == "dotm") {
        	$mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        } else if ($extension == "xlsx" or $extension == "xlsm" or $extension == "xltx" or $extension == "xltm" or $extension == "xlsb" or $extension == "xlam") {
        	$mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else if ($extension == "pptx" or $extension == "pptm" or $extension == "ppsx" or $extension == "ppsm" or $extension == "potx" or $extension == "potm" or $extension == "ppam" or $extension == "sldx" or $extension == "sldm") {
            $mime = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        }
        
        $mime = explode('/', $mime);
        $mime_type = $mime[0];
        $mime_detail = $mime[1];
        
        if ($mime_type == "application") {
        	switch($mime_detail) {
        		case "csv":
        		case "vnd.ms-excel":
        		case "vnd.ms-excel.addin.macroEnabled.12":
        		case "vnd.ms-excel.sheet.binary.macroEnabled.12":
        		case "vnd.ms-excel.sheet.macroEnabled.12":
        		case "vnd.ms-excel.template.macroEnabled.12":
        		case "vnd.msexcel":
        		case "vnd.openxmlformats-officedocument.spreadsheetml":
        		case "vnd.openxmlformats-officedocument.spreadsheetml.sheet":
        		case "vnd.openxmlformats-officedocument.spreadsheetml.template":
        		case "excel":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-excel"></i>';
        			break;
        		case "msword":
        		case "vnd.ms-word.document.macroEnabled.12":
        		case "ms-word.template.macroEnabled.12":
        		case "vnd.openxmlformats-officedocument.wordprocessingml":
        		case "vnd.openxmlformats-officedocument.wordprocessingml.document":
        		case "vnd.openxmlformats-officedocument.wordprocessingml.template":
        		case "vnd.ms-word.template.macroEnabled.12":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-word"></i>';
        			break;
        		case "pdf":
        		case "x-download":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-pdf"></i>';
        			break;
        		case "postscript":
        		case "x-photoshop":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-image"></i>';
        			break;
        		case "epub+zip":
        		case "x-gtar":
        		case "x-gzip":
        		case "x-rar-compressed":
        		case "x-stuffit":
        		case "x-tar":
        		case "x-zip":
        		case "x-zip-compressed":
        		case "zip":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-archive"></i>';
        			break;
        		case "powerpoint":
        		case "vnd.ms-powerpoint":
        		case "vnd.ms-powerpoint.addin.macroEnabled.12":
        		case "vnd.ms-powerpoint.presentation.macroEnabled.12":
        		case "vnd.ms-powerpoint.slideshow.macroEnabled.12":
        		case "vnd.ms-powerpoint.template.macroEnabled.12":
        		case "vnd.openxmlformats-officedocument.presentationml.presentation":
        		case "vnd.openxmlformats-officedocument.presentationml.slideshow":
        		case "vnd.openxmlformats-officedocument.presentationml.template":
        		case "vnd.ms-powerpoint.slide.macroEnabled.12":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-powerpoint"></i>';
        			break;
        		case "vnd.oasis.opendocument.text":
        		case "vnd.oasis.opendocument.text-master":
        		case "vnd.oasis.opendocument.text-template":
        		case "vnd.oasis.opendocument.text-web":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-alt"></i>';
        			break;
        		case "xhtml+xml":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-code"></i>';
        			break;
        		default:
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file"></i>';
        			break;
        	}
        } else if ($mime_type == "audio") {
        	$output = '<i class="'.$fa_style.' '.$class.' fa-file-audio"></i>';	
        } else if ($mime_type == "image") {
        	$output = '<i class="'.$fa_style.' '.$class.' fa-file-image"></i>';
        } else if ($mime_type == "text") {
        	switch($mime_detail) {
        		case "comma-separated-values":
        		case "css":
        		case "csv":
        		case "html":
        		case "x-comma-separated-values":
        		case "xml":
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-code"></i>';
        			break;
        		default:
        			$output = '<i class="'.$fa_style.' '.$class.' fa-file-alt"></i>';
        			break;
        	}
        } else if ($mime_type == "video") {
        	$output = '<i class="'.$fa_style.' '.$class.' fa-file-video"></i>';
        } else {
        	$output = '<i class="'.$fa_style.' '.$class.' fa-file"></i>';	
        }
        
        return $output;
	}
	
	public function field_check() {
	    $result = 1;

        //Username
        if (isset($_GET['username'])) {
        	$username = ee()->input->get('username');
        	$query = ee()->db->select('member_id')->from('members')->where(array('username' => $username))->get();
        	if ($query->num_rows() > 0) {
        		$result = 0;
        	}
        }
        
        //Screen Name
        if (isset($_GET['screen_name'])) {
        	$screen_name = ee()->input->get('screen_name');
        	$query = ee()->db->select('member_id')->from('members')->where(array('screen_name' => $screen_name))->get();
        	if ($query->num_rows() > 0) {
        		$result = 0;
        	}
        }
    
        //Email
        if (isset($_GET['email'])) {
        	$email = ee()->input->get('email');
        	$query = ee()->db->select('member_id')->from('members')->where(array('email' => $email))->get();
        	if ($query->num_rows() > 0) {
        		$result = 0;
        	}
        }
        
        //Send Response
        if ($result == 1) {
        	ee('Response')->setStatus('200');
        } else {
        	ee('Response')->setStatus('406');
        }
	}
	
	public function forum_subscriptions() {	
		//parameters
		$limit = trim($this->EE->TMPL->fetch_param('limit')) ?: 10;
		
		$query = ee()->db->select('t.topic_id AS topic_id, t.title AS title, t.topic_date AS topic_date, t.thread_total AS post_total, t.author_id AS author_id')->from('forum_topics t')->join('forum_subscriptions s', 's.topic_id = t.topic_id')->where(array('s.member_id' => ee()->session->userdata('member_id')))->limit($limit)->order_by('s.subscription_date', 'desc')->get();
		
		if ($query->num_rows() > 0) {
		    $tagdata = $this->EE->TMPL->tagdata;
		    $data = array();
		    
		    foreach($query->result_array() as $row) {
		        $data[] = array(
                    "topic_id"          => $row('topic_id'), 
                    "topic_title"       => $row('title'),
                    "topic_date"        => $row('topic_date'),
                    "post_total"        => $row('post_total'),
                    "post_author_id"    => $row('author_id')
                );
		    }
		    
		    // Construct $variables array for use in parse_variables method
    		$variables = array();
    		$variables[] = $data;
    		
    		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata, $variables );
		} else {
		    return ee()->TMPL->no_results();
		}
	}
    
    public function query_string() {
	    if ($_SERVER['QUERY_STRING'] != '') {
	        return '?'.$_SERVER['QUERY_STRING'];
	    } else {
	        return;
	    }
	}
	
	public function rel_url() {
	    $entry_id = trim($this->EE->TMPL->fetch_param('entry_id')) ?: '';
	    
	    if (is_numeric($entry_id)) {
	        $query = ee()->db->select('t.url_title AS url_title, c.channel_url AS channel_url, c.search_results_url AS search_results_url, c.max_entries AS max_entries')->from('channel_titles t')->join('channels c', 't.channel_id = c.channel_id')->where(array('t.entry_id' => $entry_id))->limit(1)->get();
	        
	        if ($query->num_rows() > 0) {
	            foreach ($query->result_array() AS $row) {
	                if ($row['max_entries'] == 1) {
	                    $url = str_replace('//', '/', '/'.$row['channel_url']);
	                } else {
    	                $query_cat = ee()->db->select('cat_id')->from('category_posts')->where(array('entry_id' => $entry_id, 'cat_id' => 1))->limit(1)->get();
    	                if ($query_cat->num_rows() > 0) {
    	                    $url = str_replace('//', '/', '/'.$row['channel_url']);
    	                } else {
    	                    $url = str_replace('//', '/', '/'.$row['search_results_url'].'/'.$row['url_title']);
    	                }
	                }
	                
	                return str_replace('//', '/', $url);
	            }    
	        }
	    }
	    
	    return;
	}
	
	public function simple_math() {
	    $var1 = trim($this->EE->TMPL->fetch_param('var1'));
	    $var2 = trim($this->EE->TMPL->fetch_param('var2'));
	    $operation = trim($this->EE->TMPL->fetch_param('operation'));
	    
	    if (is_numeric($var1) AND is_numeric($var2)) {
    	    switch($operation) {
    	        case '+': return $var1 + $var2;
    	        case '-': return $var1 - $var2;
    	        case '*': return $var1 * $var2;
    	        case '/': return $var1 / $var2;
    	        default: return;
    	    }
	    } else {
	        return;
	    }
	}
    
    public function single_val() {
	    $entry_id = trim($this->EE->TMPL->fetch_param('entry_id'));
	    $field_id = trim($this->EE->TMPL->fetch_param('field_id'));
	    
	    $query = ee()->db->select('field_id_'.$field_id.' AS output')->from('channel_data')->where(array('entry_id' => $entry_id))->limit(1)->get();
	    if ($query->num_rows() > 0) {
	        return $query->row('output');
	    } else {
	        return;
	    }
	}
	
	public function time_format() {
	    $time = trim($this->EE->TMPL->fetch_param('time')) ?: ee()->localize->now();
        $format = trim($this->EE->TMPL->fetch_param('format')) ?: '';
        
        if ($format != '') {
        	$time_output = ee()->localize->format_date($format, $time);
        } else {
        	$time_output = ee()->localize->human_time($time);
        }
        
        return $time_output;
	}
}
?>