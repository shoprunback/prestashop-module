<script type="text/javascript">
    var asyncCall = '{$asyncCall}';
    var shoprunbackAPIURL = '{$shoprunbackAPIURL}';
    var itemType = '{$itemType}';
    var token = '{$token}';
</script>

<div id="srb-manager">
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

    <h2>
        <a href="http://localhost/prestashop/admin939kmvcyx/index.php?controller=AdminShoprunback&token=23c28709c9abfa423fa123ad2363b2e8&action=test">
            <button>TEST</button>
        </a>
    </h2>
</div>
