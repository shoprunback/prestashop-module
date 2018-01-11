<script type="text/javascript">
    var asyncCall = '{$asyncCall}';
    var shoprunbackAPIURL = '{$shoprunbackAPIURL}';
    var itemType = '{$itemType}';
    var token = '{$token}';
</script>

<div id="srb-manager">
    {if count($items) > 0}
        {include file="./tables/$itemType.tpl"}
    {else}
        <div class="no-item text-center">
            <h2>
                {if $itemType == "returns"}
                    {l s='return.no_return' mod='shoprunback'}
                {/if}

                {if $itemType == "brands"}
                    {l s='brand.no_brand' mod='shoprunback'}
                {/if}

                {if $itemType == "products"}
                    {l s='product.no_product' mod='shoprunback'}
                {/if}

                {if $itemType == "orders"}
                    {l s='order.no_order' mod='shoprunback'}
                {/if}
            </h2>
        </div>
    {/if}

    <div class="pagination">
        <ul>
            {if $pages > 1}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage=1"><button>{l s="pagination.first" mod='shoprunback'}</button></a></li>
            {/if}

            {if $currentPage > 1}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage - 1}"><button>{l s="pagination.previous" mod='shoprunback'}</button></a></li>
            {/if}

            {if $currentPage - 2 > 0}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage - 2}"><button>{$currentPage - 2}</button></a></li>
            {/if}
            {if $currentPage - 1 > 0}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage - 1}"><button>{$currentPage - 1}</button></a></li>
            {/if}
            <li class="active"><button>{$currentPage}</button></li>
            {if $currentPage + 1 <= $pages}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage + 1}"><button>{$currentPage + 1}</button></a></li>
            {/if}
            {if $currentPage + 2 <= $pages}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage + 2}"><button>{$currentPage + 2}</button></a></li>
            {/if}

            {if $currentPage < $pages}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage + 1}"><button>{l s="pagination.next" mod='shoprunback'}</button></a></li>
            {/if}

            {if $pages > 1}
                <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$pages}"><button>{l s="pagination.last" mod='shoprunback'}</button></a></li>
            {/if}
        </ul>
    </div>
</div>
