<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;

class Ilum_wallet_mcp 
{
    var $baseUrl;       // the base url for this module         
    var $module_name = "Ilum_wallet";
    var $settings;

    public function __construct()
    {
        $this->baseUrl = ee('CP/URL', 'addons/settings/ilum_wallet');
        
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = '".$this->module_name."'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        
        // Sidebar
		$this->sidebar = ee('CP/Sidebar')->make();
		$this->navDashboard = $this->sidebar->addHeader(lang('wallet_api_keys'), $this->baseUrl);
		$this->navNewKey = $this->sidebar->addHeader(lang('wallet_new_api_key'), $this->baseUrl.'/new_key');
		$this->navSettings = $this->sidebar->addHeader(lang('wallet_settings'), $this->baseUrl.'/settings');
		$this->navFields = $this->sidebar->addHeader(lang('wallet_fields'), $this->baseUrl.'/fields');
		$this->navEmails = $this->sidebar->addHeader(lang('wallet_email_templates'), $this->baseUrl.'/emails');
		$this->navDocs = $this->sidebar->addHeader(lang('wallet_documentation'), ee()->cp->masked_url(lang('wallet_docs_url')))->urlIsExternal(true);
    }
    
    public function index() {
        $this->navDashboard->isActive();
        
        $table = ee('CP/Table', array(
          'reorder' => FALSE,
          'sortable' => TRUE,
          'autosearch' => TRUE,
          'autosort' => TRUE,
          'limit'   => 10000
        ));
        
        $table->setColumns(
          array(
            'user',
            'key',
            'API Type',
            'status'  => array(
              'type'  => Table::COL_STATUS
            ),
            'actions'   => array(
              'type'  => Table::COL_TOOLBAR
            )
          )
        );
        
        $data = array();
        
        $query = ee()->db->select('id, user, key, api_type, active')->from('ilum_wallet_api_keys')->limit(10000)->order_by('user', 'asc')->get();
        
        foreach($query->result_array() AS $row) {
            $data[] = array(
                $row['user'],
                array(
                    'content' => $row['key'],
                    'href' => "javascript:copyToClipboard('".$row['key']."');"  
                ),
                $row['api_type'],
                $row['active'],
                array('toolbar_items' => array(
                  'edit' => array(
                    'href' => $this->baseUrl.'/edit&id='.$row['id'],
                    'title' => lang('edit')
                  ),
                  'remove'  => array(
                    'href' => $this->baseUrl.'/delete&id='.$row['id'],
                    'title' => lang('remove')
                  )
                ))
              );
        }
        
        $table->setData($data);
        
        $vars['table'] = $table->viewData(ee('CP/URL', 'addons/settings/ilum_wallet'));
        
        return array(
			'heading' => lang('wallet_api_keys'),
			'body' => ee('View')->make('ilum_wallet:table')->render($vars),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('ilum_wallet')
			)
		);
    }
    
    public function generate_key() {
		$user = ee()->input->post('user');
		$api_type = ee()->input->post('api_type');
        if ( $user != '' && $api_type != '' ) {
            $key = $this->getGUID();
            
            ee()->db->insert('ilum_wallet_api_keys', array('user' => $user, 'key' => $key, 'active' => 'open', 'api_type' => $api_type));
            
            $message = lang('wallet_alert_generated_desc');
            $message = str_replace('USER', $user, $message);
            $message = str_replace('API_KEY', $key, $message);
            
            ee('CP/Alert')->makeStandard('shared-form')
            ->asSuccess()
            ->withTitle(lang('wallet_alert_generated'))
            ->addToBody(sprintf($message))
            ->defer();
            
            ee()->functions->redirect($this->baseUrl);
		}
	
	    ee('CP/Alert')->makeInline('shared-form')
            ->asIssue()
            ->withTitle(lang('wallet_alert_not_generated'))
            ->addToBody(sprintf(lang('wallet_alert_not_generated_desc')))
            ->defer();
            
        ee()->functions->redirect($this->baseUrl.'/new_key');
    }
    
    public function new_key() {
        $this->navNewKey->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_new_api_key');
        
        $this->data['sections'] = array(
            array(
				array(
                    'title' => lang('user'),
                    'fields' => array(
                        'user'=> array(
                            'type' => 'text',
							'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('API Type'),
                    'fields' => array(
                        'api_type'=> array(
                            'type' => 'dropdown',
							'required' => TRUE,
							'choices' => array(
                              'Ilum-API'        => 'Ilum API',
                              'Ick-API'     => 'Ick API',
                              ),
                        ),
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/generate_key' ;
        $this->data['save_btn_text'] = 'wallet_btn_generate_key';
        $this->data['save_btn_text_working'] = 'wallet_btn_generating';
        
        return array(
            'heading' => lang('wallet_new_api_key'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function update_settings() {
		$id = ee()->input->post('id');
		$status = ee()->input->post('status');
		$api_type = ee()->input->post('api_type');
		if ($status == 'y') { $status = 'open'; } else { $status = 'closed'; }
		$user = ee()->input->post('user');

        if (is_numeric($id)) {
            ee()->db->update('ilum_wallet_api_keys', array('active' => $status, 'user' => $user, 'api_type' => $api_type), array('id' => $id));
            
            $updated = lang('wallet_alert_updated_desc');
            $updated = str_replace('USER', $user, $updated);
            
            if ($status == 'open') {
                ee('CP/Alert')->makeStandard('shared-form')
                ->asSuccess()
                ->withTitle(lang('wallet_alert_updated'))
                ->addToBody(sprintf($updated))
                ->defer();
            } else {
                ee('CP/Alert')->makeStandard('shared-form')
                ->asIssue()->canClose()
                ->withTitle(lang('wallet_alert_updated'))
                ->addToBody(sprintf($updated))
                ->defer();
            }
		}
            
        ee()->functions->redirect($this->baseUrl);
    }
    
    public function edit() {
        $this->navDashboard->isActive();
        
        $id = ee()->input->get('id');
        
        $query = ee()->db->select('*')->from('ilum_wallet_api_keys')->where(array('id' => $id))->limit(1)->get();
        
        if ($query->num_rows() > 0) {
            $user = $query->row('user');
            $api_type = $query->row('api_type');
            $active = $query->row('active');
            if ($active == 'open') { $status = 'y'; } else { $status = 'n'; }
        }
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_edit_key');
        
        $this->data['sections'] = array(
            array(
				array(
                    'title' => lang('user'),
                    'fields' => array(
                        'user'=> array(
                            'type' => 'text',
							'required' => TRUE,
							'value'    => $user
                        ),
                        'id'=> array(
                            'type' => 'hidden',
                            'value' => $id
                        ),
                    ),
                )
            ),
            array(
                array(
                    'title' => lang('API Type'),
                    'fields' => array(
                        'api_type'=> array(
                            'type' => 'dropdown',
							'required' => TRUE,
							'value' => $api_type,
							'choices' => array(
                              'Ilum-API'        => 'Ilum API',
                              'Ick-API'     => 'Ick API',
                              ),
                        ),
                    ),
                ),
                array(
                    'title' => lang('status'),
                    'desc' => '',
                    'fields' => array(
                        'status'=> array(
                            'type' => 'yes_no',
                            'value' => $status,
                            'required' => TRUE
                        ),
                    ),
                )
                
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_settings' ;
        $this->data['save_btn_text'] = 'wallet_btn_save_settings';
        $this->data['save_btn_text_working'] = 'wallet_btn_saving';
        
        return array(
            'heading' => lang('wallet_edit_key'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function delete_key() {
		$id = ee()->input->post('id');

        if (is_numeric($id)) {
            ee()->db->delete('ilum_wallet_api_keys', array('id' => $id));
            
            ee('CP/Alert')->makeStandard('shared-form')
            ->asIssue()->canClose()
            ->withTitle(lang('wallet_alert_deleted'))
            ->addToBody(sprintf(lang('wallet_alert_deleted_desc')))
            ->defer();
		}
            
        ee()->functions->redirect($this->baseUrl);
    }
    
    public function delete() {
        $this->navDashboard->isActive();
        
        $id = ee()->input->get('id');
        
        $query = ee()->db->select('*')->from('ilum_wallet_api_keys')->where(array('id' => $id))->limit(1)->get();
        
        if ($query->num_rows() > 0) {
            $key = $query->row('key');
            $title = lang('wallet_confirm_delete_key');
            $title = str_replace('KEY', $key, $title);
        }
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_delete_key');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => $title,
                    'desc' => 'wallet_confirm_delete_key_desc',
                    'fields' => array(
                        'id'=> array(
                            'type' => 'hidden',
                            'value' => $id
                        )
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/delete_key' ;
        $this->data['save_btn_text'] = 'wallet_btn_delete';
        $this->data['save_btn_text_working'] = 'wallet_btn_deleting';
        
        return array(
            'heading' => lang('wallet_delete_key'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function settings() {
        $this->navSettings->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_settings');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('wallet_env'),
                    'fields' => array(
                        'wallet_env' => array(
                          'type'    => 'dropdown',
                          'choices' => array(
                              ''        => lang('wallet_env_production'),
                              'dev-'     => lang('wallet_env_development'),
                              'sandbox-' => lang('wallet_env_sandbox')
                              ),
                          'value'   => ((isset($this->settings['wallet_env']) == TRUE) ? $this->settings['wallet_env'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_ilum_api_user'),
                    'fields' => array(
                        'wallet_ilum_api_user'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_ilum_api_user']) == TRUE) ? $this->settings['wallet_ilum_api_user'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_ilum_api_key'),
                    'fields' => array(
                        'wallet_ilum_api_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_ilum_api_key']) == TRUE) ? $this->settings['wallet_ilum_api_key'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
				array(
                    'title' => lang('wallet_stripe_test_mode'),
                    'desc'  => 'wallet_stripe_test_mode_desc',
                    'fields' => array(
                        'wallet_stripe_test_mode' => array(
                          'type'    => 'yes_no',
                          'value'   => ((isset($this->settings['wallet_stripe_test_mode']) == TRUE) ? $this->settings['wallet_stripe_test_mode'] : 'n')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_stripe_publish_test_key'),
                    'fields' => array(
                        'wallet_stripe_publish_test_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_stripe_publish_test_key']) == TRUE) ? $this->settings['wallet_stripe_publish_test_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_stripe_secret_test_key'),
                    'fields' => array(
                        'wallet_stripe_secret_test_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_stripe_secret_test_key']) == TRUE) ? $this->settings['wallet_stripe_secret_test_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_stripe_publish_live_key'),
                    'fields' => array(
                        'wallet_stripe_publish_live_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_stripe_publish_live_key']) == TRUE) ? $this->settings['wallet_stripe_publish_live_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_stripe_secret_live_key'),
                    'fields' => array(
                        'wallet_stripe_secret_live_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_stripe_secret_live_key']) == TRUE) ? $this->settings['wallet_stripe_secret_live_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_plaid_env'),
                    'desc'  => 'wallet_plaid_env_desc',
                    'fields' => array(
                        'wallet_plaid_env' => array(
                          'type'    => 'dropdown',
                          'choices' => array(
                            'sandbox'       => lang('wallet_plaid_env_sandbox'),
                            'development'   => lang('wallet_plaid_env_development'),
                            'production'    => lang('wallet_plaid_env_production')
                          ),
                          'value'   => ((isset($this->settings['wallet_plaid_env']) == TRUE) ? $this->settings['wallet_plaid_env'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_plaid_client_id'),
                    'fields' => array(
                        'wallet_plaid_client_id'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_plaid_client_id']) == TRUE) ? $this->settings['wallet_plaid_client_id'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_plaid_public_key'),
                    'fields' => array(
                        'wallet_plaid_public_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_plaid_public_key']) == TRUE) ? $this->settings['wallet_plaid_public_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_plaid_secret_key'),
                    'fields' => array(
                        'wallet_plaid_secret_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_plaid_secret_key']) == TRUE) ? $this->settings['wallet_plaid_secret_key'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_paypal_account'),
                    'fields' => array(
                        'paypal_account'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['paypal_account']) == TRUE) ? $this->settings['paypal_account'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_paypal_pdt_token'),
                    'fields' => array(
                        'paypal_pdt_token'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['paypal_pdt_token']) == TRUE) ? $this->settings['paypal_pdt_token'] : '')
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_currency_ratio'),
                    'fields' => array(
                        'wallet_currency_ratio'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_currency_ratio']) == TRUE) ? $this->settings['wallet_currency_ratio'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_ip_api_key'),
                    'fields' => array(
                        'wallet_ip_api_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_ip_api_key']) == TRUE) ? $this->settings['wallet_ip_api_key'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_minimum_age'),
                    'desc'  => 'wallet_minimum_age_desc',
                    'fields' => array(
                        'wallet_minimum_age'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_minimum_age']) == TRUE) ? $this->settings['wallet_minimum_age'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_processing_percent'),
                    'desc'  => 'wallet_processing_percent_desc',
                    'fields' => array(
                        'wallet_processing_percent'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_processing_percent']) == TRUE) ? $this->settings['wallet_processing_percent'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_processing_fee'),
                    'desc'  => 'wallet_processing_fee_desc',
                    'fields' => array(
                        'wallet_processing_fee'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_processing_fee']) == TRUE) ? $this->settings['wallet_processing_fee'] : ''),
                            'required' => TRUE
                        ),
                    ),
                ),
                array(
                    'title' => lang('wallet_ick_test_mode'),
                    'desc'  => 'wallet_ick_test_mode_desc',
                    'fields' => array(
                        'wallet_ick_test_mode' => array(
                          'type'    => 'yes_no',
                          'value'   => ((isset($this->settings['wallet_ick_test_mode']) == TRUE) ? $this->settings['wallet_ick_test_mode'] : 'n')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_ick_live_security_key'),
                    'desc'  => 'wallet_ick_desc',
                    'fields' => array(
                        'wallet_ick_live_security_key'=> array(
                            'type' => 'text',
                            'value' => ((isset($this->settings['wallet_ick_live_security_key']) == TRUE) ? $this->settings['wallet_ick_live_security_key'] : '')
                        ),
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/settings_save' ;
        $this->data['save_btn_text'] = 'wallet_btn_save_settings';
        $this->data['save_btn_text_working'] = 'wallet_btn_saving';
        
        return array(
            'heading' => lang('wallet_settings'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function settings_save() {
        $this->settings['wallet_env'] = ee()->input->post('wallet_env');
        $this->settings['wallet_ilum_api_user'] = ee()->input->post('wallet_ilum_api_user');
        $this->settings['wallet_ilum_api_key'] = ee()->input->post('wallet_ilum_api_key');
        $this->settings['wallet_stripe_test_mode'] = ee()->input->post('wallet_stripe_test_mode');
        $this->settings['wallet_stripe_secret_test_key'] = ee()->input->post('wallet_stripe_secret_test_key');
        $this->settings['wallet_stripe_publish_test_key'] = ee()->input->post('wallet_stripe_publish_test_key');
        $this->settings['wallet_stripe_secret_live_key'] = ee()->input->post('wallet_stripe_secret_live_key');
        $this->settings['wallet_stripe_publish_live_key'] = ee()->input->post('wallet_stripe_publish_live_key');
        $this->settings['wallet_plaid_env'] = ee()->input->post('wallet_plaid_env');
        $this->settings['wallet_plaid_client_id'] = ee()->input->post('wallet_plaid_client_id');
        $this->settings['wallet_plaid_public_key'] = ee()->input->post('wallet_plaid_public_key');
        $this->settings['wallet_plaid_secret_key'] = ee()->input->post('wallet_plaid_secret_key');
        $this->settings['wallet_currency_ratio'] = ee()->input->post('wallet_currency_ratio');
        $this->settings['wallet_ip_api_key'] = ee()->input->post('wallet_ip_api_key');
        $this->settings['wallet_minimum_age'] = ee()->input->post('wallet_minimum_age');
        $this->settings['paypal_account'] = ee()->input->post('paypal_account');
        $this->settings['paypal_pdt_token'] = ee()->input->post('paypal_pdt_token');
        $this->settings['wallet_processing_percent'] = ee()->input->post('wallet_processing_percent');
        $this->settings['wallet_processing_fee'] = ee()->input->post('wallet_processing_fee');
        $this->settings['wallet_ick_test_mode'] = ee()->input->post('wallet_ick_test_mode');
        $this->settings['wallet_ick_live_security_key'] = ee()->input->post('wallet_ick_live_security_key');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', $this->module_name);
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('wallet_alert_settings'))
            ->addToBody(sprintf(lang('wallet_alert_settings_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/settings');
    }
    
    public function fields() {
        $this->navFields->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_fields');
        
        //Member Fields
        $m_fields = array();
		$query = ee()->db->select('m_field_id, m_field_label')->from('member_fields')->order_by('m_field_label', 'asc')->get();
		foreach($query->result_array() AS $row) {
		    $m_fields[$row['m_field_id']] = $row['m_field_label'].' ('.$row['m_field_id'].')';
		}
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('wallet_first_name_field'),
                    'fields' => array(
                        'wallet_first_name_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_first_name_field']) == TRUE) ? $this->settings['wallet_first_name_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_last_name_field'),
                    'fields' => array(
                        'wallet_last_name_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_last_name_field']) == TRUE) ? $this->settings['wallet_last_name_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_company_field'),
                    'fields' => array(
                        'wallet_company_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_company_field']) == TRUE) ? $this->settings['wallet_company_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_address_field'),
                    'fields' => array(
                        'wallet_address_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_address_field']) == TRUE) ? $this->settings['wallet_address_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_address2_field'),
                    'fields' => array(
                        'wallet_address2_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_address2_field']) == TRUE) ? $this->settings['wallet_address2_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_city_field'),
                    'fields' => array(
                        'wallet_city_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_city_field']) == TRUE) ? $this->settings['wallet_city_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_state_field'),
                    'fields' => array(
                        'wallet_state_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_state_field']) == TRUE) ? $this->settings['wallet_state_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_zip_field'),
                    'fields' => array(
                        'wallet_zip_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_zip_field']) == TRUE) ? $this->settings['wallet_zip_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_country_field'),
                    'fields' => array(
                        'wallet_country_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_country_field']) == TRUE) ? $this->settings['wallet_country_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_phone_field'),
                    'fields' => array(
                        'wallet_phone_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_phone_field']) == TRUE) ? $this->settings['wallet_phone_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_phone_email_field'),
                    'fields' => array(
                        'wallet_phone_email_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_phone_email_field']) == TRUE) ? $this->settings['wallet_phone_email_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_birth_day_field'),
                    'fields' => array(
                        'wallet_birth_day_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_birth_day_field']) == TRUE) ? $this->settings['wallet_birth_day_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_birth_month_field'),
                    'fields' => array(
                        'wallet_birth_month_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_birth_month_field']) == TRUE) ? $this->settings['wallet_birth_month_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_birth_year_field'),
                    'fields' => array(
                        'wallet_birth_year_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_birth_year_field']) == TRUE) ? $this->settings['wallet_birth_year_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_stripe_customer_id_field'),
                    'fields' => array(
                        'wallet_stripe_customer_id_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_stripe_customer_id_field']) == TRUE) ? $this->settings['wallet_stripe_customer_id_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_unique_id_field'),
                    'fields' => array(
                        'wallet_unique_id_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_unique_id_field']) == TRUE) ? $this->settings['wallet_unique_id_field'] : '')
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_app_header_field'),
                    'fields' => array(
                        'wallet_app_header_field' => array(
                          'type'    => 'dropdown',
                          'choices' => $m_fields,
                          'value'   => ((isset($this->settings['wallet_app_header_field']) == TRUE) ? $this->settings['wallet_app_header_field'] : '')
                        )
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/fields_save' ;
        $this->data['save_btn_text'] = 'wallet_btn_save_settings';
        $this->data['save_btn_text_working'] = 'wallet_btn_saving';
        
        return array(
            'heading' => lang('wallet_fields'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function fields_save() {
        $this->settings['wallet_first_name_field'] = ee()->input->post('wallet_first_name_field');
        $this->settings['wallet_last_name_field'] = ee()->input->post('wallet_last_name_field');
        $this->settings['wallet_company_field'] = ee()->input->post('wallet_company_field');
        $this->settings['wallet_address_field'] = ee()->input->post('wallet_address_field');
        $this->settings['wallet_address2_field'] = ee()->input->post('wallet_address2_field');
        $this->settings['wallet_city_field'] = ee()->input->post('wallet_city_field');
        $this->settings['wallet_state_field'] = ee()->input->post('wallet_state_field');
        $this->settings['wallet_zip_field'] = ee()->input->post('wallet_zip_field');
        $this->settings['wallet_country_field'] = ee()->input->post('wallet_country_field');
        $this->settings['wallet_phone_field'] = ee()->input->post('wallet_phone_field');
        $this->settings['wallet_phone_email_field'] = ee()->input->post('wallet_phone_email_field');
        $this->settings['wallet_birth_day_field'] = ee()->input->post('wallet_birth_day_field');
        $this->settings['wallet_birth_month_field'] = ee()->input->post('wallet_birth_month_field');
        $this->settings['wallet_birth_year_field'] = ee()->input->post('wallet_birth_year_field');
        $this->settings['wallet_stripe_customer_id_field'] = ee()->input->post('wallet_stripe_customer_id_field');
        $this->settings['wallet_unique_id_field'] = ee()->input->post('wallet_unique_id_field');
        $this->settings['wallet_app_header_field'] = ee()->input->post('wallet_app_header_field');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', $this->module_name);
        $upd= ee()->db->update('exp_modules');

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('wallet_alert_settings'))
            ->addToBody(sprintf(lang('wallet_alert_settings_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/fields');
    }
    
    public function emails() {
        $this->navEmails->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('wallet_email_templates');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('wallet_emails_from_name'),
                    'fields' => array(
                        'wallet_emails_from_name' => array(
                          'type'    => 'text',
                          'value' => ((isset($this->settings['wallet_emails_from_name']) == TRUE) ? $this->settings['wallet_emails_from_name'] : ''),
                          'required' => TRUE
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_emails_from_email'),
                    'fields' => array(
                        'wallet_emails_from_email' => array(
                          'type'    => 'text',
                          'value' => ((isset($this->settings['wallet_emails_from_email']) == TRUE) ? $this->settings['wallet_emails_from_email'] : ''),
                          'required' => TRUE
                        )
                    ),
                ),
                array(
                    'title' => lang('wallet_emails_reply_to'),
                    'fields' => array(
                        'wallet_emails_reply_to' => array(
                          'type'    => 'text',
                          'value' => ((isset($this->settings['wallet_emails_reply_to']) == TRUE) ? $this->settings['wallet_emails_reply_to'] : ''),
                          'required' => TRUE
                        )
                    ),
                ),
            )
        );
        
        $query = ee()->db->select('*')->from('ilum_wallet_email_templates')->order_by('id', 'asc')->get();
        
        foreach($query->result_array() AS $row) {
            $this->data['sections'][0][] = array(
                'title' => lang('wallet_emails_'.$row['short_name']),
                'fields' => array(
                    'id[]' => array(
                        'type' => 'hidden',
                        'value' => $row['id']
                    ),
                    'html[]' => array(
                      'type'    => 'dropdown',
                      'choices' => array(
                          '0'   => lang('wallet_text_email'),
                          '1'   => lang('wallet_html_email')
                      ),
                      'value'   => $row['html']
                    ),
                    'subject[]' => array(
                      'type'    => 'text',
                      'value'   => $row['subject'],
                      'placeholder' => lang('wallet_subject')
                    ),
                    'template[]' => array(
                        'type' => 'textarea',
                        'value' => $row['template'],
                    ),
                ),
            );
        }
        
        $this->loadCodeMirrorAssets('template[]');
        
        $this->data['base_url'] = $this->baseUrl.'/emails_save';
        $this->data['save_btn_text'] = 'wallet_btn_save_settings';
        $this->data['save_btn_text_working'] = 'wallet_btn_saving';
        
        return array(
            'heading' => lang('wallet_email_templates'),
            'body' => ee('View')->make('ilum_wallet:index')->render($this->data),
            'sidebar' => $this->sidebar,
            'breadcrumb'    => array(
                $this->baseUrl->compile() => lang('ilum_wallet')
            )
        );
    }
    
    public function emails_save() {
        $this->settings['wallet_emails_from_name'] = ee()->input->post('wallet_emails_from_name');
        $this->settings['wallet_emails_from_email'] = ee()->input->post('wallet_emails_from_email');
        $this->settings['wallet_emails_reply_to'] = ee()->input->post('wallet_emails_reply_to');

        // Put it Back
        ee()->db->set('settings', serialize($this->settings));
        ee()->db->where('module_name', $this->module_name);
        $upd= ee()->db->update('exp_modules');
        
        //Emails
        $ids = ee()->input->post('id');
        $htmls = ee()->input->post('html');
        $subjects = ee()->input->post('subject');
        $templates = ee()->input->post('template');
        
        foreach ($ids AS $key => $id) {
            ee()->db->update('ilum_wallet_email_templates', array('html' => $htmls[$key], 'subject' => $subjects[$key], 'template' => $templates[$key]), array('id' => $id));
        }

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('wallet_alert_settings'))
            ->addToBody(sprintf(lang('wallet_alert_settings_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/emails');
    }
    
    private function getGUID(){
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
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