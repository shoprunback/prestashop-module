<script type="text/javascript">
    var asyncCall = '{$asyncCall}';
    var itemType = '{$itemType}';
    var token = '{$srbtoken}';
</script>

<div id="srb-manager">
    {if $srbtoken == ''}
        <div class="row">
            <div class="col-md-10 col-md-offset-1 alert alert-warning">
                {l s="error.need_token" mod='shoprunback'}
            </div>
        </div>
    {/if}

    {if count($items) > 0}
        {include file="./tables/$itemType.tpl"}

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
    {else}
        <div class="row">
            <div class="text-center col-md-12">
                <h2>
                    {if $itemType == "return"}
                        {l s='return.no_return' mod='shoprunback'}
                    {/if}

                    {if $itemType == "brand"}
                        {l s='brand.no_brand' mod='shoprunback'}
                    {/if}

                    {if $itemType == "product"}
                        {l s='product.no_product' mod='shoprunback'}
                    {/if}

                    {if $itemType == "order"}
                        {l s='order.no_order' mod='shoprunback'}
                    {/if}
                </h2>
            </div>
        </div>
    {/if}
</div>
