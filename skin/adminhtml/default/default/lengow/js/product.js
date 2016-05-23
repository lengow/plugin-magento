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
                        $('.lengow_check_shop').after("<a href='" + data + "'<span>sync</span></a>");
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

document.observe("dom:loaded", function() {


     /*varienGridMassaction.prototype.onMassactionComplete = varienGridMassaction.prototype.onMassactionComplete.wrap(
         function(parent, transport) {
            var containerId = 'productGrid';
            var divId = $(containerId);
            var responseText = transport.responseText.replace(/>\s+</g, '><');
            divId.update(responseText);
            //transport.initMassactionElements();
            return parent();
         }
     );*/




//
//
//     function loadItem(transport) {
//         var containerId = 'productGrid';
//         var divId = $(containerId);
//         try {
//             var responseText = transport.responseText.replace(/>\s+</g, '><');
//             divId.update(responseText);
//             productGridJsObject.initGridAjax();
//             transport.initMassactionElements();
//             //transport.setUseAjax(true);
//         } catch (e) {
//             divId.update(responseText);
//             productGridJsObject.initGridAjax();
//             transport.initMassactionElements();
//             //transport.setUseAjax(true);
//         }
//
//     }
//
//     productGrid_massactionJsObject.setUseAjax(true);
//     varienGridMassaction.prototype.apply = varienGridMassaction.prototype.apply.wrap(
//         function() {
//             if (varienStringArray.count(this.checkedString) == 0) {
//                 alert(this.errorText);
//                 return;
//             }
//
//             var item = this.getSelectedItem();
//             if (!item) {
//                 this.validator.validate();
//                 return;
//             }
//             this.currentItem = item;
//             var fieldName = (item.field ? item.field : this.formFieldName);
//             var fieldsHtml = '';
//
//             if (this.currentItem.confirm && !window.confirm(this.currentItem.confirm)) {
//                 return;
//             }
//
//             this.formHiddens.update('');
//             new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({
//                 name: fieldName,
//                 value: this.checkedString
//             }));
//             new Insertion.Bottom(this.formHiddens, this.fieldTemplate.evaluate({
//                 name: 'massaction_prepare_key',
//                 value: fieldName
//             }));
//
//             if (!this.validator.validate()) {
//                 return;
//             }
//
//             if (this.useAjax && item.url) {
//                 new Ajax.Request(item.url, {
//                     'method': 'post',
//                     'parameters': this.form.serialize(true),
//                     'onComplete': this.onMassactionComplete.bind(this),
//                     'onSuccess': function(response) {
//                         loadItem(response);
//                     }
//                 });
//             } else if (item.url) {
//                 this.form.action = item.url;
//                 this.form.submit();
//             }
//         }
//     );
//
//
 });