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
 
 <div id="config" class="row">
    <div class="col-md-8 col-md-offset-2">
        <h1>{l s="config.form.title" mod='shoprunback'}</h1>

        <form action="{$formActionUrl}" method="POST">
            {if $srbtoken != ''}
                <div class="alert alert-warning">
                    <p>{l s="config.form.reset_mapping" mod='shoprunback'}</p>
                </div>
            {/if}

            <div class="form-group">
                <label for="srbtoken">{l s="config.form.token" mod='shoprunback'}</label>
                <input type="text" name="srbtoken" value="{$srbtoken}" class="form-control" required />
            </div>

            {if $srbtoken}
                {if $PSOrderReturn == 1}
                    <div class="alert alert-warning">
                        <p>{l s="config.form.disable_ps_returns" mod='shoprunback'}</p>
                    </div>
                {/if}

                <div class="form-group">
                    <label for="production">{l s="config.form.production" mod='shoprunback'}</label>

                    <div class="radio">
                        <label>
                            {l s="config.form.yes" mod='shoprunback'}
                            <input type="radio" name="production" value="1" required {if $production == 1}checked="checked"{/if}>
                        </label>
                    </div>

                    <div class="radio">
                        <label>
                            {l s="config.form.no" mod='shoprunback'}
                            <input type="radio" name="production" value="0" required {if $production == 0}checked="checked"{/if}>
                        </label>
                    </div>
                </div>
            {/if}

            <div class="form-group">
                <label for="enable_return_btn">{l s="Enable return button" mod='shoprunback'}</label>

                <div class="radio">
                    <label>
                        {l s="Enable" mod='shoprunback'}
                        <input type="radio" name="enable_return_btn" value="1" required {if $enable_return_btn == 1}checked="checked"{/if}>
                    </label>
                </div>

                <div class="radio">
                    <label>
                        {l s="Disable" mod='shoprunback'}
                        <input type="radio" name="enable_return_btn" value="0" required {if $enable_return_btn == 0}checked="checked"{/if}>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <p>{l s='If you wish to display the return part anyway, please add the &token_srb=[Your SRB Token] to the order detail URL.' mod='shoprunback'}</p>
                <p>{l s='Please be aware that the return part will only be displayed for you and not for your customers.' mod='shoprunback'}</p>
            </div>

            <div class="form-group">
                <button class="btn btn-default pull-right" type="submit">{l s="config.form.save" mod='shoprunback'}</button>
            </div>
        </form>

        {if ! $srbtoken}
            <div class="link-to-srb row">
                <div class="col-12 no-account">
                    <a href="{$shoprunbackURLProd}" class="srb-button pull-center" target="_blank">{l s="config.no_account" mod='shoprunback'}</a>
                </div>
            </div>
        {/if}
    </div>

    <div class="export-logs col-md-2 text-center">
        <h2>
            <a href="{$exportLogsUrl}" class="btn btn-default" type="submit">{l s="config.logs.export" mod='shoprunback'}</a>
        </h2>
    </div>
</div>