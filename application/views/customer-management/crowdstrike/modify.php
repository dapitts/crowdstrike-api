<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">		
		<ol class="breadcrumb">
			<li><a href="/customer-management">Customer Management</a></li>
			<li><a href="/customer-management/customer/<?php echo $client_code; ?>">Customer Overview</a></li>
			<li><a href="/customer-management/endpoint/<?php echo $client_code; ?>">Endpoint Integration</a></li>
			<li><a href="/customer-management/endpoint/crowdstrike/<?php echo $client_code; ?>">CrowdStrike Endpoint API</a></li>
			<li class="active">Modify</li>
		</ol>	
	</div>
</div>
<div class="row">
	<div class="col-md-8">		
		<div class="panel panel-light">
			<div class="panel-heading">
				<div class="clearfix">
					<div class="pull-left">
						<h3>CrowdStrike API Integration</h3>
						<h4>Modify</h4>
					</div>
					<div class="pull-right">
						<a href="/customer-management/endpoint/crowdstrike/<?php echo $client_code; ?>" type="button" class="btn btn-default btn-sm">Cancel &amp; Return</a>
					</div>
				</div>
			</div>			
			<?php echo form_open($this->uri->uri_string(), array('autocomplete' => 'off', 'aria-autocomplete' => 'off')); ?>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12">							
							<div class="form-group<?php echo form_error('api_host') ? ' has-error':''; ?>">
								<label class="control-label" for="api_host">API Host</label>
								<div class="input-group">
									<div class="input-group-addon">https://</div>
									<input type="text" class="form-control" id="api_host" name="api_host" placeholder="api.crowdstrike.com" value="<?php echo set_value('api_host', $crowdstrike_info['api_host']); ?>">
								</div>
							</div>
							<div class="form-group<?php echo form_error('client_id') ? ' has-error':''; ?>">
								<label class="control-label" for="client_id">Client ID</label>
								<input type="text" class="form-control" id="client_id" name="client_id" placeholder="Enter Client ID" value="<?php echo set_value('client_id', $crowdstrike_info['client_id']); ?>">
							</div>
							<div class="form-group<?php echo form_error('client_secret') ? ' has-error':''; ?>">
								<label class="control-label" for="client_secret">Client Secret</label>
								<input type="text" class="form-control" id="client_secret" name="client_secret" placeholder="Enter Client Secret" value="<?php echo set_value('client_secret', $crowdstrike_info['client_secret']); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 text-right">
							<button type="submit" class="btn btn-success" data-loading-text="Updating...">Update</button>
						</div>						
					</div>
				</div>	  			
			<?php echo form_close(); ?>			
		</div>		
	</div>
</div>