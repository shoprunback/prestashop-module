<section class="box" id="order-detail">
    <h1>
        {if 1==1}
            {l s='front.order.detail.title' mod='shoprunback'}<br>

            {l s='front.order.detail.content' mod='shoprunback'} <a href="{$createReturnLink}&orderId={$orderId}" id="return-order">{l s='front.order.detail.link' mod='shoprunback'}</a>
        {else}
            {l s='front.order.detail.returned' mod='shoprunback'}
        {/if}
    </h1>
</section>
