<?php

class InstagramAPIWrapper
{
	private $host = 'https://api.instagram.com';
	private $api_version = "v1";
	private $access_token = "";
	
	public function __construct($access_token)
	{
		$this->access_token = $access_token;
	}
	
	/**
	 * Retrieve data from a specific endoint API
	 * @param  string $endpoint [description]
	 * @param  array $query    [description]
	 * @return [type]           [description]
	 */
	public function get($endpoint, $query)
	{
		$query['access_token'] = $this->access_token;
		$query_data = http_build_query($query);

		$url = $this->host.'/'.$this->api_version.'/'.$endpoint.'?'.$query_data;
		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data if needed
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch,CURLOPT_POST, count($post_params));
		//curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);

		//execute query
		$result = curl_exec($ch);

		//close connection
		curl_close($ch);

		return $result;
	}
}