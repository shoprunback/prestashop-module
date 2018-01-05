<div class="display">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            <div class="top row">
                <div class="title col-sm-6">
                    <h1>{l s="order.my_orders" mod='shoprunback'}</h1>

                    <a class="srb-button post-all" data-type="{$itemType}">{l s="title.sync_all" mod='shoprunback'}</a>
                    <a class="srb-button post-new" data-type="{$itemType}">{l s="title.sync_new" mod='shoprunback'}</a>
                </div>

                <div class="external-link col-sm-2">
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
                        <th>{l s="order.returned" mod='shoprunback'}</th>
                        <th>{l s="item.last_sync" mod='shoprunback'}</th>
                        <th>{l s="item.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$items key=id item=item}
                        <tr data-id="{$item->getDBId()}">
                            <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><b>{$item->getName()}</b></a></td>
                            <td>{$item->customer->first_name} {$item->customer->last_name}</td>
                            <td>{$item->ordered_at}</td>
                            <td>
                                {if $item->id_srb_return}
                                    <a href="{$shoprunbackURL}/shipbacks/{$item->id_srb_return}" target="blank">
                                        <span class="badge badge-default {$item->state}">{$item->state|capitalize}</span>
                                    </a>
                                {/if}
                            </td>
                            <td>{$item->last_sent}</td>
                            <td>
                                {if $item->delivery}
                                    {l s="item.delivered" mod='shoprunback'}
                                {elseif ! $item->last_sent}
                                    <a class="sync-item srb-button" data-type="{$itemType}">
                                        {l s="item.sync" mod='shoprunback'}
                                    </a>
                                {/if}
                            </td>
                            <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
