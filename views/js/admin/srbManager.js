/**
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
 **/

$(document).ready(function () {
    function getSRBObjectName(string) {
        return 'SRB' + string.charAt(0).toUpperCase() + string.slice(1).toLowerCase(); // Capitalize to have for example "SRBProduct"
    }

    function ajaxAsyncCall(dataSRB) {

        $.ajax({
            type: 'POST',
            url: admin_module_ajax_url_shoprunback,
            data: {
                ajax: true,
                action: 'syncAll',
                actionSRB: dataSRB.actionSRB,
                className: dataSRB.className,
                params: dataSRB.params
            },
            dataType: 'json',
            beforeSend: function () {
                $('#srb-loader').removeClass('hidden');
                $('#srb-manager').addClass('hidden');
            },
            success: function (response) {
              window.location.reload();
            },
            error: function (response) {
              window.location.reload();
            }
        });
    }

    $('#srb-content .sync-element').on('click', function (e) {
        e.preventDefault();

         var data = {
            'actionSRB': 'sync',
            'className': getSRBObjectName($(this).data('type')),
            'params': parseInt($(this).parent().parent().data('id'))
        };

        ajaxAsyncCall(data);
    });

    $('#srb-content td[data-link][data-link!=""]').on('click', function (e) {
        window.open($(this).data('link'), '_blank');
    });

    $('.post-all').on('click', function (e) {
        e.preventDefault();

        var data = {
            'actionSRB': 'syncAll',
            'className': getSRBObjectName($(this).data('type')),
            'params': ''
        };

        ajaxAsyncCall(data);
    });
});
