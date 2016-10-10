{include file="common/subheader.tpl" title=__("hubwire_integration_title") target="#hubwire_integration"}
<div id="hubwire_integration" class="collapse in">
	<div class="control-group">
		<label class="control-label" for="hubwire_product_id">{__("hubwire_product_id")}</label>
		<div class="controls">
		<input type="number" id="hubwire_product_id" name="product_data[hubwire_product_id]" value="{$product_data.hubwire_product_id|default:"0"}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_product_id">{__("hubwire_resync_product")}</label>
		<div class="controls">
			<a data-ca-dispatch="dispatch[products.update_hubwire]" 
				data-ca-target-form="product_update_form" 
				class="btn btn-primary cm-submit btn-primary cm-ajax"> 
				{__("btn_resync")}
			</a>
		</div>
	</div>
	
</div>