(function( $ ) {
    $(document).ready(function () {

        if ($('#change_option_selected').is(':checked')){
            $('#productGrid').show();
        } else {
            $('#productGrid').hide();
        }

        function checkShop() {
            var href = $('.lengow_check_shop').attr('data-href'),
                id_shop = $(this).attr('data-id_shop');
            $.ajax({
                url: href,
                method: 'POST',
                data: {action: 'check_shop', id_shop: id_shop, form_key: FORM_KEY},
                dataType: 'script',
                beforeSend: function () {
                    $('.lengow_check_shop').html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                },
                success: function (data) {
                    if (data == "1") {
                        $('.lengow_check_shop').html("");
                        $('.lengow_check_shop').attr("id", "lengow_shop_sync");
                    } else {
                        $('.lengow_check_shop').html("");
                        $('.lengow_check_shop').attr("id", "lengow_shop_no_sync");
                        $('.lengow_check_shop').after("<a href='#'><span>sync</span></a>");
                    }
                }
            });
        }

        checkShop();

        $('.lengow-connector').on('switchChange.bootstrapSwitch', '.lengow_switch_option', function (event, state) {
            if (event.type == "switchChange") {
                var href = $(this).attr('data-href'),
                    action = $(this).attr('data-action'),
                    id_shop = $(this).attr('data-id_shop');
                $.ajax({
                    url: href,
                    method: 'POST',
                    data: {state: state ? 1 : 0, action: action, id_shop: id_shop, form_key: FORM_KEY},
                    dataType: 'script',
                    success: function(data){
                        $("#parent_total_products").load(location.href + " #total_products");
                        $("#parent_exported_products").load(location.href + " #exported_products");
                        if (action === 'change_option_selected' && data == "1") {
                            $('#productGrid').show();
                        } else if (action === 'change_option_selected'){
                            $('#productGrid').hide();
                        }
                    }
                });
            }
        });

        $('.lengow-connector').on('click', '.field-row', function() {
            $("#parent_total_products").load(location.href + " #total_products");
            $("#parent_exported_products").load(location.href + " #exported_products");
        });

        $('.lengow_switch').bootstrapSwitch();

    });
})(lengow_jquery);


function reloadGrid(grid, current, transport) {
    grid.reload();
}
