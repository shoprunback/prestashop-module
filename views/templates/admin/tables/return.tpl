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
 
 <div class="display row">
    <div class="col-md-10 col-md-offset-1">
        <div class="banner">
            <div class="top row">
                <div class="title col-sm-8">
                    <h1>{l s="return.my_returns" mod='shoprunback'}</h1>
                </div>

                <div class="external-link col-sm-4">
                    <a href="{$shoprunbackURL}/shipbacks" target="_blank" class="srb-button external-link">{l s="title.link_to_returns" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="return.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="element-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s="return.returns" mod='shoprunback'}</th>
                        <th>
                            {l s="return.customer" mod='shoprunback'}
                            {$search = $searchCustomer}
                            {$searchName = $searchCustomerName}
                            {include file="./search.tpl"}
                        </th>
                        <th>
                            {l s='order.id' mod='shoprunback'}
                            {$search = $searchOrderReference}
                            {$searchName = $searchOrderReferenceName}
                            {include file="./search.tpl"}
                        </th>
                        <th>{l s='return.state' mod='shoprunback'}</th>
                        <th>{l s='return.created_at' mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {if count($elements) > 0}
                        {foreach from=$elements key=id item=element}
                            <tr data-id="{$element->getReference()}">
                                <td><a href="{$externalLink}{$element->getReference()}" target="_blank"><b>{$element->getName()}</b></a></td>
                                <td>{$element->order->customer->first_name} {$element->order->customer->last_name}</td>
                                <td>{$element->order_id}</td>
                                <td>
                                    <a href="{$externalLink}{$element->getReference()}" target="blank">
                                        <span class="badge badge-default {$element->state}">{$element->state|capitalize}</span>
                                    </a>
                                </td>
                                <td>{$element->created_at}</td>
                                <td><a href="{$externalLink}{$element->getReference()}" target="_blank" title="{l s='See in ShopRunBack' mod='shoprunback'}"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a></td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="6">{l s="search.no_result" mod='shoprunback'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>
