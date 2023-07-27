<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'hop_pushee/lib/vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient as HttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use OneSignal\Config;
use OneSignal\Devices;
use OneSignal\OneSignal;

class Hop_pushee_api_helper
{

	private $config;
	private $client;
	private $api;

	public function __construct($application_id, $rest_api_key)
	{
		ee()->load->library('logger');

		$this->config = new Config();
		$this->config->setApplicationId($application_id);
		$this->config->setApplicationAuthKey($rest_api_key);
		// Not sure what this one is for...
		// $this->config->setUserAuthKey('your_auth_key');

		$guzzle = new GuzzleClient([
			// ..config
		]);

		$this->client = new HttpClient(new GuzzleAdapter($guzzle), new GuzzleMessageFactory());
		$this->api = new OneSignal($this->config, $this->client);
	}

	/**
	 * Retrieve notifications from OneSignal
	 *
	 * @param integer $limit
	 * @param integer $offset
	 * @return mixed NULL if the request failed, array if it went ok
	 */
	public function get_notifications($limit = 25, $offset = 0)
	{
		try
		{
			$result = $this->api->notifications->getAll($limit, $offset);
		}
		catch (Exception $e)
		{
			// var_dump ($e);
			ee()->logger->developer('Hop PushEE: Error when retrieving Notifications from OneSignal API:<br/>'.$e->getMessage());
			return null;
		}

		return $result;
	}

	public function get_notification($notification_id)
	{
		try
		{
			$result = $this->api->notifications->getOne($notification_id);
		}
		catch (Exception $e)
		{
			// var_dump ($e);
			ee()->logger->developer('Hop PushEE: Error when retrieving single Notification from OneSignal API:<br/>'.$e->getMessage());
			return null;
		}

		return $result;
	}

	/**
	 * Push a new notification to subscribers
	 *
	 * @param array $data The notification data
	 * @return mixed
	 */
	public function push_notification(array $data = array())
	{
		try
		{
			$result = $this->api->notifications->add($data);
		}
		catch (Exception $e)
		{
			// var_dump ($e);
			ee()->logger->developer('Hop PushEE: Error when pushing a Notification via OneSignal API:<br/>'.$e->getMessage());
			return null;
		}

		return $result;
	}
}