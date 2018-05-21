<section class="box" id="order-detail">
    <h1>
        {if !$shipback}
            {l s='front.order.detail.title' mod='shoprunback'}<br>

            {l s='front.order.detail.content' mod='shoprunback'} <span onclick="toggleModal()" id="create-return">{l s='front.order.detail.link' mod='shoprunback'}</span>
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
                <span onclick="toggleModal()" class="cancel form-control-submit btn btn-primary">
                    {l s='front.order.detail.modal.cancel' mod='shoprunback'}
                </span>

                <span onclick="createShopRunBackReturn('{$createReturnLink nofilter}')" class="external-link form-control-submit btn btn-primary">
                    {l s='front.order.detail.modal.validate' mod='shoprunback'}
                </span>
            </div>
        </div>
    </div>
{/if}