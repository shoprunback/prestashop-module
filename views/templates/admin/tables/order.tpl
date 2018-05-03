<div class="display row">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            <div class="top row">
                <div class="title col-sm-8">
                    <h1>{l s="order.my_orders" mod='shoprunback'}</h1>

                    {if $srbtoken != ''}
                        <a class="srb-button post-all" data-type="{$elementType}">{l s="title.sync_all" mod='shoprunback'}</a>
                        <a class="srb-button post-new" data-type="{$elementType}">{l s="title.sync_new" mod='shoprunback'}</a>
                    {/if}
                </div>

                <div class="external-link col-sm-4">
                    <a href="{$shoprunbackURL}/orders" target="_blank" class="srb-button external-link">{l s="title.link_to_orders" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="order.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="item-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s="order.orders" mod='shoprunback'}</th>
                        <th>{l s="order.customer" mod='shoprunback'}</th>
                        <th>{l s="order.command_date" mod='shoprunback'}</th>
                        <th>{l s="order.return_status" mod='shoprunback'}</th>
                        <th>{l s="item.last_sync" mod='shoprunback'}</th>
                        <th>{l s="item.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$elements key=id item=element}
                        <tr data-id="{$element->getDBId()}">
                            <td data-link="{if $element->id_item_srb}{$externalLink}{$element->id_item_srb}{/if}"><b>{$element->getName()}</b></td>
                            <td>{$element->customer->first_name} {$element->customer->last_name}</td>
                            <td>{$element->ordered_at}</td>
                            <td>
                                {if $element->id_srb_shipback}
                                    <a href="{$shoprunbackURL}/shipbacks/{$element->id_srb_shipback}" target="blank">
                                        <span class="badge badge-default {$element->state}">{$element->state|capitalize}</span>
                                    </a>
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>
                                {if $element->last_sent_at}
                                    {$element->last_sent_at}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>
                                {if $element->delivery}
                                    {l s="item.delivered" mod='shoprunback'}
                                {elseif ! $element->last_sent_at && $srbtoken != ''}
                                    <a class="sync-item srb-button" data-type="{$elementType}">
                                        {l s="item.sync" mod='shoprunback'}
                                    </a>
                                {/if}
                            </td>
                            <td>
                                {if $element->id_item_srb}
                                    <a href="{$externalLink}{$element->id_item_srb}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
