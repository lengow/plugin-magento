(function( $ ) {
    $(document).ready(function () {

        if ($('#change_option_selected').is(':checked')){
            $('#lengow_product_grid').show();
        } else {
            $('#lengow_product_grid').hide();
        }

        $('.lengow-connector').on('change', '.lengow_switch_option', function (event, state) {
            var href = $(this).attr('data-href'),
                action = $(this).attr('data-action'),
                storeId = $(this).attr('data-id_store'),
                state = $(this).prop('checked');
            $.ajax({
                url: href,
                method: 'POST',
                data: {
                    state: state ? 1 : 0,
                    action: action,
                    store_id: storeId,
                    form_key: FORM_KEY
                },
                dataType: 'script',
                success: function(data){
                    $("#total_products").load(location.href + " #total_products");
                    $("#exported_products").load(location.href + " #exported_products");
                    if (action === 'change_option_selected' && data == "1") {
                        $('#lengow_product_grid').show();
                    } else if (action === 'change_option_selected'){
                        $('#lengow_product_grid').hide();
                    }
                }
            });
        });

        $('.lengow-connector').on('click', '.field-row', function() {
            $("#total_products").load(location.href + " #total_products");
            $("#exported_products").load(location.href + " #exported_products");
        });

        /* SWITCH TOGGLE */
        $('.lengow-connector').on('change', '.lgw-switch', function() {
            var check = $(this);
            var checked = check.find('input').prop('checked');
            check.toggleClass('checked');
        });
    });
})(lengow_jquery);


function reloadGrid(grid, current, transport) {
    grid.reload();
}
