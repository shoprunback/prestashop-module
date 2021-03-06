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
            {if count($noBrand) > 0}
                <div class="row">
                    <p class="col-sm-12 alert alert-warning">
                        {l s="product.no_brand" mod='shoprunback'}
                    </p>
                </div>
            {/if}

            <div class="top row">
                <div class="title col-sm-8">
                    <h1>{l s="product.my_products" mod='shoprunback'}</h1>
                </div>

                <div class="external-link col-sm-4">
                    <a href="{$shoprunbackURL}/products" target="_blank" class="srb-button">{l s="title.link_to_products" mod='shoprunback'}</a>
                </div>
            </div>

            <div class="row">
                <p class="col-sm-12">{l s="product.description" mod='shoprunback'}</p>
            </div>
        </div>

        <div id="element-list">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            {l s="product.products" mod='shoprunback'}
                            {include file="./search.tpl"}
                        </th>
                        <th>{l s="product.reference" mod='shoprunback'}</th>
                        <th>{l s="product.brand" mod='shoprunback'}</th>
                        <th>{l s="element.last_sync" mod='shoprunback'}</th>
                        <th>{l s="element.sync" mod='shoprunback'}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$elements key=id item=element}
                        <tr data-id="{$element->getDBId()}">
                            <td class="left" data-link="{$link->getAdminLink('AdminProducts', true, ['id_product' => $element->getDBId()])}"><b>{$element->getName()}</b></td>
                            <td><b>{$element->getReference()}</b></td>
                            <td>{if $element->brand != ''}{$element->brand->getName()}{/if}</td>
                            <td>
                                {if $element->last_sent_at}
                                    {$element->last_sent_at}
                                {else}
                                    -
                                {/if}
                            </td>
                            <td>{if $srbtoken != ''}<a class="sync-element srb-button" data-type="{$elementType}">{l s="element.sync" mod='shoprunback'}{/if}</a></td>
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
