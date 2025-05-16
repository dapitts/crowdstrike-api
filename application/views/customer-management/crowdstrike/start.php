<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">		
		<ol class="breadcrumb">
			<li><a href="/customer-management">Customer Management</a></li>
			<li><a href="/customer-management/customer/<?php echo $client_code; ?>">Customer Overview</a></li>
			<li><a href="/customer-management/endpoint/<?php echo $client_code; ?>">Endpoint Integration</a></li>
			<li class="active">CrowdStrike Endpoint API</li>
		</ol>	
	</div>
</div>

<?php echo $sub_navigation; ?>

<div class="row">
	<div class="col-md-8">

		<div class="panel panel-light">
			<div class="panel-heading">
				<div class="clearfix">
					<div class="pull-left">
						<h3>CrowdStrike</h3>
						<h4>Endpoint Api integration</h4>
					</div>
					<div class="pull-right">
						<a href="/customer-management/endpoint/<?php echo $client_code; ?>" class="btn btn-default btn-sm">Return To Provider Selection</a>
					</div>
				</div>
			</div>
			<div class="panel-body">
				<?php if (is_null($crowdstrike_info)) { ?>
					<div class="row">
						<div class="col-md-8">
							<p class="lead">This customer does not have CrowdStrike Endpoint enabled. Click <em>Start CrowdStrike Endpoint Integration</em> to enable.</p>
							<a href="/customer-management/endpoint/crowdstrike/<?php echo $action; ?>/<?php echo $client_code; ?>" class="btn btn-success">Start CrowdStrike Endpoint Integration</a>
						</div>
					</div>
				<?php } else { ?>
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="col-md-12">
									<table class="table valign-middle table-25-75">
										<tr>
											<td><label class="control-label">API Host</label></td>
											<td><?php echo $crowdstrike_info['api_host']; ?></td>
										</tr>
										<tr>
											<td><label class="control-label">Client ID</label></td>
											<td><?php echo $crowdstrike_info['client_id']; ?></td>
										</tr>
										<tr>
											<td><label class="control-label">Client Secret</label></td>
											<td><?php echo $crowdstrike_info['client_secret']; ?></td>
										</tr>
									</table>	
								</div>
							</div>	
							<div class="row">
								<div class="col-md-12">
									<hr>
									<div class="clearfix">
										<div class="pull-left">
											<button type="button" class="btn btn-info" id="show-api-test">Test Integration</button>	
										</div>
										<div class="pull-right">
											<a href="/customer-management/endpoint/crowdstrike/<?php echo $action; ?>/<?php echo $client_code; ?>" class="btn btn-success"><?php echo ucfirst($action); ?></a>
										</div>
									</div>									
								</div>
							</div>			
						</div>
					</div>
					
					
					<div class="row">	
						<div class="col-md-12">
							<div class="row" id="api-test-display">
								<div class="col-md-12">
									<div class="well api-test-window">
										<p>First, we will try and retrieve a list of machines. Once we successfully retrieve a machine, you will then attempt to quarantine and release from quarantine, one of the machines. After a successful release, the integration testing will be complete and you will be able to activate the integration.</p>
										<p>
											<button class="btn btn-xs btn-success" id="run-api-test" data-api="crowdstrike" data-client="<?php echo $client_code; ?>" data-loading-text="Please Wait...">Run Test</button>
										</p>
										<p class="running-test">Running test...</p>
										<p class="test-success"><span>Successfully retrieved machine(s)</span></p>
										<p class="test-failed"><span>Error: Test failed</span></p>
										<div id="api-test-results">
											<pre class="api-json-results" class="wrap-json"></pre>
											<div class="machine-list">
												<p>
													Machines: <strong class="machine-count">0</strong> of <strong class="machines-total">0</strong>
												</p>
												<table class="table valign-middle gray-header">
													<thead>
														<tr>
															<th>Device Name</th>
															<th>OS Platform</th>
															<th>Last Internal IP Address</th>
															<th>MAC Address</th>
															<th>Status</th>
															<th class="text-right">&nbsp;</th>
														</tr>
													</thead>
													<tbody></tbody>
												</table>
												
												<div class="row">	
													<div class="col-md-12 text-center">
														<button id="load-more-machines" type="button" class="btn btn-success btn-sm" data-api="crowdstrike" data-client="<?php echo $client_code; ?>" data-loading-text="Please Wait..." data-offset="50" data-total-machines="">Load More</button>
													</div>
												</div>
												
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					
				<?php } ?>
  			</div>
		</div>
		
	</div>
	
	<div class="col-md-4">
		
		<div class="panel panel-light api-activation-panel <?php echo (!is_null($crowdstrike_info) && $show_activation) ? 'show':''; ?>">
			<div class="panel-heading">
				<div class="clearfix">
					<div class="pull-left">
						<h3>API Activation</h3>
						<h4>check list</h4>
					</div>
					<div class="pull-right"></div>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">	
					<div class="col-md-12">

						<ul class="nav api-check-list">
							<li>
								<span class="fa-stack">  
									<i class="fas fa-check fa-stack-1x icon-api-tested <?php echo ($api_tested) ? 'show' : ''; ?>"></i>
									<i class="far fa-square fa-stack-2x"></i>
								</span><span class="check-list-label"> : API Has Been Tested</span>
							</li>
							<li>
								<span class="fa-stack">  
									<i class="fas fa-check fa-stack-1x icon-api-requested <?php echo ($request_was_sent) ? 'show' : ''; ?>"></i>
									<i class="far fa-square fa-stack-2x"></i>
								</span><span class="check-list-label"> : Customer Sent Request</span>
							</li>
							<li>
								<span class="fa-stack">  
									<i class="fas fa-check fa-stack-1x icon-api-enabled <?php echo ($api_enabled) ? 'show' : ''; ?>"></i>
									<i class="far fa-square fa-stack-2x"></i>
								</span><span class="check-list-label"> : API Is Enabled</span>
							</li>
						</ul>

					</div>
				</div>	
				
				<div class="row">	
					<div class="col-md-10 col-md-offset-1">
						<?php if ($api_enabled) { ?>
							<a href="/customer-management/endpoint/crowdstrike/disable/<?php echo $client_code; ?>" data-toggle="modal" data-target="#decision_modal" class="btn btn-danger btn-lg btn-block">Disable API</a>
						<?php } else { ?>
							<a href="/customer-management/endpoint/crowdstrike/activate/<?php echo $client_code; ?>" data-toggle="modal" data-target="#decision_modal" class="btn btn-warning btn-lg btn-block">Activate API</a>
						<?php } ?>
					</div>
				</div>
				
				<!--
				<div class="row">	
					<div class="col-md-10 col-md-offset-1">		
						<div class="alert alert-info margin-top-10">	
							<p class="activate-instructions <?php //echo ($request_was_sent) ? 'hide' : 'show'; ?>">Clicking this button will notify the SOC and everyone on your Alert Distribution email list of your request.</p>
							<p class="activate-await <?php //echo ($request_was_sent) ? 'show' : 'hide'; ?>">Awaiting SOC Activation</p>
						</div>
					</div>
				</div>
				-->
			</div>
		</div>

		<div class="row">	
			<div class="col-md-12">
				<div class="alert alert-info">
					<h4>REQUIRED PERMISSIONS</h4>
					<p class="margin-bottom-10">In order to use this integration, the follow permissions are required:</p>
					<ul class="list-unstyled">
						<li>&bull; Hosts API service collection, with a scope of READ and WRITE</li>
					</ul>
				</div>
			</div>
		</div>
		
		<div id="endpoint-load-warning" class="alert alert-danger">
			<span class="load-warning">Please remain on page until test is complete, this may take a few minutes.</span>
		</div>
		
	</div>
	
</div>

<script type="text/html" id="row-template">
	<tr>		
		<td>{{name}}</td>
		<td>{{platform}}</td>
		<td>{{last_ip}}</td>
		<td>{{mac_address}}</td>
		<td>{{status}}</td>
		<td class="text-right">
			<button type="button" class="btn btn-xs btn-default" data-endpoint-provider="crowdstrike" data-endpoint-action="quarantine" data-machine="{{id}}" data-client="{{client_code}}" data-loading-text="Attempting to Quarantine...">Quarantine</button>
		</td>
	</tr>
</script>
