{**
 * 2007-2018 ShopRunBack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ShopRunBack
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the ShopRunBack module to newer
 * versions in the future.
 *
 * @author ShopRunBack <contact@shoprunback.com>
 * @copyright 2007-2018 ShopRunBack
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of ShopRunBack
 *}
 
 <section class="box" id="order-detail">
    <h1 id="request">
        {if !$shipback}
            {l s='front.order.detail.title' mod='shoprunback'}<br>

            {l s='front.order.detail.content' mod='shoprunback'} <span onclick="toggleModal()" id="create-return">{l s='front.order.detail.link' mod='shoprunback'}</span>
        {else}
            <a href="{$shipback->public_url}" target="_blank">{l s='front.order.detail.returned' mod='shoprunback'}</a>
        {/if}
    </h1>

    <div id="modal">
        <div class="content">
            <h2>{l s='front.order.detail.modal.title' mod='shoprunback'}</h2>

            <div>
                <span onclick="toggleModal()" class="cancel form-control-submit btn btn-primary">
                    {l s='front.order.detail.modal.cancel' mod='shoprunback'}
                </span>

                <span
                    onclick="createShopRunBackReturn(
                        '{$createReturnLink nofilter}',
                        &quot;<h1>{l s='front.order.detail.loading' mod='shoprunback'}</h1>&quot;
                    )"
                    class="external-link form-control-submit btn btn-primary"
                >
                    {l s='front.order.detail.modal.validate' mod='shoprunback'}
                </span>
            </div>
        </div>
    </div>
</section>