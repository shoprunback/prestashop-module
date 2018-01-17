<div class="display row">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            {if count($noBrand) > 0}
                <div class="row">
                    <p class="col-sm-12 alert alert-warning">
                        {l s="product.no_brand" mod='shoprunback'}

                        {foreach from=$noBrand key=id item=reference}
                            <b> {$reference} </b>
                        {/foreach}
                    </p>
                </div>
            {/if}

            <div class="top row">
                <div class="title col-sm-6">
                    <h1>{l s="product.my_products" mod='shoprunback'}</h1>

                    <a class="srb-button post-all" data-type="{$itemType}">{l s="title.sync_all" mod='shoprunback'}</a>
                    <a class="srb-button post-new" data-type="{$itemType}">{l s="title.sync_new" mod='shoprunback'}</a>
                </div>

                <div class="external-link col-sm-2">
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
                        <th>{l s="item.last_sync" mod='shoprunback'}</th>
                        <th>{l s="item.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$items key=id item=item}
                        <tr data-id="{$item->getDBId()}" class="{if in_array($item->getReference(), $noBrand)}warning{/if}">
                            <td class="left"><a href="{$externalLink}{$item->getReference()}" target="_blank"><b>{$item->getName()}</b></a></td>
                            <td><b>{$item->getReference()}</b></td>
                            <td>
                                {if $item->last_sent_at}
                                    {$item->last_sent_at}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>{if $srbtoken != ''}<a class="sync-item srb-button" data-type="{$itemType}">{l s="item.sync" mod='shoprunback'}{/if}</a></td>
                            <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
