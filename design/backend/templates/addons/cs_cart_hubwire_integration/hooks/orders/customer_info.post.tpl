{include file="common/subheader.tpl" title=__("hubwire_integration_title") target="#hubwire_integration"}
<div id="hubwire_integration" class="collapse in">
	<div class="control-group">
		<label class="control-label" for="hubwire_order_id">{__("hubwire_order_id")}</label>
		<div class="controls">
		<input type="number" id="hubwire_order_id" name="update_order[hubwire_order_id]" value="{$order_info.hubwire_order_id|default:"0"}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_tracking_no">{__("hubwire_tracking_no")}</label>
		<div class="controls">
		<input type="text" id="hubwire_tracking_no" name="update_order[hubwire_tracking_no]" value="{$order_info.hubwire_tracking_no|default:""}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_order_id">{__("hubwire_resync_order")}</label>
		<div class="controls">
			<a data-ca-dispatch="dispatch[orders.update_hubwire]" 
				data-ca-target-form="order_info_form" 
				class="btn btn-primary cm-submit btn-primary cm-ajax"> 
				{__("btn_resync")}
			</a>
		</div>
	</div>
	
</div>