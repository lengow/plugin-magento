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
                    data: {action: 'get_sync_data'},
                    dataType: 'json',
                    success: function (data) {
                        var targetFrame = document.getElementById("lengow_iframe").contentWindow;
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




        //alert($('#h1').html());


    });
})(lengow_jquery);

