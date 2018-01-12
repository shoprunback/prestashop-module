<div class="display">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            <div class="top row">
                <div class="title col-sm-6">
                    <h1>{l s="brand.my_brands" mod='shoprunback'}</h1>

                    <a class="srb-button post-all" data-type="{$itemType}">{l s="title.sync_all" mod='shoprunback'}</a>
                    <a class="srb-button post-new" data-type="{$itemType}">{l s="title.sync_new" mod='shoprunback'}</a>
                </div>

                <div class="external-link col-sm-2">
                    <a href="{$shoprunbackURL}/brand" target="_blank" class="srb-button external-link">{l s="title.link_to_brands" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="brand.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="item-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s="brand.brands" mod='shoprunback'}</th>
                        <th>{l s="item.last_sync" mod='shoprunback'}</th>
                        <th>{l s="item.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$items key=id item=item}
                        <tr data-id="{$item->getDBId()}">
                            <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><b>{$item->getName()}</b></a></td>
                            <td>{$item->last_sent_at}</td>
                            <td><a class="sync-item srb-button" data-type="{$itemType}">{l s="item.sync" mod='shoprunback'}</a></td>
                            <td><a href="{$externalLink}{$item->getReference()}" target="_blank"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
