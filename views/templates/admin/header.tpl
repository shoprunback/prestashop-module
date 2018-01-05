<div id="srb-header">
    <div class="header row">
        <div class="col-md-4">
            <a href="{$shoprunbackURL}" target="_blank"><img src="../modules/shoprunback/assets/img/logo.png" /></a>
        </div>

        <div class="col-md-5 col-md-offset-2 link-to-srb text-right">
            {l s="header.go_to" mod='shoprunback'} <a href="{$shoprunbackURL}" class="srb-button" target="_blank">{l s="header.my_dashboard" mod='shoprunback'}</a>
        </div>
    </div>

    <div class="navigation">
        <ul class="nav nav-pills nav-justified">
            <li class="{if $itemType eq 'returns'}active{/if}">
                <a href="{$srbManager}&itemType=returns">{l s='return.my_returns' mod='shoprunback'}</a>
            </li>
            <li class="{if $itemType eq 'brands'}active{/if}">
                <a href="{$srbManager}&itemType=brands">{l s='brand.my_brands' mod='shoprunback'}</a>
            </li>
            <li class="{if $itemType eq 'products'}active{/if}">
                <a href="{$srbManager}&itemType=products">{l s="product.my_products" mod='shoprunback'}</a>
            </li>
            <li class="{if $itemType eq 'orders'}active{/if}">
                <a href="{$srbManager}&itemType=orders">{l s="order.my_orders" mod='shoprunback'}</a>
            </li>
            <li class="{if $itemType eq 'config'}active{/if}">
                <a href="{$srbManager}&itemType=config">{l s="config" mod='shoprunback'}</a>
            </li>
        </ul>
    </div>
</div>
