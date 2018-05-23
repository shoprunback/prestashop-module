<div id="srb-content">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    {include file='./header.tpl'}

    {if $message}
        <div class="alert alert-{$messageType}">
            {if $message == AdminShoprunbackController::SUCCESS_CONFIG}
                {l s="success.config" mod='shoprunback'}
            {elseif $message == AdminShoprunbackController::ERROR_NO_TOKEN}
                {l s="error.no_token" mod='shoprunback'}
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
