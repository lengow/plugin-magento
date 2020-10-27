(function( $ ) {
    $(function() {

        var syncLink = $('#lengow_sync_link').val();
        var isoCode = $('#lengow_lang_iso').val();
        var lengowUrl = $('#lengow_url').val();

        $('#lengow-container').hide();
        $('<iframe id="lengow-iframe">', {
            id:  'lengow-iframe',
            frameborder: 0,
            scrolling: 'yes'
        }).appendTo('#lengow-iframe-container');

        var syncIframe = document.getElementById('lengow-iframe');
        var href = $('#lengow-iframe-container').attr('data-href');
        if (syncIframe) {
            syncIframe.onload = function () {
                $.ajax({
                    url: href,
                    method: 'POST',
                    data: {action: 'get_sync_data', form_key: FORM_KEY},
                    dataType: 'json',
                    success: function (data) {
                        var targetFrame = document.getElementById('lengow-iframe').contentWindow;
                        targetFrame.postMessage(data, '*');
                    }
                });
            };
            syncIframe.src = (syncLink ? 'https://cms.' + lengowUrl + '/sync/' : 'https://cms.' + lengowUrl + '/')
                + '?lang=' + isoCode + '&clientType=magento';
            $('#lengow-iframe').show();
        }

        window.addEventListener("message", receiveMessage, false);

        function receiveMessage(event) {
            switch (event.data.function) {
                case 'sync':
                    // store lengow information into Magento :
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
                    // store lengow information into Magento and reload it
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
                    // reload the parent page (after sync is ok)
                    location.reload();
                    break;
                case 'cancel':
                    // reload Dashboard page
                    var hrefCancel = location.href.replace('?isSync=true', '');
                    window.location.replace(hrefCancel);
                    break;
            }
        }
    });
})(lengow_jquery);

