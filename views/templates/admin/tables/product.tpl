<div class="display row">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            {if count($noBrand) > 0}
                <div class="row">
                    <p class="col-sm-12 alert alert-warning">
                        {l s="product.no_brand" mod='shoprunback'}
                    </p>
                </div>
            {/if}

            <div class="top row">
                <div class="title col-sm-8">
                    <h1>{l s="product.my_products" mod='shoprunback'}</h1>

                    {if $srbtoken != ''}
                        <a class="srb-button post-all" data-type="{$elementType}">{l s="title.sync_all" mod='shoprunback'}</a>
                        <a class="srb-button post-new" data-type="{$elementType}">{l s="title.sync_new" mod='shoprunback'}</a>
                    {/if}
                </div>

                <div class="external-link col-sm-4">
                    <a href="{$shoprunbackURL}/products" target="_blank" class="srb-button">{l s="title.link_to_products" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="product.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="item-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s="product.products" mod='shoprunback'}</th>
                        <th>{l s="product.reference" mod='shoprunback'}</th>
                        <th>{l s="product.brand" mod='shoprunback'}</th>
                        <th>{l s="item.last_sync" mod='shoprunback'}</th>
                        <th>{l s="item.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$elements key=id item=element}
                        <tr data-id="{$element->getDBId()}">
                            <td class="left" data-link="{$link->getAdminLink('AdminProducts', true, ['id_product' => $element->getDBId()])}"><b>{$element->getName()}</b></td>
                            <td><b>{$element->getReference()}</b></td>
                            <td>{if $element->brand != ''}{$element->brand->getName()}{/if}</td>
                            <td>
                                {if $element->last_sent_at}
                                    {$element->last_sent_at}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>{if $srbtoken != ''}<a class="sync-item srb-button" data-type="{$elementType}">{l s="item.sync" mod='shoprunback'}{/if}</a></td>
                            <td>
                                {if $element->id_item_srb}
                                    <a href="{$externalLink}{$item->id_item_srb}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
