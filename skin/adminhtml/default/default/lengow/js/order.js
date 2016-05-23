document.observe("dom:loaded", function() {

    $('lengow_import_orders').observe('click', function() {
        var url = $(this).readAttribute('data-href');
        new Ajax.Request(url,{
            method: 'post',
            parameters: {action: 'import_all', form_key: FORM_KEY},
            onSuccess: function(messages) {
                lengowOrderGridJsObject.reload();
                var messagesJson = messages.responseText.evalJSON();
                var all_messages = '';
                messagesJson.each(function(message) {
                    all_messages += message+'<br/>';
                })
                $('lengow_wrapper_messages').insert(all_messages);
                $('lengow_wrapper_messages').appear({ duration: 0.250 });
            }
        });
    });

    if ($('lengow_migrate_order') != null) {
        $('lengow_migrate_order').observe('click', function() {
            var url = $(this).readAttribute('data-href');
            new Ajax.Request(url,{
                method: 'post',
                parameters: {action: 'migrate_order', form_key: FORM_KEY},
                onSuccess: function(response) {
                    lengowOrderGridJsObject.reload();
                }
            });
        });
    };

});

function reloadGrid(grid, current, transport) {
    grid.reload();
}
