<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal-dialog" role="document">
	<div class="modal-content">
		
		<?php echo form_open('/customer-management/endpoint/crowdstrike/do-disable/'.$client_code, array('id'=>'general_action','role'=>'form', 'class'=>'element-action-form')); ?>
		
		<div class="modal-body">
			<h5>Disable CrowdStrike API for:</h5>
			
			<h3><?php echo $client_title; ?></h3>
			
			<div class="row">
				<div class="col-md-10 col-md-offset-1">
					<div class="form-group margin-top-10">
						<select name="requesting_user" id="requesting_user" class="selectpicker form-control no-error bs-center">
							<option value="">- - Select Requesting Contact - -</option>
							<?php foreach($authorized_to_modify as $row): ?>
								<option value="<?php echo $row->code; ?>"><?php echo $row->first_name.' '.$row->last_name; ?></option>
							<?php endforeach; ?>
						</select>
					</div>					
				</div>
			</div>
			<div class="row margin-top-10">
				<div class="col-md-10 col-md-offset-1">		
					<input name="api-terms-of-agreement" id="api-terms-of-agreement" value="1" type="checkbox" />
					<label for="api-terms-of-agreement">Confirm Disabling API</label>
				</div>
			</div>
			
		</div>
		
		<div class="modal-footer">
			<button type="button" class="btn btn-lg btn-default" data-dismiss="modal">Cancel</button>
			<button class="btn btn-lg btn-success form-submit-button" form="general_action" type="submit" data-loading-text="Disabling API...">Disable</button>		
		</div>
		
		<?php echo form_close(); ?>
		
	</div>	
</div>