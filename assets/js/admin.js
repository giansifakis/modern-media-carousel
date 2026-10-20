/* global jQuery, wp, mmcAdmin */
(function ($) {
  'use strict';

  $(function () {
    var $gallery = $('#mmc-admin-images');
    var $ids = $('#mmc-image-ids');
    var frame;

    function syncGallery() {
      if (!$gallery.length || !$ids.length) return;
      var ids = $gallery.children('.mmc-admin-item').map(function () {
        return String($(this).attr('data-id'));
      }).get();
      $ids.val(ids.join(','));
      $('#mmc-admin-empty').prop('hidden', ids.length > 0);
      $('#mmc-clear-images').prop('disabled', ids.length === 0);
      $('#mmc-admin-count').text(ids.length === 1 ? mmcAdmin.oneImage : mmcAdmin.imageCount.replace('%d', ids.length));
    }

    function appendImage(attachment) {
      var id = parseInt(attachment.id, 10);
      if (!id || $gallery.children('[data-id="' + id + '"]').length) return;

      var src = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : (attachment.url || attachment.icon || '');
      var label = attachment.title || attachment.filename || mmcAdmin.image;
      var $item = $('<li>', { 'class': 'mmc-admin-item', 'data-id': id });
      var $thumbnail = $('<div>', { 'class': 'mmc-admin-thumbnail' });
      if (src) $('<img>', { src: src, alt: '' }).appendTo($thumbnail);
      $item.append($thumbnail);
      $item.append($('<span>', { 'class': 'mmc-admin-item-name', title: label }).text(label));
      var $actions = $('<div>', { 'class': 'mmc-admin-item-actions' });
      $actions.append(
        $('<button>', { type: 'button', 'class': 'mmc-admin-handle', 'aria-label': mmcAdmin.dragLabel, title: mmcAdmin.dragTitle })
          .append($('<span>', { 'class': 'dashicons dashicons-move', 'aria-hidden': 'true' }))
      );
      $actions.append(
        $('<button>', { type: 'button', 'class': 'mmc-admin-remove', 'aria-label': mmcAdmin.remove, title: mmcAdmin.remove })
          .append($('<span>', { 'class': 'dashicons dashicons-no-alt', 'aria-hidden': 'true' }))
      );
      $item.append($actions).appendTo($gallery);
    }

    if ($gallery.length && typeof $.fn.sortable === 'function') {
      $gallery.sortable({
        items: '> .mmc-admin-item',
        handle: '.mmc-admin-handle',
        cancel: '.mmc-admin-remove',
        placeholder: 'mmc-admin-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        distance: 4,
        update: syncGallery
      });

      $('#mmc-add-images').on('click', function (e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (!frame) {
          frame = wp.media({
            title: mmcAdmin.selectImages,
            button: { text: mmcAdmin.addSelected },
            library: { type: 'image' },
            multiple: true
          });
          frame.on('open', function () {
            // Each Add Images operation is independent of earlier selections.
            var selection = frame.state().get('selection');
            if (selection) selection.reset();
          });
          frame.on('select', function () {
            frame.state().get('selection').each(function (model) {
              appendImage(model.toJSON());
            });
            syncGallery();
          });
        }
        frame.open();
      });

      $gallery.on('click', '.mmc-admin-remove', function () {
        $(this).closest('.mmc-admin-item').remove();
        syncGallery();
      });

      // Keyboard-accessible alternative to drag-and-drop sorting.
      $gallery.on('keydown', '.mmc-admin-handle', function (e) {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        var $item = $(this).closest('.mmc-admin-item');
        if (e.key === 'ArrowLeft' && $item.prev('.mmc-admin-item').length) {
          $item.insertBefore($item.prev('.mmc-admin-item'));
        } else if (e.key === 'ArrowRight' && $item.next('.mmc-admin-item').length) {
          $item.insertAfter($item.next('.mmc-admin-item'));
        }
        syncGallery();
        $(this).trigger('focus');
      });

      $('#mmc-clear-images').on('click', function () {
        $gallery.empty();
        syncGallery();
      });

      syncGallery();
    }

    $(document).on('click', '.mmc-copy-shortcode', function () {
      var button = this;
      var $button = $(button);
      var value = $button.attr('data-shortcode') || $('.mmc-shortcode-field').val();
      if (!value) return;
      var original = $button.text();

      function markCopied() {
        $button.text(mmcAdmin.copied);
        window.setTimeout(function () { $button.text(original); }, 1800);
      }

      function fallbackCopy() {
        var $temporary = $('<textarea>').val(value).css({ position: 'fixed', left: '-9999px' }).appendTo('body');
        $temporary[0].select();
        var success = false;
        try { success = document.execCommand('copy'); } catch (e) { /* unsupported */ }
        $temporary.remove();
        if (success) markCopied();
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(markCopied, fallbackCopy);
      } else {
        fallbackCopy();
      }
    });
  });
})(jQuery);
