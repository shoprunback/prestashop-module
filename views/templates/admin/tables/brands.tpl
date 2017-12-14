<div>
    <div class="banner">
        <h1>
            <span>{l s="brand.my_brands" mod="shoprunback"}</span>
            <a class="srb-button post-all" data-type="{$itemType}">{l s="title.sync_all" mod="shoprunback"}</a>
            <a class="srb-button post-new" data-type="{$itemType}">{l s="title.sync_new" mod="shoprunback"}</a>
        </h1>

        <p>{$conditionsToSend}</p>
    </div>

    <div id="item-list">
        <table class="table">
            <thead>
                <tr>
                    <th>{$itemType|capitalize}</th>
                    <th>{l s="brand.reference" mod="shoprunback"}</th>
                    <th>{l s="item.sync_all" mod="shoprunback"}</th>
                    <th>{l s="item.sync" mod="shoprunback"}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$items key=id item=item}
                    <tr data-id="{$item->getReference()}">
                        <td><a href="{$link->getManufacturerLink($item->ps)}"><b>{$item->getName()}</b></a></td>
                        <td><b>{$item->getReference()}</b></td>
                        <td>{$item->last_sent}</td>
                        <td><a class="sync-item srb-button" data-type="{$itemType}">{l s="item.sync" mod="shoprunback"}</a></td>
                        <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
