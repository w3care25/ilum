<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class wcloner_ext {

    public $version         = '1.1.0';

    function __construct($settings='')
    {
        $this->settings = $settings;
    }


    public function activate_extension()
    {


        $data = array(
            'class'     => __CLASS__,
            'method'    => 'add_wcloner_js',
            'hook'      => 'cp_js_end',
            'settings'  => serialize($this->settings),
            'version'   => $this->version,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $data);

        $data = array(
            'class'     => __CLASS__,
            'method'    => 'add_wcloner_css',
            'hook'      => 'cp_css_end',
            'settings'  => serialize($this->settings),
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        ee()->db->insert('extensions', $data);

    }

    function add_wcloner_js()
    {
        $js = ee()->extensions->last_call ?: '';


        $query = ee()->db->select("action_id")
            ->from('actions')
            ->where('class', 'wcloner')
            ->get();

        if ($query->num_rows() > 0)
        {
            $row = $query->row();
            $js .= "$(function() { var wClonerACT = {$row->action_id};";
            $script = file_get_contents(PATH_THIRD . 'wcloner/js/clone.js');
            $js .= $script;
        }

        $modal_vars = array(
          'name' => 'modal-confirm-clone',
          'contents' => '<h1>Confirm cloning</h1>',
            'hidden'  => array(
                'content_id' => ''
              ),

        );
        $modal_html = ee('View')->make('ee:_shared/modal')->render($modal_vars);
        $js .= "$('body').append('" . preg_replace( "/\r|\n/", "", $modal_html) . "');";
        $js .= '});';
        return $js;
    }

    function add_wcloner_css()
    {
        $css = ee()->extensions->last_call ?: '';
        $css .= '.toolbar li.clone a:before { content: "\f24d"; } .resultsTable tbody > tr li.clone a:before { content: "\f24d"; } .wcloner-toolbar { vertical-align:middle; margin-right:5px; }';

        return $css;
    }



    function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }
    }

}