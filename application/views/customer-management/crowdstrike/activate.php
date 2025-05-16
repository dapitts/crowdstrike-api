<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal-dialog" role="document">
	<div class="modal-content">
		
		<div id="modal-alert-container">
			<div class="alert alert-danger">
				<h4 class="alert-heading">Error</h4>
				<div class="modal-alert-content"></div>
			</div>
		</div>
		
		<?php echo form_open('/customer-management/endpoint/crowdstrike/do-activate/'.$client_code, array('id'=>'general_action', 'role'=>'form', 'class'=>'element-action-form')); ?>
		
		<div class="modal-body">
			<h5>Enable CrowdStrike API for:</h5>

			<h3><?php echo $client_title; ?></h3>
			
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="form-group margin-top-10">
						<select name="requesting_user" id="requesting_user" class="selectpicker form-control no-error bs-center">
							<option value="">- - Select Requesting Contact - -</option>
							<?php foreach ($authorized_to_modify as $row): ?>
								<option value="<?php echo $row->code; ?>" <?php echo ($row->code === $request_user) ? 'selected="selected"':''; ?> ><?php echo $row->first_name.' '.$row->last_name; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		
		<div class="modal-footer">
			<button type="button" class="btn btn-lg btn-default" data-dismiss="modal">Cancel</button>
			<button class="btn btn-lg btn-success form-submit-button" form="general_action" type="submit" data-loading-text="Activating API...">Activate</button>		
		</div>
		<input type="hidden" name="api-terms-of-agreement" value="1">
		<?php echo form_close(); ?>
		
	</div>	
</div>