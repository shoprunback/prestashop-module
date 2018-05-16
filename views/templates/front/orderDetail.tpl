<section class="box" id="order-detail">
    <h1>
        {if !$shipback}
            {l s='front.order.detail.title' mod='shoprunback'}<br>

            {l s='front.order.detail.content' mod='shoprunback'} <span id="create-return">{l s='front.order.detail.link' mod='shoprunback'}</span>
        {else}
            <a href="{$shipback->public_url}" target="_blank">{l s='front.order.detail.returned' mod='shoprunback'}</a>
        {/if}
    </h1>
</section>

{if !$shipback}
    <div id="modal">
        <div class="content">
            <h2>{l s='front.order.detail.modal.title' mod='shoprunback'}</h2>

            <div>
                <a class="cancel form-control-submit btn btn-primary">{l s='front.order.detail.modal.cancel' mod='shoprunback'}</a>
                <a class="external-link form-control-submit btn btn-primary" target="_blank">{l s='front.order.detail.modal.validate' mod='shoprunback'}</a>
            </div>
        </div>
    </div>
{/if}

<!-- Have to put the JS here to work on PS 1.6 since the order details appears dynamically, after the JS' load -->
{if !$shipback}
    <script type="text/javascript">
        var createReturnLink = '{$createReturnLink nofilter}';
        var frontJsPath = '{$frontJsPath}';
    </script>

    {if $isVersionGreaterThan1_7}
        <script type="text/javascript" src="{$frontJsPath}orderDetail-1.7.js"></script>
    {else}
        <script type="text/javascript" src="{$frontJsPath}orderDetail.js"></script>
    {/if}
{/if}