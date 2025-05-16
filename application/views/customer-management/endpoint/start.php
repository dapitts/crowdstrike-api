<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">		
		<ol class="breadcrumb">
			<li><a href="/customer-management">Customer Management</a></li>
			<li><a href="/customer-management/customer/<?php echo $client_code; ?>">Customer Overview</a></li>
			<li><a href="/customer-management/endpoint/<?php echo $client_code; ?>">Endpoint Integration</a></li>
			<li class="active">Provider</li>
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
						<h3>Endpoint</h3>
						<h4>integration</h4>
					</div>
					<div class="pull-right">
					</div>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-12">
						
						<p class="lead">
							<?php 
								if (is_null($endpoint_active))
								{
									echo 'This customer does not have an Endpoint API enabled.';
								}
								else
								{
									echo 'This customer currently has the '.$endpoint_active.' Endpoint API enabled.';
								}
							?>
						</p>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<h4 style="margin-bottom:12px;">Endpoint Providers</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<table class="table gray-header valign-middle">
							<thead>
								<tr>
									<th>Provider</th>
									<th>Status</th>
									<th class="text-center">Active</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>CrowdStrike</td>
									<td><?php echo $crowdstrike_status; ?></td>
									<td class="text-center">
										<?php echo ($endpoint_active === 'CrowdStrike') ? '<i class="glyphicon glyphicon-ok"></i>' : '<i class="glyphicon glyphicon-remove"></i>'; ?>
									</td>
									<td class="text-right"><a class="btn btn-default btn-xs" href="/customer-management/endpoint/crowdstrike/<?php echo $client_code; ?>">Details</a></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
  			</div>
		</div>
	</div>
</div>