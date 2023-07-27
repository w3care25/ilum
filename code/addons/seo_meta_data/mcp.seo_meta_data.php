<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seo_meta_data_mcp 
{
	var $base;			// the base url for this module			
	var $form_base;		// base url for forms
	var $module_name = "seo_meta_data";
	var $settings = array();
	var $sidebar;
	
	public function __construct()
    {
        $this->baseUrl = ee('CP/URL', 'addons/settings/seo_meta_data');
        
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Seo_meta_data'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        
        // Sidebar
        $this->sidebar = ee('CP/Sidebar')->make();
        $this->navSettings = $this->sidebar->addHeader(lang('seometa_settings'), $this->baseUrl);
        $this->navTab = $this->sidebar->addHeader(lang('seometa_tab_settings'), $this->baseUrl.'/tab');
        $this->navSocial = $this->sidebar->addHeader(lang('seometa_social'), $this->baseUrl.'/social');
        $this->navTemplate = $this->sidebar->addHeader(lang('seometa_template'), $this->baseUrl.'/template');
        $this->navRobots = $this->sidebar->addHeader(lang('seometa_robots_txt'), $this->baseUrl.'/robots');
        $this->navDocs = $this->sidebar->addHeader(lang('docs'), ee()->cp->masked_url('https://bitbucket.org/omg-monkee/seo-meta-data'))->urlIsExternal(true);
    }
    
    public function index() {
        $this->navSettings->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('seometa_settings');
        $this->data['sections'] = array(
            array(
                array(
                  'title'       => 'seometa_dev_mode',
                  'desc'        => 'seometa_dev_mode_desc',
                  'fields' => array(
                    'seo_meta_data_dev_mode' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_dev_mode']) == TRUE) ? $this->settings['seo_meta_data_dev_mode'] : 'n')
                    )
                  )
                ),
                array(
                    'title' => lang('seometa_tag_manager_id'),
                    'desc' => 'seometa_tag_manager_id_desc',
                    'fields' => array(
                        'seo_meta_data_tag_manager_id'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['seo_meta_data_tag_manager_id']) == TRUE) ? $this->settings['seo_meta_data_tag_manager_id'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('seometa_title_length'),
                    'desc' => 'seometa_title_length_desc',
                    'fields' => array(
                        'seo_meta_title_length'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['seo_meta_title_length']) == TRUE) ? $this->settings['seo_meta_title_length'] : '75')
                        ),
                    ),
                ),
                array(
                    'title' => lang('seometa_sitemap_filename'),
                    'desc' => 'seometa_sitemap_filename_desc',
                    'fields' => array(
                        'seo_meta_data_sitemap_filename'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['seo_meta_data_sitemap_filename']) == TRUE) ? $this->settings['seo_meta_data_sitemap_filename'] : 'sitemap.xml')
                        ),
                    ),
                )
            ),
        );
        $this->data['base_url'] = $this->baseUrl.'/update_settings';
      	$this->data['save_btn_text'] = 'btn_save_settings';
        $this->data['save_btn_text_working'] = 'btn_saving';
        
        return array(
			'heading' => lang('seometa_settings'),
			'body' => ee('View')->make('seo_meta_data:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('seo_meta_data')
			)
		);
    }
    
    public function update_settings()
    {
        
        $this->settings['seo_meta_data_dev_mode'] = ee()->input->post('seo_meta_data_dev_mode');
        $this->settings['seo_meta_data_tag_manager_id'] = ee()->input->post('seo_meta_data_tag_manager_id');
        $this->settings['seo_meta_title_length'] = ee()->input->post('seo_meta_title_length');
        $this->settings['seo_meta_data_sitemap_filename'] = ee()->input->post('seo_meta_data_sitemap_filename');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', 'Seo_meta_data');
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('seometa_updated'))
            ->addToBody(sprintf(lang('seometa_updated_desc')))
            ->defer();

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/seo_meta_data'));
    }
    
    public function tab() {
        $this->navTab->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('seometa_tab_settings');
        $this->data['sections'] = array(
            array(
                array(
                  'title'       => 'seometa_show_title_field',
                  'desc'        => 'seometa_show_title_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_title_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_title_field']) == TRUE) ? $this->settings['seo_meta_data_show_title_field'] : 'y')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_keywords_field',
                  'desc'        => 'seometa_show_keywords_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_keywords_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_keywords_field']) == TRUE) ? $this->settings['seo_meta_data_show_keywords_field'] : 'n')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_description_field',
                  'desc'        => 'seometa_show_description_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_description_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_description_field']) == TRUE) ? $this->settings['seo_meta_data_show_description_field'] : 'y')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_h1_field',
                  'desc'        => 'seometa_show_h1_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_h1_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_h1_field']) == TRUE) ? $this->settings['seo_meta_data_show_h1_field'] : 'n')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_h2_field',
                  'desc'        => 'seometa_show_h2_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_h2_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_h2_field']) == TRUE) ? $this->settings['seo_meta_data_show_h2_field'] : 'n')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_robots_field',
                  'desc'        => 'seometa_show_robots_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_robots_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_robots_field']) == TRUE) ? $this->settings['seo_meta_data_show_robots_field'] : 'y')
                    )
                  )
                ),
                array(
                  'title'       => 'seometa_show_canon_field',
                  'desc'        => 'seometa_show_canon_field_desc',
                  'fields' => array(
                    'seo_meta_data_show_canon_field' => array(
                      'type'    => 'yes_no',
                      'value'   => ((isset($this->settings['seo_meta_data_show_canon_field']) == TRUE) ? $this->settings['seo_meta_data_show_canon_field'] : 'n')
                    )
                  )
                )
            ),
        );
        $this->data['base_url'] = $this->baseUrl.'/update_tab_settings';
      	$this->data['save_btn_text'] = 'btn_save_settings';
        $this->data['save_btn_text_working'] = 'btn_saving';
        
        return array(
			'heading' => lang('seometa_tab_settings'),
			'body' => ee('View')->make('seo_meta_data:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('seo_meta_data')
			)
		);
    }
    
    public function update_tab_settings()
    {
        $this->settings['seo_meta_data_show_title_field'] = ee()->input->post('seo_meta_data_show_title_field');
        $this->settings['seo_meta_data_show_keywords_field'] = ee()->input->post('seo_meta_data_show_keywords_field');
        $this->settings['seo_meta_data_show_description_field'] = ee()->input->post('seo_meta_data_show_description_field');
        $this->settings['seo_meta_data_show_h1_field'] = ee()->input->post('seo_meta_data_show_h1_field');
        $this->settings['seo_meta_data_show_h2_field'] = ee()->input->post('seo_meta_data_show_h2_field');
        $this->settings['seo_meta_data_show_robots_field'] = ee()->input->post('seo_meta_data_show_robots_field');
        $this->settings['seo_meta_data_show_canon_field'] = ee()->input->post('seo_meta_data_show_canon_field');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', 'Seo_meta_data');
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('seometa_updated'))
            ->addToBody(sprintf(lang('seometa_updated_desc')))
            ->defer();

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/seo_meta_data/tab'));
    }
    
    public function social() {
        $this->navSocial->isActive();
        
        //Grid Field
        $sm_grid = ee('CP/GridInput', array(
          'field_name' => 'seo_meta_social_links'
        ));
        
        $sm_grid->setColumns(
          array(
            'seometa_social_title' => array(
              'desc'  => 'seometa_social_title_desc'
            ),
            'seometa_social_url' => array(
              'desc'  => 'seometa_social_url_desc'
            ),
            'seometa_social_icon' => array(
              'desc'  => 'seometa_social_icon_desc'
            )
          )
        );
        
        $sm_grid->setNoResultsText('seometa_no_links', 'seometa_add_link');
        
        $data = array();
        $i = 1;
        foreach ($this->settings['seo_meta_social_links']['rows'] as $row) {
          $data[] = array(
            'attrs' => array('row_id' => $i),
            'columns' => array(
              form_input('seo_meta_social_title', $row['seo_meta_social_title']),
              form_input('seo_meta_social_url', $row['seo_meta_social_url']),
              form_input('seo_meta_social_icon', $row['seo_meta_social_icon'])
            )
          );
          $i++;
        }
        
        $sm_grid->setData($data);
        
        $sm_grid->setBlankRow(array(
          form_input('seo_meta_social_title'),
          form_input('seo_meta_social_url'),
          form_input('seo_meta_social_icon')
        ));
        
        $sm_grid->loadAssets();
        
        //Form
        $this->data['cp_page_title'] = lang('seometa_social');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('seometa_twitter_handle'),
                    'desc' => 'seometa_twitter_handle_desc',
                    'fields' => array(
                        'seo_meta_twitter_handle'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['seo_meta_twitter_handle']) == TRUE) ? $this->settings['seo_meta_twitter_handle'] : ''),
                            'placeholder' => '@'
                        ),
                    ),
                ),
                array(
                    'title' => lang('seometa_default_image'),
                    'desc' => 'seometa_default_image_desc',
                    'fields' => array(
                        'seo_meta_default_image'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['seo_meta_default_image']) == TRUE) ? $this->settings['seo_meta_default_image'] : '')
                        ),
                    ),
                ),
                array(
                  'title' => 'seo_meta_social_media',
                  'desc' => 'seo_meta_social_media_desc',
                  'wide' => TRUE,
                  'grid' => TRUE,
                  'fields' => array(
                    'seo_meta_social_media_links' => array(
                      'type' => 'html',
                      'content' => ee('View')->make('ee:_shared/table')->render($sm_grid->viewData())
                    )
                  )
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_social_settings';
      	$this->data['save_btn_text'] = 'btn_save_settings';
        $this->data['save_btn_text_working'] = 'btn_saving';
        
        return array(
			'heading' => lang('seometa_social'),
			'body' => ee('View')->make('seo_meta_data:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('seo_meta_data')
			)
		);
    }
    
    public function update_social_settings()
    {
        
        $this->settings['seo_meta_twitter_handle'] = ee()->input->post('seo_meta_twitter_handle');
        $this->settings['seo_meta_default_image'] = ee()->input->post('seo_meta_default_image');
        $this->settings['seo_meta_social_links'] = ee()->input->post('seo_meta_social_links');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', 'Seo_meta_data');
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('seometa_updated'))
            ->addToBody(sprintf(lang('seometa_updated_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/social');
    }
    
    public function template() {
        $this->navTemplate->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('seometa_template');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('seometa_template_field'),
                    'desc' => 'seometa_template_desc',
                    'fields' => array(
                        'seo_meta_template'=> array(
                            'type' => 'textarea',
                            'value' => ((isset($this->settings['seo_meta_template']) == TRUE) ? $this->settings['seo_meta_template'] : ''),
                            
                        ),
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_template_settings';
      	$this->data['save_btn_text'] = 'btn_save_settings';
        $this->data['save_btn_text_working'] = 'btn_saving';
        
        $this->loadCodeMirrorAssets('seo_meta_template');
        
        return array(
			'heading' => lang('seometa_template'),
			'body' => ee('View')->make('seo_meta_data:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('seo_meta_data')
			)
		);
    }
    
    public function update_template_settings()
    {
        
        $this->settings['seo_meta_template'] = ee()->input->post('seo_meta_template');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', 'Seo_meta_data');
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('seometa_updated'))
            ->addToBody(sprintf(lang('seometa_updated_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/template');
    }
    
    public function robots() {
        $this->navRobots->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('seometa_robots_txt');
        
        $fields = array();
        $return = "";
        if (is_really_writable('../robots.txt')) {
            $fields = array(
                'robots'=> array(
                    'type' => 'textarea',
                    'rows' => 20,
                    'value' => file_get_contents("../robots.txt")
                )
            );
            $return = "/update_robots";
        }
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('seometa_robots_txt'),
                    'desc' => 'seometa_robots_txt_desc',
                    'fields' => $fields
                )
            )
        );
        
        $this->loadCodeMirrorAssets('robots');
        
        $this->data['base_url'] = $this->baseUrl.$return;
      	$this->data['save_btn_text'] = 'btn_save_settings';
        $this->data['save_btn_text_working'] = 'btn_saving';
        
        return array(
			'heading' => lang('seometa_robots_txt'),
			'body' => ee('View')->make('seo_meta_data:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('seo_meta_data')
			)
		);
    }
    
    public function update_robots()
    {
        $robots = fopen("../robots.txt", "w") or die("Unable to open file!");
        fwrite($robots, ee()->input->post('robots'));
        fclose($myfile);
        
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('seometa_robots_updated'))
            ->addToBody(sprintf(lang('seometa_robots_updated_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/robots');
    }
    
    protected function loadCodeMirrorAssets($selector = 'template_data')
	{
		ee()->javascript->set_global(
			'editor.lint', $this->_get_installed_plugins_and_modules()
		);

		$height = '250px';

		if ($height !== FALSE)
		{
			ee()->javascript->set_global(
				'editor.height', $height
			);
		}

		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror.css'));
		ee()->cp->add_to_head(ee()->view->head_link('css/codemirror-additions.css'));
		ee()->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'ui'		=> 'resizable',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/lint',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',
				)
			)
		);
		ee()->javascript->output("$('textarea[name=\"" . $selector . "\"]').toggleCodeMirror();");
	}

	/**
	 *  Returns installed module information for CodeMirror linting
	 */
	private function _get_installed_plugins_and_modules()
	{
		$addons = array_keys(ee('Addon')->all());

		$modules = ee('Model')->get('Module')->all()->pluck('module_name');
		$plugins = ee('Model')->get('Plugin')->all()->pluck('plugin_package');

		$modules = array_map('strtolower', $modules);
		$plugins = array_map('strtolower', $plugins);
		$installed = array_merge($modules, $plugins);

		return array(
			'available' => $installed,
			'not_installed' => array_values(array_diff($addons, $installed))
		);
	}

}

?>