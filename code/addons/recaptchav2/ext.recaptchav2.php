<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* ExpressionEngine reCAPTCHA v2
*
* Replaces the built-in CAPTCHA for ExpressionEngine
*
* @package		ExpressionEngine reCAPTCHA
* @author		Denik
* @link			http://eecoding.com/
* @version		1.0.4
* @license
*/

class Recaptchav2_ext
{
	public $name			= 'reCAPTCHA v2';
	public $version			= '1.0.4';
	public $description		= "Replaces the built-in CAPTCHA";
	public $settings_exist	= 'y';
	public $docs_url		= 'http://eecoding.com/docs/recaptchav2';
	public $settings		= array();
	private $hooks			= array(
								'create_captcha_start'         => 'create_captcha_start',
								'insert_comment_start'         => 'validate_captcha',
								'member_member_register_start' => 'validate_captcha',
								'user_register_start'          => 'validate_captcha',
								'freeform_module_validate_end' => 'validate_captcha',
								'template_post_parse'          => 'final_template',
								'core_template_route'          => 'core_template_route',
								'member_manager'               => 'member_manager',
								'sessions_start'               => 'sessions_start'
							);

	private $_error_msg;
	private $_languages = array(
		''       => 'Auto detect by browser',

		'ar'     => 'Arabic',
		'af'     => 'Afrikaans',
		'am'     => 'Amharic',
		'hy'     => 'Armenian',
		'az'     => 'Azerbaijani',
		'eu'     => 'Basque',
		'bn'     => 'Bengali',
		'bg'     => 'Bulgarian',
		'ca'     => 'Catalan',
		'zh-HK'  => 'Chinese (Hong Kong)',
		'zh-CN'  => 'Chinese (Simplified)',
		'zh-TW'  => 'Chinese (Traditional)',
		'hr'     => 'Croatian',
		'cs'     => 'Czech',
		'da'     => 'Danish',
		'nl'     => 'Dutch',
		'en-GB'  => 'English (UK)',
		'en'     => 'English (US)',
		'et'     => 'Estonian',
		'fil'    => 'Filipino',
		'fi'     => 'Finnish',
		'fr'     => 'French',
		'fr-CA'  => 'French (Canadian)',
		'gl'     => 'Galician',
		'ka'     => 'Georgian',
		'de'     => 'German',
		'de-AT'  => 'German (Austria)',
		'de-CH'  => 'German (Switzerland)',
		'el'     => 'Greek',
		'gu'     => 'Gujarati',
		'iw'     => 'Hebrew',
		'hi'     => 'Hindi',
		'hu'     => 'Hungarain',
		'is'     => 'Icelandic',
		'id'     => 'Indonesian',
		'it'     => 'Italian',
		'ja'     => 'Japanese',
		'kn'     => 'Kannada',
		'ko'     => 'Korean',
		'lo'     => 'Laothian',
		'lv'     => 'Latvian',
		'lt'     => 'Lithuanian',
		'ms'     => 'Malay',
		'ml'     => 'Malayalam',
		'mr'     => 'Marathi',
		'mn'     => 'Mongolian',
		'no'     => 'Norwegian',
		'fa'     => 'Persian',
		'pl'     => 'Polish',
		'pt'     => 'Portuguese',
		'pt-BR'  => 'Portuguese (Brazil)',
		'pt-PT'  => 'Portuguese (Portugal)',
		'ro'     => 'Romanian',
		'ru'     => 'Russian',
		'sr'     => 'Serbian',
		'si'     => 'Sinhalese',
		'sk'     => 'Slovak',
		'sl'     => 'Slovenian',
		'es'     => 'Spanish',
		'es-419' => 'Spanish (Latin America)',
		'sw'     => 'Swahili',
		'sv'     => 'Swedish',
		'ta'     => 'Tamil',
		'te'     => 'Telugu',
		'th'     => 'Thai',
		'tr'     => 'Turkish',
		'uk'     => 'Ukrainian',
		'ur'     => 'Urdu',
		'vi'     => 'Vietnamese',
		'zu'     => 'Zulu'
	);

	/**
	 * Class constructor
	 * @param string $settings current settings
	 */
	function __construct($settings='')
	{
		$this->settings = $settings;
	}

