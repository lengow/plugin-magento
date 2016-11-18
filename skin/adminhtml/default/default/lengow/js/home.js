(function( $ ) {
    $(function() {

        var syncLink = $('#lengow_sync_link').val();

        $('#lengow-container').hide();
        $('<iframe id="lengow-iframe">', {
            id:  'lengow-iframe',
            frameborder: 0,
            scrolling: 'yes'
        }).appendTo('#lengow-iframe-container');

        var syncIframe = document.getElementById('lengow-iframe');
        if (syncIframe) {
            syncIframe.onload = function () {
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
            if (syncLink) {
                // syncIframe.src = 'http://cms.lengow.io/sync/';
                // syncIframe.src = 'http://cms.lengow.net/sync/';
                syncIframe.src = 'http://cms.lengow.rec/sync/';
                // syncIframe.src = 'http://cms.lengow.dev/sync/';
            } else {
                // syncIframe.src = 'http://cms.lengow.io/';
                // syncIframe.src = 'http://cms.lengow.net/';
                syncIframe.src = 'http://cms.lengow.rec/';
                // syncIframe.src = 'http://cms.lengow.dev/';
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

