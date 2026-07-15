{*
 * views/admin/order_export_form.tpl
 * Smarty template for the order-export admin page.
 * Standalone template – loaded via absolute _PS_MODULE_DIR_ path.
 *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-download"></i>
        {l s='Export Orders to CSV' mod='mod_export_orders'}
    </div>

    <div class="panel-body">
        <form method="post" action="{$form_action|escape:'html':'UTF-8'}" class="form-horizontal">

            {* ── Status filter ───────────────────────────────────────── *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Order status' mod='mod_export_orders'}
                </label>
                <div class="col-lg-9">
                    <select name="id_order_state" class="form-control">
                        <option value="0">{l s='-- All statuses --' mod='mod_export_orders'}</option>
                        {foreach from=$order_statuses item=status}
                            <option value="{$status.id_order_state|intval}"
                                {if $selected_status == $status.id_order_state}selected="selected"{/if}>
                                {$status.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>

            {* ── Product filter ──────────────────────────────────────── *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Product' mod='mod_export_orders'}
                </label>
                <div class="col-lg-9">
                    <select name="id_product" class="form-control">
                        <option value="0">{l s='-- All products --' mod='mod_export_orders'}</option>
                        {foreach from=$products item=product}
                            <option value="{$product.id_product|intval}"
                                {if $selected_product == $product.id_product}selected="selected"{/if}>
                                {$product.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>

            {* ── Submit ──────────────────────────────────────────────── *}
            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    <button type="submit" name="export_csv" value="1"
                            class="btn btn-success">
                        <i class="icon-download"></i>
                        {l s='Download CSV' mod='mod_export_orders'}
                    </button>
                </div>
            </div>

        </form>
    </div>{* /.panel-body *}

    <div class="panel-footer">
        <small class="text-muted">
            {l s='The CSV file is encoded in UTF-8 and uses a semicolon (;) as the delimiter.' mod='mod_export_orders'}
        </small>
    </div>
</div>{* /.panel *}
