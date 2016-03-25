(function( $ ) {
    $(function() {

        $('#lengow-container').hide();
        $('<iframe id="lengow-iframe">', {
            id:  'lengow-iframe',
            frameborder: 0,
            scrolling: 'no'
        }).appendTo('#lengow-iframe-container');
        $('#lengow-iframe').show();

        var sync_iframe = document.getElementById('lengow-iframe');
        if (sync_iframe) {
            sync_iframe.onload = function () {
                $.ajax({
                    method: 'POST',
                    data: {action: 'get_sync_data', form_key: FORM_KEY},
                    dataType: 'json',
                    success: function (data) {
                        var targetFrame = document.getElementById("lengow-iframe").contentWindow;
                        targetFrame.postMessage(data, '*');
                    }
                });
            };
            sync_iframe.src = '/skin/adminhtml/default/default/lengow/temp/index.html';
            //sync_iframe.src = '/modules/lengow/webservice/sync.php';
            //$('#lengow-iframe').height($('body').height() - $('.header').height() - $('.notification-global').height());
            //resize();
            //$(window).on('resize', function () {
            //    resize();
            //});
            //function resize() {
            //    $('#lengow-iframe').height($('body').height());
            //}
        }

        window.addEventListener("message", receiveMessage, false);

        function receiveMessage(event) {
            //if (event.origin !== "http://solution.lengow.com")
            //    return;

            switch (event.data.function) {
                case 'sync':
                    //Store lengow information into prestashop :
                    // account_id
                    // access_token
                    // secret_token
                    $.ajax({
                        method: 'POST',
                        data: {action: 'sync', data: event.data.parameters, form_key: FORM_KEY},
                        dataType: 'script'
                    });
                    break;
                case 'sync_and_reload':
                    //Store lengow information into prestashop and reload it
                    // account_id
                    // access_token
                    // secret_token
                    $.ajax({
                        method: 'POST',
                        data: {action: 'sync', data: event.data.parameters, form_key: FORM_KEY},
                        dataType: 'script',
                        success: function() {
                            location.reload();
                        }
                    });
                    break;
                case 'reload':
                    //Reload the parent page (after sync is ok)
                    location.reload();
                    break;
            }
        }
    });
})(lengow_jquery);

