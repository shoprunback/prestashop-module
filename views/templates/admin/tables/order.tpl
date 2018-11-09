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
                    <h1>{l s="order.my_orders" mod='shoprunback'}</h1>
                </div>

                <div class="external-link col-sm-4">
                    <a href="{$shoprunbackURL}/orders" target="_blank" class="srb-button external-link">{l s="title.link_to_orders" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="order.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="element-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            {l s="order.orders" mod='shoprunback'}
                            {$search = $searchOrderNumber}
                            {$searchName = $searchOrderNumberName}
                            {include file="./search.tpl"}
                        </th>
                        <th>
                            {l s="order.customer" mod='shoprunback'}
                            {$search = $searchCustomer}
                            {$searchName = $searchCustomerName}
                            {include file="./search.tpl"}
                        </th>
                        <th>{l s="order.command_date" mod='shoprunback'}</th>
                        <th>{l s="order.return_status" mod='shoprunback'}</th>
                        <th>{l s="element.last_sync" mod='shoprunback'}</th>
                        <th>{l s="element.sync" mod='shoprunback'}</th>
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
                                {if $element->delivery && $element->last_sent_at}
                                    {l s="element.delivered" mod='shoprunback'}
                                {elseif $srbtoken != ''}
                                    <a class="sync-element srb-button" data-type="{$elementType}">
                                        {l s="element.sync" mod='shoprunback'}
                                    </a>
                                {/if}
                            </td>
                            <td>
                                {if $element->id_item_srb}
                                    <a href="{$externalLink}{$element->id_item_srb}" target="_blank" title="{l s='See in ShopRunBack' mod='shoprunback'}"><i class="fa fa-external-link-square fa-lg" aria-hidden="true"></i></a>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>
