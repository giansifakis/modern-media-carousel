<?php
/**
 * Plugin Name: Modern Media Carousel
 * Description: Create beautiful 16:9 image carousels from your WordPress Media Library with drag-and-drop ordering and easy shortcodes.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: modern-media-carousel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Modern_Media_Carousel {
    const VERSION   = '1.0.0';
    const POST_TYPE = 'mmc_carousel';
    const META_KEY  = '_mmc_image_ids';

    public static function boot() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_carousel' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
        add_shortcode( 'media_carousel', array( __CLASS__, 'shortcode' ) );
    }

    public static function register_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name'               => __( 'Carousels', 'modern-media-carousel' ),
                    'singular_name'      => __( 'Carousel', 'modern-media-carousel' ),
                    'add_new'            => __( 'Add New', 'modern-media-carousel' ),
                    'add_new_item'       => __( 'Add New Carousel', 'modern-media-carousel' ),
                    'edit_item'          => __( 'Edit Carousel', 'modern-media-carousel' ),
                    'new_item'           => __( 'New Carousel', 'modern-media-carousel' ),
                    'view_item'          => __( 'View Carousel', 'modern-media-carousel' ),
                    'search_items'       => __( 'Search Carousels', 'modern-media-carousel' ),
                    'not_found'          => __( 'No carousels found.', 'modern-media-carousel' ),
                    'not_found_in_trash' => __( 'No carousels found in the Trash.', 'modern-media-carousel' ),
                    'all_items'          => __( 'All Carousels', 'modern-media-carousel' ),
                    'menu_name'          => __( 'Carousels', 'modern-media-carousel' ),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_rest'        => false,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'rewrite'             => false,
                'query_var'           => false,
                'menu_icon'           => 'dashicons-images-alt2',
                'menu_position'       => 25,
                'supports'            => array( 'title' ),
            )
        );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'mmc_gallery',
            __( 'Carousel Images', 'modern-media-carousel' ),
            array( __CLASS__, 'render_gallery_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'mmc_shortcode',
            __( 'Your Shortcode', 'modern-media-carousel' ),
            array( __CLASS__, 'render_shortcode_box' ),
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public static function get_image_ids( $post_id ) {
        $stored = get_post_meta( $post_id, self::META_KEY, true );
        if ( ! is_array( $stored ) ) {
            return array();
        }

        $ids = array();
        foreach ( $stored as $value ) {
            $id = absint( $value );
            if ( $id && wp_attachment_is_image( $id ) && ! in_array( $id, $ids, true ) ) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public static function render_gallery_box( $post ) {
        $ids = self::get_image_ids( $post->ID );
        wp_nonce_field( 'mmc_save_gallery', 'mmc_gallery_nonce' );
        ?>
        <div class="mmc-admin-gallery">
            <p class="mmc-admin-intro">
                <?php esc_html_e( 'Select images from your Media Library. Drag the handle to change their order, or focus the handle and use the left/right arrow keys.', 'modern-media-carousel' ); ?>
            </p>
            <input type="hidden" name="mmc_image_ids" id="mmc-image-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
            <ul class="mmc-admin-images" id="mmc-admin-images">
                <?php foreach ( $ids as $id ) : ?>
                    <?php
                    $thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
                    $attachment = get_post( $id );
                    $name  = $attachment ? $attachment->post_title : __( 'Image', 'modern-media-carousel' );
                    ?>
                    <li class="mmc-admin-item" data-id="<?php echo esc_attr( $id ); ?>">
                        <div class="mmc-admin-thumbnail">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <span class="mmc-admin-item-name" title="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></span>
                        <div class="mmc-admin-item-actions">
                            <button type="button" class="mmc-admin-handle" aria-label="<?php esc_attr_e( 'Drag to reorder; use left and right arrow keys', 'modern-media-carousel' ); ?>" title="<?php esc_attr_e( 'Drag or use arrow keys to reorder', 'modern-media-carousel' ); ?>">
                                <span class="dashicons dashicons-move" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="mmc-admin-remove" aria-label="<?php esc_attr_e( 'Remove image', 'modern-media-carousel' ); ?>" title="<?php esc_attr_e( 'Remove image', 'modern-media-carousel' ); ?>">
                                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mmc-admin-empty" id="mmc-admin-empty" <?php echo $ids ? 'hidden' : ''; ?>>
                <span class="dashicons dashicons-format-gallery" aria-hidden="true"></span>
                <strong><?php esc_html_e( 'No images yet', 'modern-media-carousel' ); ?></strong>
                <span><?php esc_html_e( 'Add some images to start your carousel.', 'modern-media-carousel' ); ?></span>
            </div>
            <div class="mmc-admin-toolbar">
                <button type="button" class="button button-primary" id="mmc-add-images">
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <?php esc_html_e( 'Add Images', 'modern-media-carousel' ); ?>
                </button>
                <button type="button" class="button" id="mmc-clear-images" <?php echo $ids ? '' : 'disabled'; ?>>
                    <?php esc_html_e( 'Remove All', 'modern-media-carousel' ); ?>
                </button>
                <span class="mmc-admin-count" id="mmc-admin-count" aria-live="polite">
                    <?php
                    printf(
                        /* translators: %d: number of images */
                        esc_html( _n( '%d image', '%d images', count( $ids ), 'modern-media-carousel' ) ),
                        count( $ids )
                    );
                    ?>
                </span>
            </div>
            <p class="description"><?php esc_html_e( 'The published carousel always uses a 16:9 frame. Portrait and landscape pictures appear in full, without cropping.', 'modern-media-carousel' ); ?></p>
        </div>
        <?php
    }

    public static function render_shortcode_box( $post ) {
        ?>
        <div class="mmc-shortcode-box">
            <p><?php esc_html_e( 'Publish or update this carousel, then paste the shortcode into any post, page, or Shortcode block.', 'modern-media-carousel' ); ?></p>
            <input
                type="text"
                class="widefat mmc-shortcode-field"
                readonly
                aria-label="<?php esc_attr_e( 'Carousel shortcode', 'modern-media-carousel' ); ?>"
                value="<?php echo esc_attr( '[media_carousel id="' . $post->ID . '"]' ); ?>"
            />
            <button type="button" class="button mmc-copy-shortcode">
                <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                <?php esc_html_e( 'Copy Shortcode', 'modern-media-carousel' ); ?>
            </button>
        </div>
        <?php
    }

    public static function save_carousel( $post_id ) {
        if ( ! isset( $_POST['mmc_gallery_nonce'] ) || ! is_string( $_POST['mmc_gallery_nonce'] ) ) {
            return;
        }
        $nonce = sanitize_text_field( wp_unslash( $_POST['mmc_gallery_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'mmc_save_gallery' ) ) {
            return;
        }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( ! isset( $_POST['mmc_image_ids'] ) || ! is_string( $_POST['mmc_image_ids'] ) ) {
            return;
        }

        $raw  = sanitize_text_field( wp_unslash( $_POST['mmc_image_ids'] ) );
        $ids  = array();
        foreach ( explode( ',', $raw ) as $value ) {
            $id = absint( trim( $value ) );
            if ( $id && wp_attachment_is_image( $id ) && ! in_array( $id, $ids, true ) ) {
                $ids[] = $id;
            }
        }
        update_post_meta( $post_id, self::META_KEY, $ids );
    }

    public static function admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
            return;
        }
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
            return;
        }

        wp_enqueue_style(
            'mmc-admin',
            plugins_url( 'assets/css/admin.css', __FILE__ ),
            array(),
            self::VERSION
        );

        if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-sortable' );
        }

        $admin_dependencies = array( 'jquery' );
        if ( 'edit.php' !== $hook ) {
            $admin_dependencies[] = 'jquery-ui-sortable';
        }

        wp_enqueue_script(
            'mmc-admin',
            plugins_url( 'assets/js/admin.js', __FILE__ ),
            $admin_dependencies,
            self::VERSION,
            true
        );

        wp_localize_script(
            'mmc-admin',
            'mmcAdmin',
            array(
                'selectImages' => __( 'Select carousel images', 'modern-media-carousel' ),
                'addSelected'  => __( 'Add Selected Images', 'modern-media-carousel' ),
                'image'        => __( 'Image', 'modern-media-carousel' ),
                'imageCount'   => __( '%d images', 'modern-media-carousel' ),
                'oneImage'     => __( '1 image', 'modern-media-carousel' ),
                'dragLabel'    => __( 'Drag to reorder; use left and right arrow keys', 'modern-media-carousel' ),
                'dragTitle'    => __( 'Drag or use arrow keys to reorder', 'modern-media-carousel' ),
                'remove'       => __( 'Remove image', 'modern-media-carousel' ),
                'copied'       => __( 'Copied!', 'modern-media-carousel' ),
            )
        );
    }

    public static function columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['mmc_shortcode'] = __( 'Shortcode', 'modern-media-carousel' );
                $new['mmc_images']    = __( 'Images', 'modern-media-carousel' );
            }
        }
        return $new;
    }

    public static function column_content( $column, $post_id ) {
        if ( 'mmc_shortcode' === $column ) {
            $shortcode = '[media_carousel id="' . $post_id . '"]';
            echo '<code>' . esc_html( $shortcode ) . '</code> ';
            echo '<button type="button" class="button button-small mmc-copy-shortcode" data-shortcode="' . esc_attr( $shortcode ) . '">';
            esc_html_e( 'Copy', 'modern-media-carousel' );
            echo '</button>';
        } elseif ( 'mmc_images' === $column ) {
            echo esc_html( (string) count( self::get_image_ids( $post_id ) ) );
        }
    }

    public static function frontend_assets() {
        // Lightweight, dependency-free assets are loaded early to support shortcodes
        // in classic posts, blocks, widgets, and page builders without late CSS issues.
        wp_enqueue_style(
            'mmc-frontend',
            plugins_url( 'assets/css/frontend.css', __FILE__ ),
            array(),
            self::VERSION
        );
        wp_enqueue_script(
            'mmc-frontend',
            plugins_url( 'assets/js/frontend.js', __FILE__ ),
            array(),
            self::VERSION,
            true
        );
    }

    public static function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'media_carousel' );
        $post_id = absint( $atts['id'] );
        if ( ! $post_id ) {
            return '';
        }

        $post = get_post( $post_id );
        if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
            return '';
        }

        $ids = self::get_image_ids( $post_id );
        if ( empty( $ids ) ) {
            return '';
        }

        $instance_id = wp_unique_id( 'mmc-carousel-' );
        $total = count( $ids );
        $name = $post->post_title ? $post->post_title : __( 'Image carousel', 'modern-media-carousel' );

        ob_start();
        ?>
        <div
            id="<?php echo esc_attr( $instance_id ); ?>"
            class="mmc-carousel"
            role="region"
            aria-roledescription="<?php esc_attr_e( 'carousel', 'modern-media-carousel' ); ?>"
            aria-label="<?php echo esc_attr( $name ); ?>"
            tabindex="0"
            data-mmc-carousel
        >
            <div class="mmc-viewport">
                <div class="mmc-track" data-mmc-track>
                    <?php foreach ( $ids as $index => $id ) : ?>
                        <div
                            class="mmc-slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
                            role="group"
                            aria-roledescription="<?php esc_attr_e( 'slide', 'modern-media-carousel' ); ?>"
                            aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d', 'modern-media-carousel' ), $index + 1, $total ) ); ?>"
                            aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
                        >
                            <?php
                            echo wp_get_attachment_image(
                                $id,
                                'full',
                                false,
                                array(
                                    'class'    => 'mmc-image',
                                    'loading'  => 0 === $index ? 'eager' : 'lazy',
                                    'decoding' => 'async',
                                    'sizes'    => '(max-width: 900px) 100vw, 900px',
                                )
                            );
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mmc-counter" aria-live="polite" aria-atomic="true">
                    <span class="mmc-counter-current" data-mmc-current>1</span><span class="mmc-counter-divider">/</span><span class="mmc-counter-total"><?php echo esc_html( (string) $total ); ?></span>
                </div>
                <?php if ( $total > 1 ) : ?>
                    <button type="button" class="mmc-arrow mmc-arrow-prev" data-mmc-prev aria-label="<?php esc_attr_e( 'Previous image', 'modern-media-carousel' ); ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="mmc-arrow mmc-arrow-next" data-mmc-next aria-label="<?php esc_attr_e( 'Next image', 'modern-media-carousel' ); ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

Modern_Media_Carousel::boot();
