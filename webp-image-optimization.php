<?php
/*
Plugin Name: WebP Image Optimization
Plugin URI: https://github.com/adgardner1392/webp-image-optimization
Description: Automatically converts uploaded images to WebP format and resizes them. Also allows manual conversion from the Media Library with undo functionality.
Version: 1.4.0
Author: Adam Gardner
Author URI: https://github.com/adgardner1392
License: GPLv2 or later
Text Domain: webp-image-optimization
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebP_Image_Optimization {

    public function __construct() {
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Add settings page
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Handle file upload
        add_filter( 'wp_handle_upload', array( $this, 'handle_upload' ), 10, 2 );

        // Generate attachment metadata
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ), 10, 2 );

        // Add attachment buttons
        add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_buttons' ), 10, 2 );

        // Allow WebP uploads
        add_filter( 'upload_mimes', array( $this, 'allow_webp_uploads' ) );

        // Fix WebP mime type
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_webp_mime_type' ), 10, 4 );

        // Register AJAX handler for converting attachments to WebP and replacing the original
        add_action( 'wp_ajax_webp_convert_attachment', array( $this, 'ajax_convert_attachment_replace' ) );

        // Add Convert to WebP button to the Media Library
        add_filter( 'media_row_actions', array( $this, 'add_media_buttons' ), 10, 2 );

        // Register AJAX handler for converting attachments to WebP from the Media Library
        add_action( 'wp_ajax_webp_convert_media_attachment', array( $this, 'ajax_convert_media_attachment' ) );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets( $hook ) {
        // Enqueue on plugin settings page
        if ( $hook === 'tools_page_webp-image-optimization' ) {
            // Enqueue CSS
            wp_enqueue_style(
                'webp-image-optimization-admin',
                plugin_dir_url( __FILE__ ) . 'css/admin.css',
                array(),
                '1.3.1'
            );

            // Enqueue JS
            wp_enqueue_script(
                'webp-image-optimization-admin',
                plugin_dir_url( __FILE__ ) . 'js/admin.js',
                array( 'jquery' ),
                '1.3.1',
                true
            );

            // Localize script with AJAX URL and nonce
            wp_localize_script( 'webp-image-optimization-admin', 'webpImageOptimization', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'webp_image_optimization_nonce' ),
            ));
        }

        // Enqueue on Media Library pages
        if ( in_array( $hook, array( 'upload.php', 'media.php' ), true ) ) {
            // Enqueue JS for Media Library
            wp_enqueue_script(
                'webp-image-optimization-media',
                plugin_dir_url( __FILE__ ) . 'js/media.js',
                array( 'jquery' ),
                '1.3.1',
                true
            );

            // Localize script with AJAX URL and nonce
            wp_localize_script( 'webp-image-optimization-media', 'webpImageOptimization', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'webp_image_optimization_nonce' ),
            ));
        }
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'tools.php',
            __( 'WebP Image Optimization Settings', 'webp-image-optimization' ),
            __( 'WebP Image Optimization', 'webp-image-optimization' ),
            'manage_options',
            'webp-image-optimization',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'webp_image_optimization_settings_group',
            'webp_image_optimization_settings',
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'webp_image_optimization_settings_section',
            __( 'Resize and Conversion Settings', 'webp-image-optimization' ),
            array( $this, 'settings_section_callback' ),
            'webp_image_optimization_settings'
        );

        // Maximum Width field
        add_settings_field(
            'max_width',
            __( 'Maximum Width (px)', 'webp-image-optimization' ),
            array( $this, 'max_width_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // Maximum Height field
        add_settings_field(
            'max_height',
            __( 'Maximum Height (px)', 'webp-image-optimization' ),
            array( $this, 'max_height_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // JPEG Quality field
        add_settings_field(
            'jpeg_quality',
            __( 'JPEG Quality (0-100)', 'webp-image-optimization' ),
            array( $this, 'jpeg_quality_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // PNG Compression Level field
        add_settings_field(
            'png_compression',
            __( 'PNG Compression Level (0-9)', 'webp-image-optimization' ),
            array( $this, 'png_compression_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // WebP Quality field
        add_settings_field(
            'webp_quality',
            __( 'WebP Quality (0-100)', 'webp-image-optimization' ),
            array( $this, 'webp_quality_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // Don't Convert JPEG checkbox
        add_settings_field(
            'dont_convert_jpeg',
            __( 'Disable conversion of JPEG images to WebP format', 'webp-image-optimization' ),
            array( $this, 'dont_convert_jpeg_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );

        // Don't Convert PNG checkbox
        add_settings_field(
            'dont_convert_png',
            __( 'Disable conversion of PNG images to WebP format', 'webp-image-optimization' ),
            array( $this, 'dont_convert_png_render' ),
            'webp_image_optimization_settings',
            'webp_image_optimization_settings_section'
        );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings( $input ) {
        $output = array();

        // Sanitize Maximum Width
        if ( isset( $input['max_width'] ) ) {
            $output['max_width'] = intval( $input['max_width'] );
            if ( $output['max_width'] <= 0 ) {
                $output['max_width'] = 1500; // Default value
            }
        }

        // Sanitize Maximum Height
        if ( isset( $input['max_height'] ) ) {
            $output['max_height'] = intval( $input['max_height'] );
            if ( $output['max_height'] <= 0 ) {
                $output['max_height'] = 1500; // Default value
            }
        }

        // Sanitize JPEG Quality
        if ( isset( $input['jpeg_quality'] ) ) {
            $jpeg_quality = intval( $input['jpeg_quality'] );
            if ( $jpeg_quality < 0 || $jpeg_quality > 100 ) {
                $jpeg_quality = 90; // Default value
            }
            $output['jpeg_quality'] = $jpeg_quality;
        } else {
            $output['jpeg_quality'] = 90; // Default value
        }

        // Sanitize PNG Compression Level
        if ( isset( $input['png_compression'] ) ) {
            $png_compression = intval( $input['png_compression'] );
            if ( $png_compression < 0 || $png_compression > 9 ) {
                $png_compression = 6; // Default value
            }
            $output['png_compression'] = $png_compression;
        } else {
            $output['png_compression'] = 6; // Default value
        }

        // Sanitize WebP Quality
        if ( isset( $input['webp_quality'] ) ) {
            $webp_quality = intval( $input['webp_quality'] );
            if ( $webp_quality < 0 || $webp_quality > 100 ) {
                $webp_quality = 80; // Default value
            }
            $output['webp_quality'] = $webp_quality;
        } else {
            $output['webp_quality'] = 80; // Default value
        }

        // Sanitize Don't Convert JPEG checkbox
        $output['dont_convert_jpeg'] = isset( $input['dont_convert_jpeg'] ) && $input['dont_convert_jpeg'] == '1' ? true : false;

        // Sanitize Don't Convert PNG checkbox
        $output['dont_convert_png'] = isset( $input['dont_convert_png'] ) && $input['dont_convert_png'] == '1' ? true : false;

        return $output;
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Set the maximum dimensions for images, specify image quality/compression, and select which image types you do not want to convert to WebP. Images larger than the specified dimensions will be resized upon upload.', 'webp-image-optimization' ) . '</p>';
    }

    /**
     * Render Maximum Width field
     */
    public function max_width_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $max_width = isset( $options['max_width'] ) ? esc_attr( $options['max_width'] ) : '1500';
        echo '<input type="number" name="webp_image_optimization_settings[max_width]" value="' . esc_attr( $max_width ) . '" min="1" />';
    }

    /**
     * Render Maximum Height field
     */
    public function max_height_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $max_height = isset( $options['max_height'] ) ? esc_attr( $options['max_height'] ) : '1500';
        echo '<input type="number" name="webp_image_optimization_settings[max_height]" value="' . esc_attr( $max_height ) . '" min="1" />';
    }

    /**
     * Render JPEG Quality field
     */
    public function jpeg_quality_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $jpeg_quality = isset( $options['jpeg_quality'] ) ? esc_attr( $options['jpeg_quality'] ) : '90';
        ?>
        <div class="webp-settings__field webp-settings__field--jpeg-quality">
            <input type="range" class="webp-settings__slider" id="jpeg_quality_range" value="<?php echo esc_attr( $jpeg_quality ); ?>" min="0" max="100" />
            <input type="number" class="webp-settings__input" id="jpeg_quality_number" name="webp_image_optimization_settings[jpeg_quality]" value="<?php echo esc_attr( $jpeg_quality ); ?>" min="0" max="100" />
            <span class="webp-settings__value" id="jpeg_quality_value"><?php echo esc_html( $jpeg_quality ); ?></span>
        </div>
        <?php
    }

    /**
     * Render PNG Compression Level field
     */
    public function png_compression_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $png_compression = isset( $options['png_compression'] ) ? esc_attr( $options['png_compression'] ) : '6';
        ?>
        <div class="webp-settings__field webp-settings__field--png-compression">
            <input type="range" class="webp-settings__slider" id="png_compression_range" value="<?php echo esc_attr( $png_compression ); ?>" min="0" max="9" />
            <input type="number" class="webp-settings__input" id="png_compression_number" name="webp_image_optimization_settings[png_compression]" value="<?php echo esc_attr( $png_compression ); ?>" min="0" max="9" />
            <span class="webp-settings__value" id="png_compression_value"><?php echo esc_attr( $png_compression ); ?></span>
        </div>
        <?php
    }

    /**
     * Render WebP Quality field
     */
    public function webp_quality_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $webp_quality = isset( $options['webp_quality'] ) ? esc_attr( $options['webp_quality'] ) : '80';
        ?>
        <div class="webp-settings__field webp-settings__field--webp-quality">
            <input type="range" class="webp-settings__slider" id="webp_quality_range" value="<?php echo esc_attr( $webp_quality ); ?>" min="0" max="100" />
            <input type="number" class="webp-settings__input" id="webp_quality_number" name="webp_image_optimization_settings[webp_quality]" value="<?php echo esc_attr( $webp_quality ); ?>" min="0" max="100" />
            <span class="webp-settings__value" id="webp_quality_value"><?php echo esc_html( $webp_quality ); ?></span>
        </div>
        <?php
    }

    /**
     * Render Don't Convert JPEG checkbox
     */
    public function dont_convert_jpeg_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $dont_convert_jpeg = isset( $options['dont_convert_jpeg'] ) ? $options['dont_convert_jpeg'] : '';
        echo '<input type="checkbox" name="webp_image_optimization_settings[dont_convert_jpeg]" value="1"' . checked( 1, $dont_convert_jpeg, false ) . ' />';
    }

    /**
     * Render Don't Convert PNG checkbox
     */
    public function dont_convert_png_render() {
        $options = get_option( 'webp_image_optimization_settings' );
        $dont_convert_png = isset( $options['dont_convert_png'] ) ? $options['dont_convert_png'] : '';
        echo '<input type="checkbox" name="webp_image_optimization_settings[dont_convert_png]" value="1"' . checked( 1, $dont_convert_png, false ) . ' />';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WebP Image Optimization Settings', 'webp-image-optimization' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                // These functions already handle escaping internally.
                settings_fields( 'webp_image_optimization_settings_group' );
                do_settings_sections( 'webp_image_optimization_settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Hook into file upload to process images.
     */
    public function handle_upload( $upload, $context ) {
        $file_path = $upload['file'];

        // Get the settings
        $options = get_option( 'webp_image_optimization_settings' );
        $max_width       = isset( $options['max_width'] ) ? intval( $options['max_width'] ) : 1500;
        $max_height      = isset( $options['max_height'] ) ? intval( $options['max_height'] ) : 1500;

        // Resize the image
        $resized_image = $this->resize_image( $file_path, $max_width, $max_height );
        if ( $resized_image ) {
            $file_path = $resized_image;
            $upload['file'] = $file_path;
        }

        // Do not convert to WebP here. Delay to 'wp_generate_attachment_metadata'.

        return $upload;
    }

    public function generate_attachment_metadata( $metadata, $attachment_id ) {
        // Get the file path
        $file = get_attached_file( $attachment_id );

        if ( ! $file || ! file_exists( $file ) ) {
            return $metadata;
        }

        $file_info = pathinfo( $file );
        $extension = strtolower( $file_info['extension'] );

        // Only proceed if it's a supported image format
        if ( ! in_array( $extension, array( 'jpeg', 'jpg', 'png' ), true ) ) {
            return $metadata;
        }

        // Get the settings
        $options = get_option( 'webp_image_optimization_settings' );

        // Check if conversion for this type is disabled
        switch ( $extension ) {
            case 'jpeg':
            case 'jpg':
                if ( ! empty( $options['dont_convert_jpeg'] ) ) {
                    return $metadata; // Skip conversion
                }
                break;
            case 'png':
                if ( ! empty( $options['dont_convert_png'] ) ) {
                    return $metadata; // Skip conversion
                }
                break;
            default:
                return $metadata;
        }

        // Get the original file size
        $original_file_size = filesize( $file );

        // Convert to WebP
        $webp_file = $this->convert_to_webp( $file );

        if ( ! $webp_file || ! file_exists( $webp_file ) ) {
            return $metadata;
        }

        $webp_file_size = filesize( $webp_file );

        // Save the file sizes to attachment meta
        update_post_meta( $attachment_id, '_original_file_size', $original_file_size );
        update_post_meta( $attachment_id, '_webp_file_size', $webp_file_size );

        // Update the attachment to point to the WebP file
        $upload_dir = wp_upload_dir();
        $relative_webp_path = str_replace( $upload_dir['basedir'] . '/', '', $webp_file );
        $webp_url = $upload_dir['baseurl'] . '/' . $relative_webp_path;

        // Update the attachment's '_wp_attached_file' meta to point to WebP
        update_post_meta( $attachment_id, '_wp_attached_file', $relative_webp_path );

        // Update the attachment's 'guid' and 'post_mime_type'
        wp_update_post( array(
            'ID'             => $attachment_id,
            'guid'           => $webp_url,
            'post_mime_type' => 'image/webp',
        ) );

        // Remove the filter to prevent recursion
        remove_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ), 10 );

        // Regenerate the metadata based on the new WebP file
        $metadata = wp_generate_attachment_metadata( $attachment_id, $webp_file );

        // Re-add the filter
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_attachment_metadata' ), 10, 2 );

        return $metadata;
    }

    /**
     * Convert image to WebP format using Imagick or GD.
     */
    public function convert_to_webp( $file ) {
        $file_info = pathinfo( $file );
        $extension = strtolower( $file_info['extension'] );

        // Only convert if it's a supported image format
        if ( ! in_array( $extension, array( 'jpeg', 'jpg', 'png' ), true ) ) {
            return false;
        }

        // Get settings
        $options = get_option( 'webp_image_optimization_settings' );

        // Check if conversion for this type is disabled
        switch ( $extension ) {
            case 'jpeg':
            case 'jpg':
                if ( ! empty( $options['dont_convert_jpeg'] ) ) {
                    return false; // Skip conversion
                }
                break;
            case 'png':
                if ( ! empty( $options['dont_convert_png'] ) ) {
                    return false; // Skip conversion
                }
                break;
            default:
                return false;
        }

        // Define the path for the WebP file
        $webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

        // Check if WebP file already exists to prevent duplicate conversions
        if ( file_exists( $webp_file ) ) {
            return false; // WebP version already exists
        }

        // Determine WebP quality
        $webp_quality = isset( $options['webp_quality'] ) ? intval( $options['webp_quality'] ) : 80;

        // Use Imagick if available
        if ( class_exists( 'Imagick' ) ) {
            try {
                $image = new Imagick( $file );
                $image->setImageFormat( 'webp' );
                $image->setImageCompressionQuality( $webp_quality );

                // Preserve transparency for PNGs
                if ( in_array( $extension, array( 'png' ), true ) ) {
                    $image->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );
                }

                if ( $image->writeImage( $webp_file ) ) {
                    $image->destroy();
                    return $webp_file;
                }

                $image->destroy();
                error_log( 'WebP Image Optimization: Imagick failed to convert ' . $file . ' to WebP.' );
                return false;
            } catch ( Exception $e ) {
                error_log( 'WebP Image Optimization: Imagick exception for ' . $file . ' - ' . $e->getMessage() );
                // Fallback to GD
            }
        }

        // Fallback to GD
        switch ( $extension ) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg( $file );
                break;
            case 'png':
                $image = imagecreatefrompng( $file );
                if ( !$image ) {
                    error_log( 'WebP Image Optimization: Failed to create image resource from PNG ' . $file );
                    return false;
                }
                // Preserve transparency
                imagepalettetotruecolor( $image );
                imagealphablending( $image, true );
                imagesavealpha( $image, true );
                break;
            default:
                return false;
        }

        if ( ! $image ) {
            error_log( 'WebP Image Optimization: Failed to create image resource from ' . $file );
            return false;
        }

        // Convert the image to WebP and save
        if ( imagewebp( $image, $webp_file, $webp_quality ) ) { // Quality set from settings
            imagedestroy( $image );
            return $webp_file;
        }

        imagedestroy( $image );
        error_log( 'WebP Image Optimization: imagewebp failed for ' . $file );
        return false;
    }

    /**
     * Resize image to specified dimensions.
     */
    public function resize_image( $file, $max_width, $max_height ) {
        $image_info = getimagesize( $file );
        $width      = $image_info[0];
        $height     = $image_info[1];
        $mime_type  = $image_info['mime'];

        if ( $width <= $max_width && $height <= $max_height ) {
            return $file; // No resizing needed
        }

        // Calculate aspect ratio and new dimensions
        $aspect_ratio = $width / $height;
        if ( $width > $height ) {
            $new_width  = $max_width;
            $new_height = $max_width / $aspect_ratio;
        } else {
            $new_width  = $max_height * $aspect_ratio;
            $new_height = $max_height;
        }

        $new_width  = intval( $new_width );
        $new_height = intval( $new_height );

        $image_resized = imagecreatetruecolor( $new_width, $new_height );

        // Load the original image
        switch ( $mime_type ) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg( $file );
                break;
            case 'image/png':
                $image = imagecreatefrompng( $file );
                // Preserve transparency
                imagealphablending( $image_resized, false );
                imagesavealpha( $image_resized, true );
                break;
            default:
                return false;
        }

        if ( ! $image ) {
            return false;
        }

        // Resample the image
        imagecopyresampled( $image_resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

        // Get settings
        $options = get_option( 'webp_image_optimization_settings' );
        $jpeg_quality    = isset( $options['jpeg_quality'] ) ? intval( $options['jpeg_quality'] ) : 90;
        $png_compression = isset( $options['png_compression'] ) ? intval( $options['png_compression'] ) : 6;

        // Save resized image
        switch ( $mime_type ) {
            case 'image/jpeg':
                imagejpeg( $image_resized, $file, $jpeg_quality );
                break;
            case 'image/png':
                imagepng( $image_resized, $file, $png_compression );
                break;
        }

        // Free up memory
        imagedestroy( $image );
        imagedestroy( $image_resized );

        return $file;
    }

    /**
     * Add Convert to WebP button to attachment edit fields
     */
    public function add_attachment_buttons( $form_fields, $post ) {
        // Check if the attachment is an image
        if ( strpos( $post->post_mime_type, 'image/' ) !== false ) {
            $file_info = pathinfo( get_attached_file( $post->ID ) );
            $extension = strtolower( $file_info['extension'] );

            if ( $extension !== 'webp' ) {
                // Add Convert to WebP button
                $form_fields['convert_to_webp'] = array(
                    'label' => esc_html__( 'Convert to WebP', 'webp-image-optimization' ),
                    'input' => 'html',
                    'html'  => '<button type="button" class="button convert-to-webp" data-attachment-id="' . esc_attr( $post->ID ) . '">' . esc_html__( 'Convert to WebP', 'webp-image-optimization' ) . '</button>',
                );
            }

            // Show the file size reduction
            $original_size = get_post_meta( $post->ID, '_original_file_size', true );
            $webp_size     = get_post_meta( $post->ID, '_webp_file_size', true );

            // Calculate the percentage saved
            $percentage_saved = 0;
            if ( $original_size && $webp_size ) {
                $percentage_saved = ( ( $original_size - $webp_size ) / $original_size ) * 100;
            }

            // Only show this data if the image has been converted
            if ( $extension === 'webp' && $original_size && $webp_size ) {
                // Format the sizes
                $formatted_original_size = $this->format_bytes( $original_size );
                $formatted_webp_size     = $this->format_bytes( $webp_size );
                $saved_bytes             = $original_size - $webp_size;
                $formatted_saved_size    = $this->format_bytes( $saved_bytes );

                $form_fields['file_size_saved'] = array(
                    'label' => esc_html__( 'File Size Saved', 'webp-image-optimization' ),
                    'input' => 'html',
                    'html'  => '<p>' . sprintf(
                        esc_html__( 'Original Size: %s, WebP Size: %s, Saved: %s (%.2f%%)', 'webp-image-optimization' ),
                        esc_html( $formatted_original_size ),
                        esc_html( $formatted_webp_size ),
                        esc_html( $formatted_saved_size ),
                        esc_html( $percentage_saved )
                    ) . '</p>',
                );
            }
        }
        return $form_fields;
    }

    /**
     * Format bytes into KB or MB
     */
    private function format_bytes( $bytes ) {
        if ( $bytes >= 1048576 ) {
            $bytes_formatted = number_format( $bytes / 1048576, 2 ) . ' MB';
        } elseif ( $bytes >= 1024 ) {
            $bytes_formatted = number_format( $bytes / 1024, 2 ) . ' KB';
        } else {
            $bytes_formatted = $bytes . ' bytes';
        }
        return $bytes_formatted;
    }

    /**
     * Allow WebP uploads
     */
    public function allow_webp_uploads( $mimes ) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }

    /**
     * Ensure WebP images have the correct MIME type
     */
    public function fix_webp_mime_type( $data, $file, $filename, $mimes ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( 'webp' === $ext ) {
            $data['type'] = 'image/webp';
            $data['ext']  = 'webp';
        }
        return $data;
    }

    /**
     * Register AJAX handler for converting attachments to WebP and replacing the original
     */
    public function ajax_convert_attachment_replace() {
        // Verify nonce
        check_ajax_referer( 'webp_image_optimization_nonce', 'nonce' );

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        // Get attachment ID
        $attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( 'Invalid attachment ID.' );
        }

        // Get file path
        $original_file = get_attached_file( $attachment_id );

        if ( ! $original_file || ! file_exists( $original_file ) ) {
            wp_send_json_error( 'Original file does not exist.' );
        }

        // Get the original file size
        $original_file_size = filesize( $original_file );

        // Perform the conversion using existing function
        $webp_converted_file = $this->convert_to_webp( $original_file );

        if ( ! $webp_converted_file ) {
            wp_send_json_error( 'Conversion to WebP failed. Check error logs for details.' );
        }

        // Ensure WebP file exists and is non-zero
        if ( ! file_exists( $webp_converted_file ) || filesize( $webp_converted_file ) === 0 ) {
            wp_send_json_error( 'WebP file was not created successfully.' );
        }

        // Get the WebP file size
        $webp_file_size = filesize( $webp_converted_file );

        // Save the file sizes to attachment meta
        update_post_meta( $attachment_id, '_original_file_size', $original_file_size );
        update_post_meta( $attachment_id, '_webp_file_size', $webp_file_size );

        // Define paths
        $upload_dir = wp_upload_dir();
        $relative_webp_path = str_replace( $upload_dir['basedir'] . '/', '', $webp_converted_file );
        $webp_url = $upload_dir['baseurl'] . '/' . $relative_webp_path;

        // Update the attachment's '_wp_attached_file' meta to point to WebP
        update_post_meta( $attachment_id, '_wp_attached_file', $relative_webp_path );

        // Update the attachment's 'guid' and 'post_mime_type'
        wp_update_post( array(
            'ID'             => $attachment_id,
            'guid'           => $webp_url,
            'post_mime_type' => 'image/webp',
        ) );

        // Regenerate metadata
        $metadata = wp_generate_attachment_metadata( $attachment_id, $webp_converted_file );
        wp_update_attachment_metadata( $attachment_id, $metadata );

        wp_send_json_success( array( 'webp_url' => $webp_url ) );
    }

    /**
     * Add Convert to WebP button to the Media Library
     */
    public function add_media_buttons( $actions, $post ) {
        if ( strpos( $post->post_mime_type, 'image/' ) !== false ) {
            $file_info = pathinfo( get_attached_file( $post->ID ) );
            $extension = strtolower( $file_info['extension'] );

            if ( $extension !== 'webp' ) {
                $actions['convert_to_webp'] = '<a href="#" class="convert-to-webp button" data-attachment-id="' . esc_attr( $post->ID ) . '">' . __( 'Convert to WebP', 'webp-image-optimization' ) . '</a>';
            }
        }
        return $actions;
    }

    /**
     * Register AJAX handler for converting attachments to WebP from the Media Library
     */
    public function ajax_convert_media_attachment() {
        // Verify nonce
        check_ajax_referer( 'webp_image_optimization_nonce', 'nonce' );

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        // Get attachment ID
        $attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( 'Invalid attachment ID.' );
        }

        // Get file path
        $original_file = get_attached_file( $attachment_id );

        if ( ! $original_file || ! file_exists( $original_file ) ) {
            wp_send_json_error( 'Original file does not exist.' );
        }

        // Perform the conversion using existing function
        $webp_converted_file = $this->convert_to_webp( $original_file );

        if ( ! $webp_converted_file ) {
            wp_send_json_error( 'Conversion to WebP failed. Check error logs for details.' );
        }

        // Ensure WebP file exists and is non-zero
        if ( ! file_exists( $webp_converted_file ) || filesize( $webp_converted_file ) === 0 ) {
            wp_send_json_error( 'WebP file was not created successfully.' );
        }

        // Define paths
        $upload_dir = wp_upload_dir();
        $relative_webp_path = str_replace( $upload_dir['basedir'] . '/', '', $webp_converted_file );
        $webp_url = $upload_dir['baseurl'] . '/' . $relative_webp_path;

        // Update the attachment's '_wp_attached_file' meta to point to WebP
        update_post_meta( $attachment_id, '_wp_attached_file', $relative_webp_path );

        // Update the attachment's 'guid' and 'post_mime_type'
        wp_update_post( array(
            'ID'             => $attachment_id,
            'guid'           => $webp_url,
            'post_mime_type' => 'image/webp',
        ) );

        wp_send_json_success( array( 'webp_url' => $webp_url ) );
    }
}

// Instantiate the plugin class
new WebP_Image_Optimization();
