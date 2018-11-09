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
 
 <div id="srb-content">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    {include file='./header.tpl'}

    {if isset($messageType)}
        <div class="col-sm-12">
            {if $messageType == 'success'}
                <div class="alert alert-success">
                    {l s="Form saved with success" mod='shoprunback'}
                </div>
            {else}
                <div class="alert alert-danger">
                    {l s="There was an error saving your configuration, please check your token" mod='shoprunback'}
                </div>
            {/if}
        </div>

    {/if}

    {if isset($notifications)}
        <script>
            var putNotificationUrl = '{$putNotificationUrl}';
        </script>

        <div class="alert alert-danger notifications">
            <h4>{l s="notifications.title" mod='shoprunback'}</h4>

            <ul>
                {foreach from=$notifications key=id item=notification}
                    <li data-id="{$notification->id_srb_notification}">
                        [{$notification->created_at}]
                        <span class="label label-info mark-as-read">
                            {l s="notification.read" mod='shoprunback'}
                            <span class="icon-check"></span>
                        </span>

                        <br>

                        {if $notification->object_type}
                            {ucfirst($notification->object_type)}:
                        {else}
                            [Unknown type]
                        {/if}

                        {if $notification->object_id}
                            {$notification->object_id}
                        {else}
                            [Unknown ID]
                        {/if}

                        <br/>

                        {$notification->message}
                    </li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {include file="./$template.tpl"}
</div>
