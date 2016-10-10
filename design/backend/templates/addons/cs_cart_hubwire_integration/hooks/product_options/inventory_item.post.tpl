<div class="control-group">
	<label class="control-label" for="inventory_{$i.combination_hash}_sku_id">{__("hubwire_sku_id")}</label>
	<div class="controls">
		<input type="number" 
			id="inventory_{$i.combination_hash}_sku_id" 
			name="inventory[{$i.combination_hash}][hubwire_sku_id]" 
			value="{$i.hubwire_sku_id}" 
			readonly />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="inventory_{$i.combination_hash}_sku">{__("hubwire_sku")}</label>
	<div class="controls">
		<input type="text" 
			id="inventory_{$i.combination_hash}_sku" 
			name="inventory[{$i.combination_hash}][hubwire_sku]" 
			value="{$i.hubwire_sku}" 
			readonly />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="inventory_{$i.combination_hash}_list_price">{__("hubwire_sku_list_price")}</label>
	<div class="controls">
		<input type="number" 
			min="0"
			step="0.01"
			id="inventory_{$i.combination_hash}_list_price" 
			name="inventory[{$i.combination_hash}][list_price]" 
			value="{$i.list_price}" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="inventory_{$i.combination_hash}_price">{__("hubwire_sku_price")}</label>
	<div class="controls">
		<input type="number" 
			min="0"
			step="0.01"
			id="inventory_{$i.combination_hash}_price" 
			name="inventory[{$i.combination_hash}][price]" 
			value="{$i.price}" />
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="inventory_{$i.combination_hash}_weight">{__("hubwire_sku_weight")}</label>
	<div class="controls">
		<input type="number" 
			min="0"
			step="0.001"
			id="inventory_{$i.combination_hash}_weight" 
			name="inventory[{$i.combination_hash}][weight]" 
			value="{$i.weight}" />
	</div>
</div>