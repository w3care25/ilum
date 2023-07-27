<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Monstrous_email_mcp 
{
	var $baseUrl;		// the base url for this module			
	var $module_name = "monstrous_email";
	var $settings;
	var $sidebar;

	public function __construct()
    {
        $this->baseUrl = ee('CP/URL', 'addons/settings/monstrous_email');
        
        $query = ee()->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Monstrous_email'");
        if ($query->row('settings') != FALSE) {
            $this->settings = @unserialize($query->row('settings'));
        }
        if (!isset($this->settings['monstrous_email_url'])) {
            $this->settings['monstrous_email_url'] = '';
        }
        if (!isset($this->settings['monstrous_email_key'])) {
            $this->settings['monstrous_email_key'] = '';
        }
        
        $omg_services = ee('Addon')->get('omg_services');
        
        if (empty($omg_services)) {
            ee('CP/Alert')->makeBanner('monstrous_email_no_omg_services')
              ->asIssue()
              ->withTitle(lang('monstrous_email_error'))
              ->addToBody(lang('monstrous_email_no_omg_services'))
              ->now();
              
            $this->sidebar = '';
        } else {
            require_once(PATH_THIRD.'omg_services/mod.omg_services.php');
            
            // Sidebar
            $this->sidebar = ee('CP/Sidebar')->make();
            $this->navDashboard = $this->sidebar->addHeader(lang('monstrous_email_dashboard'), $this->baseUrl);
            $this->navSalesForce = $this->sidebar->addHeader(lang('monstrous_email_sf_settings'), $this->baseUrl.'/salesforce');
            $this->navDocs = $this->sidebar->addHeader(lang('monstrous_email_documentation'), ee()->cp->masked_url(lang('monstrous_email_docs_url')))->urlIsExternal(true);
            }
    }
    
    public function index() {
        $this->data['cp_page_title'] = lang('monstrous_email_dashboard');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('monstrous_email_url'),
                    'desc' => 'monstrous_email_url_desc',
                    'fields' => array(
                        'monstrous_email_url'=> array(
                            'type' => 'text',
                            'value' => $this->settings['monstrous_email_url']
                        ),
                    ),
                ),
                array(
                    'title' => lang('monstrous_email_key'),
                    'desc' => 'monstrous_email_key_desc',
                    'fields' => array(
                        'monstrous_email_key'=> array(
                            'type' => 'text',
                            'value' => $this->settings['monstrous_email_key']
                        ),
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_settings' ;
      	$this->data['save_btn_text'] = 'monstrous_email_btn_save_settings';
        $this->data['save_btn_text_working'] = 'monstrous_email_btn_saving';
        
        return array(
			'heading' => lang('monstrous_email_dashboard'),
			'body' => ee('View')->make('monstrous_email:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('monstrous_email')
			)
		);
    }
    
    public function salesforce() {
        $this->_salesforce_tabs();
        $this->navSalesForce->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('monstrous_email_sf_settings');
        
        $this->data['sections'] = array(
            array(
                array(
                    'title' => lang('monstrous_email_sf_url'),
                    'desc' => 'monstrous_email_sf_url_desc',
                    'fields' => array(
                        'monstrous_email_sf_url'=> array(
                            'type' => 'text',
                            'value' => ''
                        ),
                    ),
                ),
                array(
                    'title' => lang('monstrous_email_sf_username'),
                    'desc' => 'monstrous_email_sf_username_desc',
                    'fields' => array(
                        'monstrous_email_sf_username'=> array(
                            'type' => 'text',
                            'value' => ''
                        ),
                    ),
                ),
                array(
                    'title' => lang('monstrous_email_sf_password'),
                    'desc' => 'monstrous_email_sf_password_desc',
                    'fields' => array(
                        'monstrous_email_sf_password'=> array(
                            'type' => 'password',
                            'value' => ''
                        ),
                    ),
                ),
                array(
                    'title' => lang('monstrous_email_sf_wsdl_file'),
                    'desc' => 'monstrous_email_sf_wsdl_file_desc',
                    'fields' => array(
                        'monstrous_email_sf_wsdl_file'=> array(
                            'type' => 'text',
                            'value' => ''
                        ),
                    ),
                )
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_sf_settings' ;
      	$this->data['save_btn_text'] = 'monstrous_email_btn_save_settings';
        $this->data['save_btn_text_working'] = 'monstrous_email_btn_saving';
        
        return array(
			'heading' => lang('monstrous_email_sf_settings'),
			'body' => ee('View')->make('monstrous_email:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('monstrous_email')
			)
		);
    }
    
    public function salesforce_leads() {
        $this->_salesforce_tabs();
        $this->navSalesForce->isActive();
        $this->navSalesForceLeads->isActive();
        
        //Form
        $this->data['cp_page_title'] = lang('monstrous_email_leads');
        
        $this->data['sections'] = array(
            array(
                array(
                  'title' => 'monstrous_email_sf_leads_fields',
                  'desc' => 'monstrous_email_sf_leads_fields_desc',
                  'fields' => array(
                    'monstrous_email_sf_leads_fields' => array(
                      'type' => 'checkbox',
                      'choices' => array(
                        'address' => lang('sf_address'),
                        'annual_revenue' => lang('sf_annual_revenue')
                      )
                    )
                  )
                ),
            )
        );
        
        $this->data['base_url'] = $this->baseUrl.'/update_sf_settings' ;
      	$this->data['save_btn_text'] = 'monstrous_email_btn_save_settings';
        $this->data['save_btn_text_working'] = 'monstrous_email_btn_saving';
        
        return array(
			'heading' => lang('monstrous_email_leads'),
			'body' => ee('View')->make('monstrous_email:index')->render($this->data),
			'sidebar' => $this->sidebar,
			'breadcrumb' 	=> array(
				$this->baseUrl->compile() => lang('monstrous_email'),
				$this->baseUrl.'/salesforce' => lang('monstrous_email_sf_settings')
			)
		);
    }
    
    private function _salesforce_tabs() {
        $salesforceList = $this->navSalesForce->addBasicList();
        $this->navSalesForceLeads = $salesforceList->addItem(lang('monstrous_email_leads'), $this->baseUrl.'/salesforce_leads');
        $this->navSalesForceContacts = $salesforceList->addItem(lang('monstrous_email_contacts'), $this->baseUrl.'/salesforce_contacts');
        $this->navSalesForceOpportunities = $salesforceList->addItem(lang('monstrous_email_opportunities'), $this->baseUrl.'/salesforce_opportunities');
        $this->navSalesForceAccounts = $salesforceList->addItem(lang('monstrous_email_accounts'), $this->baseUrl.'/salesforce_accounts');
        $this->navSalesForceCampaigns = $salesforceList->addItem(lang('monstrous_email_campaigns'), $this->baseUrl.'/salesforce_campaigns');
        $this->navSalesForceOwners = $salesforceList->addItem(lang('monstrous_email_owners'), $this->baseUrl.'/salesforce_owners');
        $this->navSalesForceGroups = $salesforceList->addItem(lang('monstrous_email_groups'), $this->baseUrl.'/salesforce_groups');
    }
    
    public function update_settings()
    {
        $settings['monstrous_email_url'] = ee()->input->post('monstrous_email_url');
        $settings['monstrous_email_key'] = ee()->input->post('monstrous_email_key');
        
        // Put it Back
        ee()->db->set('settings', serialize($settings));
        ee()->db->where('module_name', 'Monstrous_email');
        $upd= ee()->db->update('exp_modules');
        
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('monstrous_email_updated'))
            ->addToBody(sprintf(lang('monstrous_email_updated_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl);
    }
    
    public function update_sf_settings()
    {
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('monstrous_email_updated'))
            ->addToBody(sprintf(lang('monstrous_email_sf_updated_desc')))
            ->defer();

        ee()->functions->redirect($this->baseUrl.'/salesforce');
    }

}

?>