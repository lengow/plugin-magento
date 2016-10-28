(function( $ ) {
    $(function() {

        var sync_link = $('#lengow_sync_link').val();

        $('#lengow-container').hide();
        $('<iframe id="lengow-iframe">', {
            id:  'lengow-iframe',
            frameborder: 0,
            scrolling: 'no'
        }).appendTo('#lengow-iframe-container');

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
            if (sync_link) {
                // sync_iframe.src = 'http://cms.lengow.io/sync/';
                // sync_iframe.src = 'http://cms.lengow.net/sync/';
                sync_iframe.src = 'http://cms.lengow.rec/sync/';
                // sync_iframe.src = 'http://cms.lengow.dev/sync/';
            } else {
                // sync_iframe.src = 'http://cms.lengow.io/';
                // sync_iframe.src = 'http://cms.lengow.net/';
                sync_iframe.src = 'http://cms.lengow.rec/';
                // sync_iframe.src = 'http://cms.lengow.dev/';
            }
            $('#lengow-iframe').show();
        }

        window.addEventListener("message", receiveMessage, false);

        function receiveMessage(event) {
            //if (event.origin !== "http://solution.lengow.com")
            //    return;

            switch (event.data.function) {
                case 'sync':
                    //Store lengow information into magento :
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
                    //Store lengow information into magento and reload it
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

