{include file="common/subheader.tpl" title=__("hubwire_integration_title") target="#hubwire_integration"}
<div id="hubwire_integration" class="collapse in">
	<div class="control-group">
		<label class="control-label" for="hubwire_client_id">{__("hubwire_client_id")}</label>
		<div class="controls">
			<input type="text" id="hubwire_client_id" name="company_data[hubwire_client_id]" value="{$company_data.hubwire_client_id|default:""}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_client_secret">{__("hubwire_client_secret")}</label>
		<div class="controls">
			<input type="text" id="hubwire_client_secret" name="company_data[hubwire_client_secret]" value="{$company_data.hubwire_client_secret|default:""}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="default_products_category">{__("default_products_category")}</label>
		<div class="controls">
			<input type="number" id="default_products_category" name="company_data[default_products_category]" value="{$company_data.default_products_category|default:0}" class="input-large" size="10" />
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_product_id">{__("hubwire_vendor_create_webhooks")}</label>
		<div class="controls">
			<a data-ca-dispatch="dispatch[companies.hubwire_create_webhooks]" 
				data-ca-target-form="company_update_form" 
				class="btn btn-primary cm-submit btn-primary cm-ajax"> 
				{__("btn_create")}
			</a>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label" for="hubwire_product_id">{__("hubwire_vendor_resync_products")}</label>
		<div class="controls">
			<a data-ca-dispatch="dispatch[companies.hubwire_resync_products]" 
				data-ca-target-form="company_update_form" 
				class="btn btn-primary cm-submit btn-primary cm-ajax">
				{__("btn_resync")}
			</a>
		</div>
	</div>	
</div>