	/**
	 * Create captcha hook
	 * @return string
	 */
	public function create_captcha_start()
	{
		// Check settings
		if( ! $this->_validate_settings() )
		{
			ee()->extensions->end_script = TRUE;

			return $this->_error_msg;
		}

		// Create our 'fake' entry in the captcha table
		$data = array(
			'date' 			=> time(),
			'ip_address'	=> ee()->input->ip_address(),
			'word'			=> 'reCAPTCHA v2'
		);

		ee()->db->insert('captcha', $data);

		// Default recaptcha loader
		$output = "<div class=\"g-recaptcha\"></div>";

		ee()->extensions->end_script = TRUE;

		return $output;
	}

	/**
	 * Validate CAPTCHA
	 * @return type
	 */
	public function validate_captcha()
	{
		// Bail out if settings are empty or wrong
		if ( ! $this->_validate_settings())
		{
			ee()->extensions->end_script = TRUE;
			return false;
		}

		// Load library
		ee()->load->library('recaptchav2', array(
			'site_key' => $this->settings['site_key'],
			'secret'   => $this->settings['secret']
		));

		// Check answer
		$response = ee()->recaptchav2->validate( ee()->input->post('g-recaptcha-response') );
		
		// Clear captcha word
		if( isset($_POST['g-recaptcha-response']) ) unset( $_POST['g-recaptcha-response'] );

		if ($response === TRUE)
		{
			// Give EE what it's looking for
			$_POST['captcha'] = 'reCAPTCHA v2';

			return true;
		}

		// Ensure EE knows the captcha was invalid
		$_POST['captcha'] = '';

		// Whether the user's response was empty or just wrong, all we can do is make EE
		// think the captcha is missing, so we'll use more generic language for an error
		ee()->lang->loadfile('recaptchav2');
		ee()->lang->language['captcha_required'] = lang('recaptcha_error');
		$this->_error_msg = lang('recaptcha_error');

		if ($this->settings['debug'] == 'y')
		{
			ee()->lang->language['captcha_required'] .= " (".lang(ee()->recaptchav2->error_code).")";
			$this->_error_msg .= " (".lang(ee()->recaptchav2->error_code).")";
		}

		return false;
	}

	/**
	 * Validation some other forms (which do unsupported hooks)
	 * @param  object $SESS Session object
	 * @return void
	 */
	public function sessions_start($SESS)
	{
		// ExpressionEngine Contact form
		// mod.email.php / send_mail hook
		if( array_key_exists('ACT', $_POST)
			&& array_key_exists('recipients', $_POST)
			&& array_key_exists('user_recipients', $_POST)
			&& array_key_exists('charset', $_POST)
			&& array_key_exists('replyto', $_POST) )
		{
			$action_id = ee()->input->post('ACT');

			if( is_numeric($action_id) )
			{
				// Fetch action method
				ee()->db->select('class, method, csrf_exempt');
				ee()->db->where('action_id', $action_id);
				$query = ee()->db->get('actions');
				
				$class  = ucfirst($query->row('class'));
				$method = strtolower($query->row('method'));
				$csrf_exempt = (bool) $query->row('csrf_exempt');

				// email/send_mail
				if( $class == 'Email' && $method == 'send_email' )
				{
					// Load the "core" language file - must happen after the session is loaded
					ee()->lang->loadfile('core');

					// Validate
					if( ! $this->validate_captcha() )
					{
						echo ee()->output->show_user_error('general', array(lang('captcha_required')));
						ee()->extensions->end_script = TRUE;
					}

					return;
				}
			}
		}
	}

	/**
	 * Call getScript() in HTML variant
	 * @return string - HTML code
	 */
	public function getScriptHtml()
	{
		return "<script type=\"text/javascript\">\n" . $this->getScript() . "\n</script>";
	}

