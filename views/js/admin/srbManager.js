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
