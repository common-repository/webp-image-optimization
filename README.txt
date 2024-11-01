=== WebP Image Optimization ===

Contributors: adgardner1392  
Tags: webp, image optimization, image conversion, jpeg, png  
Requires at least: 5.0  
Tested up to: 6.6.2  
Requires PHP: 7.2  
Stable tag: 1.4.0
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically converts uploaded JPEG and PNG images to WebP format, resizes them, and allows conversion of existing images directly from the Media Library with detailed file size savings.

== Description ==

**WebP Image Optimization** enhances your website's performance by converting uploaded JPEG and PNG images to the more efficient WebP format. It also allows you to resize images to specified maximum dimensions and adjust image quality and compression settings. With the latest update, you can now convert existing images directly from the Media Library and view detailed file size savings, making the optimization process more insightful and streamlined.

**Key Features:**

- **Automatic Conversion to WebP:**
  - Converts JPEG and PNG images to WebP format upon upload.
  - Option to exclude JPEG and/or PNG images from conversion.

- **Media Library Integration:**
  - **New in v1.4.0:** Added WebP conversion option directly within the Media Library.
  - Easily convert existing images to WebP without re-uploading.
  - **v1.3.1:** Added redirection/refresh when an existing image is converted to WebP in the Media library.

- **Image Resizing:**
  - Resize images that exceed user-defined maximum width and height.
  - Maintains aspect ratio during resizing.

- **Quality and Compression Control:**
  - Set JPEG quality (0-100).
  - Set PNG compression level (0-9).
  - Adjust settings via intuitive sliders or input fields.

- **File Size Savings Display:**
  - **New in v1.4.0:** Displays original and WebP file sizes in a readable format (KB/MB).
  - Shows the amount of file size saved both in bytes and percentage.
  - Enhances transparency of optimization benefits.

- **Settings Page:**
  - Accessible under **Tools > WebP Image Optimization** in the WordPress admin dashboard.
  - User-friendly interface with responsive design.

- **Optimized for Performance:**
  - Uses vanilla JavaScript for settings page interactions.
  - Follows WordPress coding standards and best practices.
  - Minimal impact on server resources.

**Benefits:**

- **Improved Page Load Times:**
  - WebP images are typically smaller than JPEG and PNG, reducing bandwidth usage.
  - Faster image loading enhances user experience.

- **SEO Advantages:**
  - Improved site speed can positively impact search engine rankings.

- **User Control:**
  - Flexible settings allow customization based on specific needs.
  - Ability to maintain original image formats if desired.

- **Enhanced Convenience:**
  - Convert existing images directly from the Media Library, saving time and effort.
  - View detailed file size savings to understand optimization impact.

== Installation ==

1. **Upload the Plugin:**
   - Upload the `webp-image-optimization` folder to the `/wp-content/plugins/` directory.
   - Alternatively, install the plugin through the WordPress plugins screen directly by searching for "WebP Image Optimization".

2. **Activate the Plugin:**
   - Activate the plugin through the 'Plugins' screen in WordPress.

3. **Configure Settings:**
   - Navigate to **Tools > WebP Image Optimization** to access the settings page.
   - Set your desired maximum image dimensions, JPEG quality, PNG compression level, and conversion preferences.

4. **Optimize Existing Images:**
   - Go to the **Media Library**.
   - Select images you wish to convert to WebP.
   - Click the newly added **Convert to WebP** option to optimize your existing images.

5. **Enjoy Optimized Images:**
   - Upload new images via the Media Library or post editor.
   - The plugin will automatically resize and convert images based on your settings.

== Frequently Asked Questions ==

= Does this plugin convert existing images in my media library? =

**Yes, starting from version 1.4.0,** you can convert existing JPEG and PNG images to WebP directly from the Media Library using the new conversion option.

= What happens to the original JPEG or PNG files after conversion? =

By default, the original images remain on the server. You can choose to delete them manually if desired.

= Can I exclude certain image types from conversion to WebP? =

Yes, the settings page allows you to exclude JPEG and/or PNG images from being converted to WebP.

= What if my server doesn't support WebP images? =

The plugin requires the GD library with WebP support enabled on your server. Most modern servers have this capability. If not, contact your hosting provider.

= Will the converted WebP images work on all browsers? =

Most modern browsers support WebP images. For browsers that do not support WebP, the plugin ensures that the original image formats are served as fallbacks.

= Can I adjust the image quality and compression settings? =

Yes, you can set the JPEG quality (0-100) and PNG compression level (0-9) via the settings page to balance between image quality and file size.

= How are file size savings displayed? =

After conversion, the plugin displays the original and WebP file sizes in a readable format (KB/MB) along with the amount of file size saved both in bytes and percentage.

== Screenshots ==

1. **Settings Page - Desktop View**
2. **Settings Page - Mobile View**
3. **Media Library - WebP Conversion Option**
4. **File Size Savings Display**

== Changelog ==

= 1.4.0 =

- **New Feature:** Added the ability to display original and WebP file sizes in a readable format (KB/MB) along with the amount saved in both bytes and percentage.
- Enhanced the Media Library conversion interface to show detailed file size savings.
- Improved user interface for better readability and understanding of optimization benefits.

= 1.3.1 =

- Add redirection/refresh when an existing image is converted to WebP in the Media library.

= 1.3.0 =

- **Media Library Integration:**
  - Added WebP conversion option directly within the Media Library.
  - Enables users to convert existing JPEG and PNG images to WebP without re-uploading them.

= 1.2.0 =

- Amend incorrect URI links.

= 1.1.0 =

- **Security Fixes:**
  - Escaped all dynamic output to comply with WordPress standards.
  - Replaced `_e()` with `esc_html_e()` for translatable strings in settings page.
  - Escaped input values using `esc_attr()` in form fields.
  - Escaped plain text output using `esc_html()` where applicable.
  - Ensured all outputs are properly escaped to prevent XSS vulnerabilities.

= 1.0.0 =

- Initial release.
- Automatic conversion of JPEG and PNG images to WebP format.
- Image resizing based on user-defined maximum width and height.
- Settings page under **Tools** with options for:
  - Maximum image dimensions.
  - JPEG quality and PNG compression levels.
  - Excluding JPEG and/or PNG images from conversion.
- Responsive design with sliders and inputs for settings adjustments.
- Vanilla JavaScript used for settings interactions.
- Follows WordPress coding standards and best practices.

== Upgrade Notice ==

= 1.4.0 =

- **New Feature:** Added the ability to display original and WebP file sizes in a readable format (KB/MB) along with the amount saved in both bytes and percentage.
- Enhanced Media Library conversion interface to provide detailed file size savings information.

= 1.3.0 =

- **New Feature:** Added the ability to convert existing images directly from the Media Library. You can now optimize your existing JPEG and PNG images to WebP without re-uploading them.

= 1.2.0 =

- Amend incorrect URI links.

= 1.1.0 =

- Security improvements to ensure safer operations.

= 1.0.0 =

- Initial release with automatic image conversion and resizing features.

== Roadmap ==

- Implement fallback mechanisms for browsers that do not support WebP.
- Add bulk conversion option for existing images in the media library.
- Provide more granular control over image resizing and quality settings.
- Enhance compatibility with other media-related plugins.
- Introduce advanced reporting on overall file size savings across the website.

== Support ==

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/webp-image-optimization/) or the [GitHub repository](https://github.com/adgardner1392/webp-image-optimization/issues).
