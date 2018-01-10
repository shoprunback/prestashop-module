<div id="srb-content">
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    {include file='./header.tpl'}

    {if $message}
        <div class="alert alert-{$messageType}">
            {if $message == 'success.token'}
                {l s="sucess.token" mod='shoprunback'}
            {elseif $message == 'error.no_token'}
                {l s="error.no_token" mod='shoprunback'}
            {/if}
        </div>
    {/if}

    {include file="./$template.tpl"}
</div>