	/**
	 * JavaScript for recaptcha
	 * @return string - JavaScript code
	 */
	public function getScript()
	{
		// Language
		$hl = isset($this->settings['lang'])&&$this->settings['lang'] ? "&hl={$this->settings['lang']}" : "";

/* ORIGIAL VARIANT
var reCAPTCHAv2_init = function(){
	document.reCAPTCHAv2();
	if( window.jQuery ) window.jQuery(document).trigger('reCAPTCHAv2_init');
};
(function() {
	if (window['___reCAPTCHAv2_init']) {
		return;
	};
	window['___reCAPTCHAv2_init'] = true;

	document.reCAPTCHAv2 = function(object){
		if( typeof grecaptcha === 'undefined' ) return;
		if( object == undefined ) object = "g-recaptcha";
		if( typeof object == 'string' ) object = window.jQuery ? jQuery("."+object) : document.getElementsByClassName(object);
		if( object.length == undefined ) object = [object];
		for( var i = 0; i<object.length; i++ )
		{
			grecaptcha.render(object[i], {
				'sitekey' : "{$this->settings['site_key']}"
			});
		}
	};

	// document.reCAPTCHAv2_init = function(){
	// 	document.reCAPTCHAv2();
	// 	if( window.jQuery ) window.jQuery(document).trigger('reCAPTCHAv2_init');
	// };

	var po = document.createElement('script');
	po.type = 'text/javascript';
	po.async = true;
	po.src = 'https://www.google.com/recaptcha/api.js?onload=reCAPTCHAv2_init&render=explicit{$hl}';
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(po, s);
})();
<script src="https://www.google.com/recaptcha/api.js?onload=reCAPTCHAv2_init&render=explicit{$hl}" async defer></script>
 */

		// Minify variant
		return <<<JAVASCRIPT
var reCAPTCHAv2_init=function(){document.reCAPTCHAv2(),window.jQuery&&window.jQuery(document).trigger("reCAPTCHAv2_init")};!function(){if(!window.___reCAPTCHAv2_init){window.___reCAPTCHAv2_init=!0,document.reCAPTCHAv2=function(a){if("undefined"!=typeof grecaptcha){void 0==a&&(a="g-recaptcha"),"string"==typeof a&&(a=window.jQuery?jQuery("."+a):document.getElementsByClassName(a)),void 0==a.length&&(a=[a]);for(var b=0;b<a.length;b++)grecaptcha.render(a[b],{sitekey:"{$this->settings['site_key']}"})}};var a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src="https://www.google.com/recaptcha/api.js?onload=reCAPTCHAv2_init&render=explicit{$hl}";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)}}();
JAVASCRIPT;
	}

	/**
	 * Include javascript to head
	 * @param  string  $final_template The template string after template tags have been parsed
	 * @param  boolean $is_partial     TRUE if the current template is an embed or a layout
	 * @param  string  $site_id        Site ID of the current template
	 * @return string                  Final template
	 */
	public function final_template($final_template, $is_partial, $site_id)
	{
		// Multiple Extensions, Same Hook
		if( ee()->extensions->last_call !== FALSE ) $final_template = ee()->extensions->last_call;

		// && ! ee()->session->cache(__CLASS__, 'google_api_inc')

		// If this is not EMBED and not AJAX-request
		if( ! $is_partial && ! AJAX_REQUEST )
		{
			$inc_type = isset( $this->settings['inc_type'] ) ? $this->settings['inc_type'] : 'head';
			$pos = false;

			// Insert before </head> ?
			if( $inc_type == 'head' )
			{
				$pos = stripos($final_template, "</$inc_type>");
			}

			// Insert before </body> ?
			if( $inc_type == 'body' )
			{
				$pos = strripos($final_template, "</$inc_type>");
			}

			// insert somewhere ? )
			if( $pos !== FALSE )
			{
				$final_template = substr_replace($final_template, $this->getScriptHtml(), $pos, 0);
				/*ee()->session->set_cache(__CLASS__, 'google_api_inc', TRUE);*/
			}
		}

		return $final_template;
	}

	/**
	 * Add reCAPTCHAv2 global variables
	 * @param  string $uri_string string
	 * @return mixed
	 */
	public function core_template_route($uri_string)
	{
		// Multiple Extensions, Same Hook
		if( ee()->extensions->last_call !== FALSE ) $uri_string = ee()->extensions->last_call;

		// reCAPTCHAv2 variables
		ee()->config->_global_vars['recaptcha:sitekey']     = $this->getSetting('site_key');
		ee()->config->_global_vars['recaptcha:lang']        = $this->getSetting('lang');
		ee()->config->_global_vars['recaptcha:inc_type']    = $this->getSetting('inc_type', 'head');
		ee()->config->_global_vars['recaptcha:script']      = $this->getScript();
		ee()->config->_global_vars['recaptcha:script:html'] = $this->getScriptHtml();

		return $uri_string;
	}

	/**
	 * Add reCAPTCHAv2 script to member templates
	 * @param  object $mbr Member Class Object
	 * @return string (for ee()->extensions->end_script)
	 */
	public function member_manager($mbr)
	{
		$mbr->head_extra .= $this->getScriptHtml();

		return '';
	}

	/**
	 * [getSetting description]
	 * @return [type] [description]
	 */
	private function getSetting($key, $default = FALSE)
	{
		return isset( $this->settings[$key] ) ? $this->settings[$key] : $default;
	}

	/**
	 * Extension settings
	 * @return array
	 */
	public function settings()
	{
		$settings = array(
			'site_key' 	=>  '',
			'secret' 	=>  '',
			'lang'		=> array('s',
				$this->_languages,
				'en'
			),
			'inc_type'	=> array('s',
				array(
					'head' => lang('end_of_head'),
					'body' => lang('end_of_body'),
					'none' => lang('no_inc')
				),
				'head'
			),
			'debug'	=> array('r',
				array(
					'y' => lang('yes'),
					'n' => lang('no')
				),
				'n'
			)
		);

		return $settings;
	}

	/**
	 * Check settings
	 * @return bool
	 */
	private function _validate_settings()
	{
		// Have we been configured at all?
		if( ! isset($this->settings['site_key']) || ! isset($this->settings['secret']) )
		{
			$this->_error_msg = 'reCAPTCHA v2: Not yet configured';

			return FALSE;
		}

		// Be nice
		$this->settings['site_key'] = trim($this->settings['site_key']);
		$this->settings['secret'] = trim($this->settings['secret']);
			
		// Is either key obviously invalid?
		if (strlen($this->settings['site_key'])  != 40 OR
			strlen($this->settings['secret']) != 40)
		{
			$this->_error_msg = 'reCAPTCHA: Invalid public or private key';

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Activate extension
	 * @return void
	 */
	public function activate_extension()
	{
		foreach( $this->hooks as $hook => $method )
		{
			ee()->db->insert('extensions',
				array(
					'class'        => __CLASS__,
					'method'       => $method,
					'hook'         => $hook,
					'settings'     => '',
					'priority'     => 5,
					'version'      => $this->version,
					'enabled'      => 'y'
				)
			);
		}
	}

	/**
	 * Update extension
	 * @param  string $current
	 * @return type
	 */
	public function update_extension($current = '')
	{
		// Check hooks
		if( version_compare($this->version, $current, '>') )
		{
			$q = ee()->db->select('hook,method,settings')->get_where('extensions', array('class' => __CLASS__));
			$have_hooks = array();
			$settings = $q->row('settings');
			foreach( $q->result() as $row )
			{
				$have_hooks[$row->hook] = $row->method;
			}

			$add_hooks = array_diff_assoc($this->hooks, $have_hooks);
			$del_hooks = array_diff_assoc($have_hooks, $this->hooks);

			foreach($del_hooks as $hook => $method)
			{
				ee()->db->where(array(
					'class'  => __CLASS__,
					'method' => $method,
					'hook'   => $hook,
				))->delete('extensions');
			}

			foreach($add_hooks as $hook => $method)
			{
				ee()->db->insert('extensions',
					array(
						'class'        => __CLASS__,
						'method'       => $method,
						'hook'         => $hook,
						'settings'     => $settings,
						'priority'     => 5,
						'version'      => $this->version,
						'enabled'      => 'y'
					)
				);
			}

			// Update version
			ee()->db->update(
				'extensions',
				array(
					'version' => $this->version
				),
				array(
					'class' => __CLASS__
				)
			);
		}

		return TRUE;
	}

	/**
	 * Disable extension
	 * @return void
	 */
	public function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

}
// END CLASS