<section class="box" id="order-detail">
    <h1>
        {if !$shipback}
            {l s='front.order.detail.title' mod='shoprunback'}<br>

            {l s='front.order.detail.content' mod='shoprunback'} <a id="create-return">{l s='front.order.detail.link' mod='shoprunback'}</a>
        {else}
            <a href="{$shipback->public_url}" target="_blank">{l s='front.order.detail.returned' mod='shoprunback'}</a>
        {/if}
    </h1>
</section>

{if !$shipback}
    <div id="modale">
        <div class="modale">
            <h2>{l s='front.order.detail.modale.title' mod='shoprunback'}</h2>

            <div>
                <a class="cancel form-control-submit btn btn-primary" target="_blank">{l s='front.order.detail.modale.cancel' mod='shoprunback'}</a>
                <a class="external-link form-control-submit btn btn-primary" target="_blank">{l s='front.order.detail.modale.validate' mod='shoprunback'}</a>
            </div>
        </div>
    </div>
{/if}

<!-- Have to put the JS here to work on PS 1.6 since the order details appears dynamically, after the JS' load -->
{if !$shipback}
    <script type="text/javascript">
        var createReturnLink = '{$createReturnLink nofilter}';
        var redirectUrl = '';

        // On PS 1.7, since you load the page, you must wait for the DOM to load
        // But you must not wait for it for PS 1.6, since it is already loaded
        {if $isVersionGreaterThan1_7}
            document.addEventListener("DOMContentLoaded", function(event) {
        {/if}

        $('#create-return').on('click', function () {
            $.ajax({
                url: createReturnLink,
                method: 'POST',
                dataType: 'json',
                success: function (urls) {
                    if (typeof urls === 'object') {
                        // Success
                        redirectUrl = urls.redirectUrl;
                        $('.external-link').attr('href', urls.shipbackPublicUrl);
                        $('.cancel').attr('href', redirectUrl);
                        $('#modale').css('display', 'flex');
                    } else {
                        // Failure
                        window.location.href = urls;
                    }
                }
            });
        });

        $('.external-link').on('click', function () {
            window.location.href = redirectUrl;
        });

        {if $isVersionGreaterThan1_7}
            });
        {/if}
    </script>
{/if}