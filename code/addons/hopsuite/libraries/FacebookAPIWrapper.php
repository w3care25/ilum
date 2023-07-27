<?php

class FacebookAPIWrapper
{
    private $host = 'graph.facebook.com';
    private $api_version = "v2.10";
    private $access_token = "";

    public function __construct(array $settings)
    {
        if (!in_array('curl', get_loaded_extensions()))
        {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }

        if (!isset($settings['access_token']))
        {
            throw new Exception('Make sure you are passing in the correct parameters');
        }

        $this->access_token = $settings['access_token'];
    }

    public function get($endpoint, $query)
    {
        $query["access_token"] = $this->access_token;
        $post_data = http_build_query($query);

        $url = "https://".$this->host."/".$this->api_version."/".$endpoint.'?'.$post_data;
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data if needed
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch,CURLOPT_POST, count($post_params));
        //curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $result;
    }
}
