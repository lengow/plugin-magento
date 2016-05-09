(function( $ ) {
    $(function() {

        $('.lengow-connector').on('switchChange.bootstrapSwitch', '.lengow_switch_option', function (event, state) {
            if (event.type == "switchChange") {
                var href = $(this).attr('data-href');
                var action = $(this).attr('data-action');
                var id_shop = $(this).attr('data-id_shop');
                $.ajax({
                    url: href,
                    method: 'POST',
                    data: {state: state ? 1 : 0, action: action, id_shop: id_shop, form_key: FORM_KEY},
                    dataType: 'script'
                });
            }
        });

        $('.lengow-connector').on('change', '#lengow_select', function () {
                var href = $(this).attr('data-href');
                var action = $(this).attr('data-action');
                var id_shop = $(this).attr('data-id_shop');
                var values = $(this).val();
                $.ajax({
                    url: href,
                    method: 'POST',
                    data: {action: action, id_shop: id_shop, types:values.join(','), form_key: FORM_KEY},
                    dataType: 'script'
                });
        });


        $('.lengow_switch').bootstrapSwitch();
        $('.lengow_select').select2({
            tags: true,
            tokenSeparators: [',']
        });
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