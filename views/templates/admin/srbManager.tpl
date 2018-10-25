<script type="text/javascript">
    var asyncCall = '{$asyncCall}';
    var elementType = '{$elementType}';
    var token = '{$srbtoken}';
    var loadingMessage = "{l s='manager.loading' mod='shoprunback'}";
    var admin_module_ajax_url_shoprunback = '{$admin_module_ajax_url_shoprunback}';
</script>

<div id="srb-loader" class="hidden">
    <div class="row text-center">
        <div class="col-sm-12">
            <i class="fa fa-spinner fa-spin fa-5x" aria-hidden="true"></i>
        </div>
        <div class="col-sm-12 srb-information-text">
            <br>
            {l s='manager.loading' mod='shoprunback'}
        </div>
    </div>


</div>

<div id="srb-manager">
    {if $srbtoken == ''}
        <div class="row">
            <div class="col-md-10 col-md-offset-1 alert alert-warning">
                {l s="error.need_token" mod='shoprunback'}
            </div>
        </div>
    {/if}

    {if count($elements) > 0 || $searchCondition}
        {include file="./tables/$elementType.tpl"}

        <div class="pagination">
            <ul>
                {if $pages > 1}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage=1{$searchCondition}"><span>{l s="pagination.first" mod='shoprunback'}</span></a></li>
                {/if}

                {if $currentPage > 1}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage - 1}{$searchCondition}"><span>«</span></a></li>
                {/if}

                {if $currentPage - 2 > 0}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage - 2}{$searchCondition}"><span>{$currentPage - 2}</span></a></li>
                {/if}
                {if $currentPage - 1 > 0}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage - 1}{$searchCondition}"><span>{$currentPage - 1}</span></a></li>
                {/if}
                <li class="active"><span>{$currentPage}</span></li>
                {if $currentPage + 1 <= $pages}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage + 1}{$searchCondition}"><span>{$currentPage + 1}</span></a></li>
                {/if}
                {if $currentPage + 2 <= $pages}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage + 2}{$searchCondition}"><span>{$currentPage + 2}</span></a></li>
                {/if}

                {if $currentPage < $pages}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$currentPage + 1}{$searchCondition}"><span>»</span></a></li>
                {/if}

                {if $pages > 1}
                    <li><a href="{$srbManager}&elementType={$elementType}&currentPage={$pages}{$searchCondition}"><span>{l s="pagination.last" mod='shoprunback'}</span></a></li>
                {/if}
            </ul>
        </div>
    {else}
        <div class="row">
            <div class="text-center col-md-12">
                <h2>
                    {if $elementType == "return"}
                        {l s='return.no_return' mod='shoprunback'}
                    {/if}

                    {if $elementType == "brand"}
                        {l s='brand.no_brand' mod='shoprunback'}
                    {/if}

                    {if $elementType == "product"}
                        {l s='product.no_product' mod='shoprunback'}
                    {/if}

                    {if $elementType == "order"}
                        {l s='order.no_order' mod='shoprunback'}
                    {/if}
                </h2>
            </div>
        </div>
    {/if}
</div>
