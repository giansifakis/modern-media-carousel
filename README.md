# Modern Media Carousel — WordPress Plugin

A lightweight WordPress plugin for responsive image carousels made from your Media Library.

## Features

- Create as many separate carousels as you like using **Carousels → Add New**.
- Add multiple images directly from the WordPress Media Library.
- Reorder by dragging the grip handles. You can also focus a handle and press **Left / Right** to reorder by keyboard.
- Remove one image or clear the entire selection.
- Copy a shortcode for each carousel and place it in any WordPress article, page, Shortcode block, or shortcode-enabled page builder.
- Fixed **16:9** display area with **no cropping**: every image uses `object-fit: contain` and is centered. For portraits or differently proportioned landscape photos, empty space is filled with a subtle dark background.
- Rounded, modern navigation arrows, an **`1/5`** style top-right counter, smooth transitions, wraparound navigation, swipe gestures on touch screens, and keyboard navigation.
- Responsive, no third-party CDN/library on the front end, automatic WordPress image `srcset` support, and accessible labels and focus indicators.

## Install

1. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
2. Upload the `modern-media-carousel.zip` file.
3. Click **Install Now** and **Activate Plugin**.
4. Open **Carousels → Add New** in the WordPress admin sidebar.
5. Give your carousel a title, click **Add Images**, and choose Media Library photos.
6. Drag the images into the desired order, then click **Publish**.
7. Copy the shortcode from the right sidebar (also available in **Carousels → All Carousels**).
8. Paste it into a post or page; for example:

   `[media_carousel id="123"]`

   Replace `123` with your carousel's actual ID. Save or publish the post.

## Notes

- The shortcode only displays **published** carousels with at least one valid image.
- With one image, navigation arrows are hidden and the counter reads `1/1`.
- Original media files remain in your Media Library if an image is removed from a carousel or the carousel itself is deleted.
- For accessibility, add meaningful **Alt Text** to images in your Media Library.
- Front-end assets are small and loaded on normal WordPress pages, including those where the shortcode may be inserted by a page builder.
- No autoplay is included: visitors navigate with controls or gestures.

## Compatibility

- WordPress 5.8+; PHP 7.4+.
- No build process and no external JavaScript/CSS dependencies on the front end.

## File layout

- `modern-media-carousel.php` — plugin registration, admin UI, data storage, shortcode rendering.
- `assets/js/admin.js` / `assets/css/admin.css` — image selection and drag-and-drop admin UI.
- `assets/js/frontend.js` / `assets/css/frontend.css` — interactive responsive carousel.

License: GPL-2.0-or-later.
