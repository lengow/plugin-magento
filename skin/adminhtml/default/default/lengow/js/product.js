(function( $ ) {
    $(document).ready(function () {

        if ($('#change_option_selected').is(':checked')){
            $('#lengow_product_grid').show();
        } else {
            $('#lengow_product_grid').hide();
        }

        function checkStore() {
            var href = $('.lengow_check_store').attr('data-href'),
                store_id = $('.lengow_check_store').attr('data-id_store');
            $.getJSON({
                url: href,
                method: 'POST',
                data: {action: 'check_store', store_id: store_id, form_key: FORM_KEY},
                dataType: 'json',
                beforeSend: function () {
                    $('.lengow_check_store').html('<i class="fa fa-circle-o-notch fa-spin"></i>');
                },
                success: function (data) {
                    if (data.result == false) {
                        $('.lengow_check_store').html('<span class="no_indexation">'+data.message+'</span>');
                        $('.lengow_check_store').attr('id', data.id);
                        $('.lengow_check_store').after('<a href="'+data.link_href+'"><span>'+data.link_title+'</span></a>');
                    } else {
                        $('.lengow_check_store').html('<span class="last_indexation">'+data.message+'</span>');
                        $('.lengow_check_store').attr('id', data.id);
                        $('.lengow_check_store').after(data.link_title);
                    }
                }
            });
        }

        checkStore();

        $('.lengow-connector').on('change', '.lengow_switch_option', function (event, state) {
            var href = $(this).attr('data-href'),
                action = $(this).attr('data-action'),
                store_id = $(this).attr('data-id_store'),
                state = $(this).prop('checked');
            $.ajax({
                url: href,
                method: 'POST',
                data: {
                    state: state ? 1 : 0,
                    action: action,
                    store_id: store_id,
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
