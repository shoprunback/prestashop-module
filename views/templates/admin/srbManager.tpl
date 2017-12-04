<script type="text/javascript">
    var asyncCall = '{$asyncCall}';
    var shoprunbackAPIURL = '{$shoprunbackAPIURL}';
    var itemType = '{$itemType}';
    var token = '{$token}';
</script>

<div id="srb-content">
    <div class="header row">
        <div class="pull-left">
            <a href="{$shoprunbackURL}" target="_blank"><img src="../modules/shoprunback/assets/img/logo.png" /></a>
        </div>

        <div class="pull-right link-to-srb">
            <a href="{$shoprunbackURL}" class="srb-button" target="_blank">{l s='Go to My dashboard' mod='ShopRunBack'}</a>
        </div>
    </div>

    <div class="navigation">
        <ul class="nav nav-pills nav-justified">
            <li class="{if $itemType eq 'returns'}active{/if}">
                <a href="{$srbManager}&itemType=returns">{l s='Returns' mod='ShopRunBack'}</a>
            </li>
            <li class="{if $itemType eq 'products'}active{/if}">
                <a href="{$srbManager}&itemType=products">{l s='Products' mod='ShopRunBack'}</a>
            </li>
            <li class="{if $itemType eq 'brands'}active{/if}">
                <a href="{$srbManager}&itemType=brands">{l s='Brands' mod='ShopRunBack'}</a>
            </li>
            <li class="{if $itemType eq 'orders'}active{/if}">
                <a href="{$srbManager}&itemType=orders">{l s='Orders' mod='ShopRunBack'}</a>
            </li>
            <li class="{if $itemType eq 'configuration'}active{/if}">
                <a href="{$configurationURL}">{l s='Configuration' mod='ShopRunBack'}</a>
            </li>
        </ul>
    </div>

    <div class="data">
        <div class="banner">
            <h1>
                <span>My {$itemType|capitalize}</span>
                <a class="srb-button post-all" data-type="{$itemType}">Sync all</a>
                <a class="srb-button post-new" data-type="{$itemType}">Sync New only</a>
            </h1>

            <p>{$conditionsToSend}</p>
        </div>

        <div id="item-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>{$itemType|capitalize}</th>
                        <th>{l s='Reference ShopRunBack' mod='ShopRunBack'}</th>
                        <th>{l s='Last sync at' mod='ShopRunBack'}</th>
                        <th>{l s='Sync' mod='ShopRunBack'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$items key=id item=item}
                        <tr data-id="{$item.id}">
                            <td><a href="{$link->getProductLink($item)}"><b>{$item.name}</b></a></td>
                            <td><b>{$item.reference}</b></td>
                            <td>{$item.last_sent}</td>
                            <td><a class="sync-item srb-button" data-type="{$itemType}">SYNC</a></td>
                            <td><a href="http://localhost:3000/en/products/{$item.id}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <ul>
                {if $pages > 1}
                    <li><a href="{$srbManager}&itemType={$itemType}&currentPage=1"><button>First</button></a></li>
                {/if}

                {if $currentPage > 1}
                    <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage - 1}"><button>Previous</button></a></li>
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
                    <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$currentPage + 1}"><button>Next</button></a></li>
                {/if}

                {if $pages > 1}
                    <li><a href="{$srbManager}&itemType={$itemType}&currentPage={$pages}"><button>Last</button></a></li>
                {/if}
            </ul>
        </div>
    </div>

    <h2>
        <a href="http://localhost/prestashop/admin939kmvcyx/index.php?controller=AdminShoprunback&token=23c28709c9abfa423fa123ad2363b2e8&action=test">
            <button>TEST</button>
        </a>
    </h2>
</div>
