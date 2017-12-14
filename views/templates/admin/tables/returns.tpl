<div>
    <div class="banner">
        <h1>
            <span>{l s="return.my_returns" mod="shoprunback"}</span>
        </h1>

        <p>{$conditionsToSend}</p>
    </div>

    <div id="item-list">
        <table class="table">
            <thead>
                <tr>
                    <th>{$itemType|capitalize}</th>
                    <th>{l s='return.state' mod='shoprunback'}</th>
                    <th>{l s='order.id' mod='shoprunback'}</th>
                    <th>{l s='return.created_at' mod='shoprunback'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$items key=id item=item}
                    <tr data-id="{$item->getReference()}">
                        <td><a href=""><b>{$item->getName()}</b></a></td>
                        <td>{$item->state}</td>
                        <td>{$item->order_id}</td>
                        <td>{$item->created_at}</td>
                        <td><a href="{$item->getPublicUrl()}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
