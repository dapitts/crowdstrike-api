<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Crowdstrike_api 
{
	private $ch;
	private $client_redis_key;
	private $redis_host;
	private $redis_port;
	private $redis_timeout;  
	private $redis_password;
	private $max_rows;
	private $oauth_access_token;

	function __construct()
	{
		$CI =& get_instance();
		
		$this->client_redis_key = 'crowdstrike_';		
		$this->redis_host       = $CI->config->item('redis_host');
		$this->redis_port       = $CI->config->item('redis_port');
		$this->redis_password   = $CI->config->item('redis_password');
		$this->redis_timeout    = $CI->config->item('redis_timeout');
		$this->max_rows         = $CI->config->item('crowdstrike_max_rows') ?? 2000;  // Max: 5000
	}

	public function redis_info($client, $field = NULL, $action = 'GET', $data = NULL)
	{
		$client_info    = client_redis_info($client);
		$client_key     = $this->client_redis_key.$client;

		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);
		
		if ($action === 'SET')
		{
			$check = $redis->hMSet($client_key, $data);
		}
		else
		{
			if (is_null($field))
			{
				$check = $redis->hGetAll($client_key);
			}
			else
			{
				$check = $redis->hGet($client_key, $field);
			}
		}     
			
		$redis->close();
		
		if (empty($check))
		{
			$check = NULL;
		}
		
		return $check;		
	}

	public function create_client_redis_key($client, $data = NULL)
	{
		$client_info    = client_redis_info($client);
		$client_key     = $this->client_redis_key.$client;
		
		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);

		$check = $redis->hMSet($client_key, [
			'api_host'      => $data['api_host'],
			'client_id'     => $data['client_id'],
			'client_secret' => $data['client_secret'],
			'endpointer'    => 'device_id',
			'tested'        => '0',
			'request_sent'  => '0',
			'terms_agreed'  => '0'
		]);
						
		$redis->close();
		
		return $check;		
	}

	public function quarantine_machine($client, $machine_id, $data = NULL)
	{
		$device_id  = base64_decode_url($machine_id);
		$response   = $this->quarantine($client, $device_id, TRUE);

		if ($response['success'])
		{
			// *** WARNING *** Do not call get_device_details() immediately after a successful 
			// call to quarantine(). Due to a timing issue, the CrowdStrike sensor is left in 
			// the 'containment_pending' status.

			return array(
				'success'   => TRUE,
				'response'  => array(
					'status'    => 'Pending',
					'id'        => $response['response']['resources'][0]['id']
				)
			);
		}
		else
		{
			if (!empty($response['response']['errors']))
			{
				$msg = $this->get_error_message($response['response']['errors']);
			}
			else
			{
				$msg = 'N/A';
			}

			return array(
				'success'   => FALSE,
				'response'  => $response['response'],
				'message'   => $msg
			);
		}
	}

	public function machine_status($client, $vars, $data = NULL)
	{
		$device_id  = base64_decode_url($vars['machine_id']);
		$response   = $this->get_device_details($client, $device_id);

		if ($response['success'])
		{
			$machine = $response['response']['resources'][0];

			switch ($machine['status'])
			{
				case 'normal':
				case 'containment_pending':
					$status = 'Pending';
					break;
				case 'contained':
					$status = 'Succeeded';
					break;
			}

			return array(
				'success'   => TRUE,
				'response'  => array(
					'status'    => $status,
					'id'        => $machine['device_id']
				)
			);
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}
	}

	public function pull_machine_id($event_json)
	{
		$event_obj = json_decode($event_json);

		if (isset($event_obj->payload))
		{
			$cef_log = base64_decode($event_obj->payload);

			$header_delim   = '|';
			$headers        = explode($header_delim, $cef_log);
			$headers_len    = count($headers);
			$extension      = $headers[$headers_len - 1];

			if (($pos = strpos($extension, 'externalId')) !== FALSE)
			{
				// externalId=99f1873b8422447ab427b2e6b00e67ae
				return base64_encode_url(substr($extension, $pos + 11, 32));
			}
		}

		return NULL;
	}

	public function get_endpoint_list_search($client, $search_params)
	{
		$response = $this->query_devices_by_filter($client, $search_params);

		if ($response['success'])
		{
			if ($response['response']['meta']['pagination']['total'])
			{
				$response2 = $this->get_device_details($client, $response['response']['resources']);

				if ($response2['success'])
				{
					$devices        = [];
					$device_count   = count($response2['response']['resources']);

					foreach ($response2['response']['resources'] as $device)
					{
						$devices[] = array(
							'client_code'   => $search_params['client'],
							'id'            => base64_encode_url($device['device_id']),
							'name'          => $device['hostname'],
							'last_ip'       => $device['local_ip'] ?? 'N/A',
							'platform'      => $device['platform_name'],
							'mac_address'   => $device['mac_address'] ?? 'N/A',
							'provider'      => 'crowdstrike'
						);
					}

					$return_array = array(
						'success'       => TRUE,
						'response'      => $response2['response'],
						'machine_count' => $device_count,
						'machine_data'  => $devices,
						'machine_total' => $device_count
					);
				}
				else
				{
					$return_array = array(
						'success'   => FALSE,
						'response'  => $response2['response']
					);
				}
			}
			else
			{
				$return_array = array(
					'success'       => TRUE,
					'response'      => $response['response'],
					'machine_count' => 0,
					'machine_data'  => [],
					'machine_total' => 0
				);
			}
		}
		else
		{
			$return_array = array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		return $return_array;
	}

	public function get_endpoint_information($client, $client_code, $machine_id)
	{
		$device_id  = base64_decode_url($machine_id);
		$response   = $this->get_device_details($client, $device_id);

		if ($response['success'])
		{
			$machine = $response['response']['resources'][0];

			$device = array(
				'name'          => $machine['hostname'],
				'last_ip'       => $machine['local_ip'] ?? 'N/A',
				'platform'      => $machine['platform_name'],
				'id'            => $machine['device_id'],
				'mac'           => $machine['mac_address'] ?? 'N/A',
				'last_user'     => false,
				'connected'     => $machine['status'] === 'normal',
				'url_id'        => base64_encode_url($machine['device_id']),
				'provider'      => 'crowdstrike',
				'client_code'   => $client_code
			);

			$return_array = array(
				'success'   => TRUE,
				'json'      => $machine,
				'details'   => $device
			);
		}
		else
		{
			$return_array = array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		return $return_array;
	}

	public function query_devices_by_filter($client, $search_params = NULL, $limit = 20, $offset = 0)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/devices/queries/devices/v1';
		$response           = $this->get_oauth_access_token($client);

		if (!$response['success'])
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		$header_fields = array(
			'Accept: application/json',
			'Authorization: Bearer '.$response['access_token']
		);

		$query_params = array();

		if (!is_null($search_params))
		{
			switch ($search_params['type'])
			{
				case 'device_id':
					$query_params['filter'] = "device_id:'".$search_params['term']."'";
					$query_params['limit']  = $limit;
					break;
				case 'os':
					// Windows, Mac, Linux
					$query_params['filter'] = "platform_name:'".ucfirst(strtolower($search_params['term']))."'";
					$query_params['limit']  = $this->max_rows;
					break;
				case 'status':
					// normal, containment_pending, contained, lift_containment_pending
					$query_params['filter'] = "status:'".strtolower($search_params['term'])."'";
					$query_params['limit']  = $this->max_rows;
					break;
				case 'computer_name':
					$query_params['filter'] = "hostname:'".$search_params['term']."'";
					$query_params['limit']  = $this->max_rows;
					break;
				case 'ip_address':
					// local_ip or external_ip
					$query_params['filter'] = "local_ip:'".$search_params['term']."',external_ip:'".$search_params['term']."'";
					$query_params['limit']  = $this->max_rows;
					break;
				case 'mac_address':
					$query_params['filter'] = "mac_address:'".$search_params['term']."'";
					$query_params['limit']  = $this->max_rows;
					break;
				case 'list':
				default:
					$query_params['limit']  = $this->max_rows;
					$query_params['offset'] = $offset;
			}
		}
		else
		{
			$query_params['limit']  = $this->max_rows;
			$query_params['offset'] = $offset;
		}

		$response2 = $this->call_api('GET', $url.'?'.http_build_query($query_params), $header_fields);

		if ($response2['result'] !== FALSE)
		{
			if ($response2['http_code'] === 200)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response2['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response2['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response2['errno'],
							'message'   => $response2['error']
						]
					)
				)
			);
		}
	}

	public function get_device_details($client, $ids)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/devices/entities/devices/v2';
		$response           = $this->get_oauth_access_token($client);

		if (!$response['success'])
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		$header_fields = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer '.$response['access_token']
		);

		$post_fields = new stdClass();

		if (is_array($ids))
		{
			$post_fields->ids   = $ids;
		}
		else
		{
			$post_fields->ids[] = $ids;
		}

		$response2 = $this->call_api('POST', $url, $header_fields, json_encode($post_fields));

		if ($response2['result'] !== FALSE)
		{
			if ($response2['http_code'] === 200)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response2['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response2['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response2['errno'],
							'message'   => $response2['error']
						]
					)
				)
			);
		}
	}

	public function query_device_login_history($client, $device_id)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/devices/combined/devices/login-history/v1';
		$response           = $this->get_oauth_access_token($client);

		if (!$response['success'])
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		$header_fields = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer '.$response['access_token']
		);

		$post_fields = new stdClass();
		$post_fields->ids[] = $device_id;

		$response2 = $this->call_api('POST', $url, $header_fields, json_encode($post_fields));

		if ($response2['result'] !== FALSE)
		{
			if ($response2['http_code'] === 200)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response2['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response2['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response2['errno'],
							'message'   => $response2['error']
						]
					)
				)
			);
		}
	}

	public function get_online_state($client, $device_id)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/devices/entities/online-state/v1';
		$query_str          = http_build_query(['ids' => $device_id]);
		$response           = $this->get_oauth_access_token($client);

		if (!$response['success'])
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		$header_fields = array(
			'Accept: application/json',
			'Authorization: Bearer '.$response['access_token']
		);

		$response2 = $this->call_api('GET', $url.'?'.$query_str, $header_fields);

		if ($response2['result'] !== FALSE)
		{
			if ($response2['http_code'] === 200)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response2['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response2['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response2['errno'],
							'message'   => $response2['error']
						]
					)
				)
			);
		}
	}

	public function quarantine($client, $device_id, $enable = FALSE)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/devices/entities/devices-actions/v2';
		$query_str          = http_build_query(['action_name' => $enable ? 'contain' : 'lift_containment']);
		$response           = $this->get_oauth_access_token($client);

		if (!$response['success'])
		{
			return array(
				'success'   => FALSE,
				'response'  => $response['response']
			);
		}

		$header_fields = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer '.$response['access_token']
		);

		$post_fields = new stdClass();
		$post_fields->ids[] = $device_id;

		$response2 = $this->call_api('POST', $url.'?'.$query_str, $header_fields, json_encode($post_fields));

		if ($response2['result'] !== FALSE)
		{
			if ($response2['http_code'] === 202)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response2['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response2['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response2['errno'],
							'message'   => $response2['error']
						]
					)
				)
			);
		}
	}

	private function call_api($method, $url, $header_fields, $post_fields = NULL)
	{
		$this->ch = curl_init();

		switch ($method)
		{
			case 'POST':
				curl_setopt($this->ch, CURLOPT_POST, true);

				if (isset($post_fields))
				{
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
				}

				break;
			case 'PUT':
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');

				if (isset($post_fields))
				{
					curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
				}

				break;
			case 'DELETE':
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		if (is_array($header_fields))
		{
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header_fields);
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		//curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);
		//curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);

		if (($response['result'] = curl_exec($this->ch)) !== FALSE)
		{
			// Make sure the size of the response is non-zero prior to json_decode()
			if (curl_getinfo($this->ch, CURLINFO_SIZE_DOWNLOAD_T))
			{
				$response['result'] = json_decode($response['result'], TRUE);
			}

			$response['http_code'] = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		}
		else
		{
			$response['errno'] 	= curl_errno($this->ch);
			$response['error'] 	= curl_error($this->ch);
		}

		curl_close($this->ch);

		return $response;
	}

	public function change_api_activation_status($client, $requested, $status)
	{
		$set_activation = $status ? 1 : 0;
		$set_provider   = $status ? 'crowdstrike' : '';
		$check          = FALSE;
		
		#set soc redis keys
		$redis = new Redis();
		$redis->connect($this->redis_host, $this->redis_port, $this->redis_timeout);
		$redis->auth($this->redis_password);

		$check = $redis->hMSet($client.'_information', [
			'endpoint_provider' => $set_provider,
			'endpoint_enabled'  => $set_activation
		]);
			
		$redis->close();

		# set client redis keys
		if ($check)
		{
			$status_data = array(
				'request_sent'  => $set_activation,
				'request_user'  => $requested,
				'terms_agreed'  => $set_activation
			);

			$config_data = array(
				'endpoint_provider' => $set_provider,
				'endpoint_enabled'  => $set_activation
			);
			
			if ($this->redis_info($client, NULL, 'SET', $status_data))
			{
				$this->reset_providers($client, 'crowdstrike');

				if ($this->client_config($client, NULL, 'SET', $config_data))
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	private function reset_providers($client, $active_provider)
	{
		$client_info    = client_redis_info($client);
		$providers      = endpoint_providers($active_provider);

		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);
			
		$reset_data = array(
			'request_sent'  => '0',
			'request_user'  => '0',
			'terms_agreed'  => '0'
		);
		
		foreach ($providers as $provider)
		{
			$provider_key = $provider.'_'.$client;

			if ($redis->exists($provider_key))
			{
				$redis->hMSet($provider_key, $reset_data);
			}
		}
				
		$redis->close();
	}
	
	public function client_config($client, $field = NULL, $action = 'GET', $data = NULL)
	{
		$client_info    = client_redis_info($client);
		$client_key     = $client.'_configurations';

		$redis = new Redis();
		$redis->connect($client_info['redis_host'], $client_info['redis_port'], $this->redis_timeout);
		$redis->auth($client_info['redis_password']);

		if ($action === 'SET')
		{
			$check = $redis->hMSet($client_key, $data);
		}
		else
		{
			if (is_null($field))
			{
				$check = $redis->hGetAll($client_key);
			}
			else
			{
				$check = $redis->hGet($client_key, $field);
			}
		}   
			
		$redis->close();
		
		if (empty($check))
		{
			$check = NULL;
		}
		
		return $check;		
	}

	private function get_oauth_access_token($client)
	{
		$crowdstrike_info = $this->redis_info($client);

		if (isset($crowdstrike_info['oauth_access_token']))
		{
			$this->oauth_access_token = json_decode($crowdstrike_info['oauth_access_token']);
		}

		if ($this->is_access_token_expired())
		{
			$url = 'https://'.$crowdstrike_info['api_host'].'/oauth2/token';

			$header_fields = array(
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: application/json'
			);

			$post_fields = array(
				'client_id'     => $crowdstrike_info['client_id'],
				'client_secret' => $crowdstrike_info['client_secret']
			);

			$response = $this->call_api('POST', $url, $header_fields, http_build_query($post_fields));

			if ($response['result'] !== FALSE)
			{
				if ($response['http_code'] === 201)
				{
					$this->oauth_access_token               = $response['result'];
					$this->oauth_access_token['created_at'] = time();
					$this->oauth_access_token               = json_encode($this->oauth_access_token);

					$this->redis_info($client, NULL, 'SET', array('oauth_access_token' => $this->oauth_access_token));

					$this->oauth_access_token = json_decode($this->oauth_access_token);
				}
				else
				{
					return array(
						'success'   => FALSE,
						'response'  => $response['result']
					);
				}
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => array(
						'errors'    => array(
							[
								'code'      => $response['errno'],
								'message'   => $response['error']
							]
						)
					)
				);
			}
		}

		return array(
			'success'       => TRUE,
			'access_token'  => $this->oauth_access_token->access_token
		);
	}

	public function revoke_oauth_access_token($client, $token)
	{
		$crowdstrike_info   = $this->redis_info($client);
		$url                = 'https://'.$crowdstrike_info['api_host'].'/oauth2/revoke';

		$header_fields = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
			'Authorization: Basic '.base64_encode($crowdstrike_info['client_id'].':'.$crowdstrike_info['client_secret'])
		);

		$post_fields = array(
			'token' => $token
		);

		$response = $this->call_api('POST', $url, $header_fields, http_build_query($post_fields));

		if ($response['result'] !== FALSE)
		{
			if ($response['http_code'] === 200)
			{
				return array(
					'success'   => TRUE,
					'response'  => $response['result']
				);
			}
			else
			{
				return array(
					'success'   => FALSE,
					'response'  => $response['result']
				);
			}
		}
		else
		{
			return array(
				'success'   => FALSE,
				'response'  => array(
					'errors'    => array(
						[
							'code'      => $response['errno'],
							'message'   => $response['error']
						]
					)
				)
			);
		}
	}

	private function is_access_token_expired()
	{
		if (!$this->oauth_access_token)
		{
			return TRUE;
		}

		// If the OAuth access token does not have an 'expires_in' property, then it's considered expired
		if (!isset($this->oauth_access_token->expires_in))
		{
			return TRUE;
		}

		$created_at = 0;

		if (isset($this->oauth_access_token->created_at))
		{
			$created_at = $this->oauth_access_token->created_at;
		}

		// If the OAuth access token is set to expire in the next 30 seconds.
		return ($created_at + $this->oauth_access_token->expires_in - 30) < time();
	}

	private function get_error_message($errors)
	{
		$message    = '';
		$errors_len = count($errors);

		foreach ($errors as $index => $error)
		{
			if ($errors_len === 1 || $errors_len > 1 && $index === $errors_len - 1)
			{
				if ($error['message'][-1] === '.')
				{
					$message    .= '['.$error['code'].'] '.$error['message'];
				}
				else
				{
					$message    .= '['.$error['code'].'] '.$error['message'].'.';
				}
			}
			else if ($errors_len > 1 && $index < $errors_len - 1)
			{
				if ($error['message'][-1] === '.')
				{
					$error['message'][-1]   = ',';
					$message                .= '['.$error['code'].'] '.$error['message'].' ';
				}
				else
				{
					$message    .= '['.$error['code'].'] '.$error['message'].', ';
				}
			}
		}

		return $message;
	}
}