/*
 * HTML5 Sortable jQuery Plugin
 * http://farhadi.ir/projects/html5sortable
 *
 * Copyright 2012, Ali Farhadi
 * Released under the MIT license.
 */
(function(jQuery) {
    var dragging, placeholders = jQuery();
    jQuery.fn.cro_post_sortable = function(options) {
        var method = String(options);
        options = jQuery.extend({
            connectWith: false
        }, options);
        return this.each(function() {
            if (/^enable|disable|destroyjQuery/.test(method)) {
                var items = jQuery(this).children(jQuery(this).data('items')).attr('draggable', method == 'enable');
                if (method == 'destroy') {
                    items.add(this).removeData('connectWith items')
                            .off('dragstart.h5s dragend.h5s selectstart.h5s dragover.h5s dragenter.h5s drop.h5s');
                }
                return;
            }
            var isHandle, index, items = jQuery(this).children(options.items);
            var placeholder = jQuery('<' + (/^ul|oljQuery/i.test(this.tagName) ? 'li' : 'div') + ' class="sortable-placeholder">');
            items.find(options.handle).mousedown(function() {
                isHandle = true;
            }).mouseup(function() {
                isHandle = false;
            });
            jQuery(this).data('items', options.items)
            placeholders = placeholders.add(placeholder);
            if (options.connectWith) {
                jQuery(options.connectWith).add(this).data('connectWith', options.connectWith);
            }
            items.attr('draggable', 'true').on('dragstart.h5s', function(e) {
                if (options.handle && !isHandle) {
                    return false;
                }
                isHandle = false;
                var dt = e.originalEvent.dataTransfer;
                dt.effectAllowed = 'move';
                dt.setData('Text', 'dummy');
                index = (dragging = jQuery(this)).addClass('sortable-dragging').index();
            }).on('dragend.h5s', function() {
                if (!dragging) {
                    return;
                }
                dragging.removeClass('sortable-dragging').show();
                placeholders.detach();
                if (index != dragging.index()) {
                    dragging.parent().trigger('sortupdate', {item: dragging});
                }
                dragging = null;
            }).not('a[href], img').on('selectstart.h5s', function() {
                this.dragDrop && this.dragDrop();
                return false;
            }).end().add([this, placeholder]).on('dragover.h5s dragenter.h5s drop.h5s', function(e) {
                if (!items.is(dragging) && options.connectWith !== jQuery(dragging).parent().data('connectWith')) {
                    return true;
                }
                if (e.type == 'drop') {
                    e.stopPropagation();
                    placeholders.filter(':visible').after(dragging);
                    dragging.trigger('dragend.h5s');
                    return false;
                }
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                if (items.is(this)) {
                    if (options.forcePlaceholderSize) {
                        placeholder.height(dragging.outerHeight());
                    }
                    dragging.hide();
                    jQuery(this)[placeholder.index() < jQuery(this).index() ? 'after' : 'before'](placeholder);
                    placeholders.not(placeholder).detach();
                } else if (!placeholders.is(this) && !jQuery(this).children(options.items).length) {
                    placeholders.detach();
                    jQuery(this).append(placeholder);
                }
                return false;
            });
        });
    };
})(jQuery);