<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Endpoint extends CI_Controller 
{
	public function __construct()
	{
		parent::__construct();
		
		if (!$this->tank_auth->is_logged_in()) 
		{	
			if ($this->input->is_ajax_request()) 
			{
				redirect('/auth/ajax_logged_out_response');
			} 
			else 
			{
				redirect('/auth/login');
			}
		}

		$this->utility->restricted_access();
		$this->load->model('account/account_model', 'account');

		$this->load->library('endpoint_api');
	}

    function _remap($method, $args)
    { 
       if (method_exists($this, $method))
       {
           $this->$method($args);
       }
       else
       {
            $this->index($method, $args);
       }
    }

	public function index($method, $args = array())
	{	
		$asset = client_redis_info_by_code();

		$nav['client_name']         = $asset['client'];
		$nav['client_code']         = $asset['code']; 

		$data['client_code']        = $asset['code'];
		$data['sub_navigation']     = $this->load->view('customer-management/navigation', $nav, TRUE);	

		$data['crowdstrike_info']   = $this->crowdstrike_api->redis_info($asset['seed_name']);

		$data['crowdstrike_status'] = '';

		if (!is_null($data['crowdstrike_info']))
		{
			$data['crowdstrike_status'] = $data['crowdstrike_info']['tested'] ? 'Configured' : 'Configuring';	
		}

		$data['endpoint_active']	= NULL;

		if (isset($asset['endpoint_enabled']) && intval($asset['endpoint_enabled']) === 1)
		{
			if ($asset['endpoint_provider'] === 'crowdstrike')
			{
				$data['endpoint_active'] = 'CrowdStrike';
			}
		}

		# Page Views
		$this->load->view('assets/header');	
		$this->load->view('customer-management/endpoint/start', $data);	
		$this->load->view('assets/footer');	

	}

}
    