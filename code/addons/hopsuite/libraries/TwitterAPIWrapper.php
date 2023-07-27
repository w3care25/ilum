<?php

/**
 *
 */
class TwitterAPIWrapper {

    private $host = 'api.twitter.com';
    private $api_version = "1.1";
    private $oauth_access_token;
    private $oauth_access_token_secret;
    private $consumer_key;
    private $consumer_secret;

    public function __construct(array $settings)
    {
        if (!in_array('curl', get_loaded_extensions()))
        {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }

        if (!isset($settings['oauth_access_token'])
            || !isset($settings['oauth_access_token_secret'])
            || !isset($settings['consumer_key'])
            || !isset($settings['consumer_secret']))
        {
            throw new Exception('Make sure you are passing in the correct parameters');
        }

        $this->oauth_access_token = $settings['oauth_access_token'];
        $this->oauth_access_token_secret = $settings['oauth_access_token_secret'];
        $this->consumer_key = $settings['consumer_key'];
        $this->consumer_secret = $settings['consumer_secret'];
    }

    /**
     * Send a GET request to Twitter API
     * @param  string $end_point End point of Twitter API ("/statuses/user_timeline.json" ...)
     * @param  array $query     Parameters for the query
     * @return string            The response as JSON
     */
    public function get($end_point, $query)
    {
        $method = 'GET';
        $oauth = array(
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_token' => $this->oauth_access_token,
            'oauth_nonce' => (string)mt_rand(), // a stronger nonce is recommended
            'oauth_timestamp' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0'
        );

        $oauth = array_map("rawurlencode", $oauth); // must be encoded before sorting
        $query = array_map("rawurlencode", $query);

        $arr = array_merge($oauth, $query); // combine the values THEN sort

        asort($arr); // secondary sort (value)
        ksort($arr); // primary sort (key)

        // http_build_query automatically encodes, but our parameters
        // are already encoded, and must be by this point, so we undo
        // the encoding step
        $querystring = urldecode(http_build_query($arr, '', '&'));

        $url = "https://$this->host/$this->api_version/$end_point";

        // mash everything together for the text to hash
        $base_string = $method."&".rawurlencode($url)."&".rawurlencode($querystring);

        // same with the key
        $key = rawurlencode($this->consumer_secret)."&".rawurlencode($this->oauth_access_token_secret);

        // generate the hash
        $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));

        // this time we're using a normal GET query, and we're only encoding the query params
        // (without the oauth params)
        // (we need to decode the query because it has been encoded before)
        $query = array_map("urldecode", $query);
        $url .= "?".http_build_query($query);

        $oauth['oauth_signature'] = $signature; // don't want to abandon all that work!
        ksort($oauth); // probably not necessary, but twitter's demo does it

        // also not necessary, but twitter's demo does this too
        if (!function_exists("add_quotes"))
        {
            function add_quotes($str) { return '"'.$str.'"'; }
        }

        $oauth = array_map("add_quotes", $oauth);

        // this is the full value of the Authorization line
        $auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));

        // if you're doing post, you need to skip the GET building above
        // and instead supply query parameters to CURLOPT_POSTFIELDS
        $options = array( CURLOPT_HTTPHEADER => array("Authorization: $auth", "Content-Type: application/x-www-form-urlencoded"),
                          //CURLOPT_POSTFIELDS => $postfields,
                          CURLOPT_HEADER => false,
                          CURLOPT_URL => $url,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_SSL_VERIFYPEER => false);

        // do our business
        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);

        return $json;
    }
}
