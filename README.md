# Twee 4.2

**Twee** is a powerful WordPress theme framework built for high-performance, large-scale projects. It is finely tuned for Advanced Custom Fields, features an
alternative metadata storage API, and leverages object caching to minimize database load and maximize speed.

## Features

### 1. Ultrafast Metadata Storage for Advanced Custom Fields

- Stores metadata for Repeater, Group, or Clone fields in a single row instead of multiple rows
- Fully compatible with the WordPress Metadata API, even for complex field types
- Stores field keys in a single field, instead of duplicating keys for each row as in the default ACF behavior
- Fully supports the ACF API and all ACF plugins
- Enables dynamic rendering for ACF Flexible Content fields when using the ACF Extended plugin
- Supports automatic section preview generation for ACF Flexible Content fields via Puppeteer

### 2. Highly Optimized Metadata API

- Includes a performance-optimized metadata API that remains fully compatible with WordPress
- Reduces heavy database queries by preloading metadata into memory
- Uses cache partitioning to minimize data transfer from cache
- Implements advanced techniques like binary search to speed up metadata access
- Fully integrates with WordPress metadata caching
- Performs fast metadata updates by checking for changes before writing to the database

### 3. High-Performance Post and Term Libraries

- Enables bulk fetching of post data to avoid N+1 query issues
- Retrieves post terms and term posts in a single request for improved performance
- Builds and caches term trees, with the ability to flatten them if needed
- Includes term threading to easily retrieve full parent-child term hierarchies
- Supports fetching post IDs associated with specific terms

### 4. Advanced Asset Management

- Automatically scans, resolves, and registers assets and their dependencies
- Includes only required styles based on section class names
- Injects essential base styles and fonts into the head for improved loading speed
- Automates versioning to eliminate manual version bumps when unnecessary
- Loads required assets for Flexible Content section previews automatically

### 5. Efficient Image Processing and Thumbnail Generation

- Supports hidden image sizes that generate thumbnails on demand to avoid clutter
- Integrates with popular image minification plugins for optimized output
- Allows SVG uploads and generates proper metadata
- Automatically generates correct srcset and sizes based on layout settings
- Uses high-performance caching for faster thumbnail generation
- Provides built-in WebP conversion support

### 6. Additional Tools and Utilities

- Includes a custom pagination library with support for custom queries
- Offers a breadcrumbs library with JSON-LD structured data support
- Fixes common accessibility issues in menus
- Built-in Load More post handler for dynamic content loading
- Integrated logging system with WooCommerce compatibility
- Provides a base class for building widgets
- Centralized configuration within functions.php for easy management

### 7. Build Tools and Styles

- Uses Gulp to build all assets, automatically generating separate block styles, injecting CSS properties into the head, and applying additional optimizations
- Assumes that all styles for a single block are located in one SCSS or CSS file, with the base class ending in _box. Learn more about this approach
  here: [CSS_BOX: A Better Way to Write CSS](https://www.reddit.com/r/css/comments/1apomz1/css_box_a_better_way_to_write_css/)
- The HTML/CSS starter kit is available in a separate repository: [CSS_BOX Starter Kit](https://github.com/TwistedAndy/starter-kit)
- Uses the Bigger Picture library for popup images and videos: [Bigger Picture](https://github.com/TwistedAndy/bigger-picture). This library is optimized for
  maximum browser performance and a smooth user experience

## Installation

- Install a theme framework as a regular theme by copying the theme folder.
- Rename the theme folder as needed and add a **screenshot.jpg** file to the root.
- Install the [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) plugin
- If you plan to use section previews in WP Admin, also install [Advanced Custom Fields: Extended](https://wordpress.org/plugins/acf-extended/)
- Open WP Admin -> ACF -> Field Groups -> Sync available -> Sync ([read more](https://www.advancedcustomfields.com/resources/synchronized-json/))

## Configuration

All configurations are located in functions.php.

### Scripts and Styles

Theme scripts and styles can be registered using the ```tw_asset_register()``` function.

It supports registering multiple assets at once:

```php
tw_asset_register([
	'base' => [
		'style' => 'base.css',
		'inline' => '',
		'footer' => false,
		'display' => true,
		'directory' => 'build'
	],
	'other' => [
		'style' => 'other.css',
		'footer' => true,
		'display' => true,
		'directory' => 'build'
	],
	'scripts' => [
		'footer' => true,
		'display' => true,
		'deps' => ['jquery', 'app'],
		'script' => 'scripts.js',
		'object' => 'tw_template',
		'directory' => 'build',
		'localize' => function() {
			return [
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('ajax-nonce')
			];
		}
	]
]);
```

Note that the script and style properties support both file names in the specified directory and arrays of file names.

The directory is specified using the ```'directory'``` parameter.

Assets marked with ```'display' => true``` will be enqueued automatically.

The others can be enqueued using the ```tw_asset_enqueue(...)``` call.

If you need to include additional JavaScript data, specify it for the ```'localize'``` property as an array or as a function returning an array.

The data will be available under the object specified in the ```'object'``` property.

If you need to inject some inline styles added when the main asset is enqueued, use the ```'inline'``` property. It supports both the styles and a callback returning styles.

Additionally, the script automatically registers all the assets located in the ```assets/plugins``` folder. The data is pulled from the ```index.php``` files.

### Image Sizes

Use the ```tw_image_sizes()``` function to register a new thumbnail size or redefine an existing one.

Some thumbnail sizes can be marked as hidden with the ```'hidden' => true``` flag. In this case, they will not be registered in WordPress but will be generated and cached on the first call in ```tw_image()```, ```tw_image_link()```, ```tw_image_attribute()``` and similar functions.

This feature is extremely useful when you need an image in a special size for a few sections on the site and do not want to bloat the uploads folder with additional image sizes.

### Post Types and Taxonomies

Custom post types can be registered using ```tw_app_type()``` and ```tw_app_taxonomy()``` functions.

Their arguments are almost identical to [register_post_type()](https://developer.wordpress.org/reference/functions/register_post_type/) and [register_taxonomy()](https://developer.wordpress.org/reference/functions/register_taxonomy/). They are registered at the correct time automatically.

### Sidebars, Menus, and Widgets

They can be registered using ```tw_app_sidebar()```, ```tw_app_menus()```, and ```tw_app_widget()``` functions.

In the case of widgets, the function expects to have a class name of the custom widget class. See ```includes/widgets/posts.php``` as an example.

## Usage

This theme framework includes a huge library of helper functions grouped by the context. You may find them in the ```includes/core``` and ```includes/theme``` folders.

Here are some most useful libraries:

### Asset

It includes functions to work with assets (a set of script, styles, and related data). It resolves the dependencies, support lazy loading, and styles injecting. The most useful function is ```tw_asset_enqueue()```, which includes the specified asset to the page.

### Content

This library contains a set of functions to work with title, text, link, dates, etc.

### Image

It includes functions to work with images. The most used ones are:
- ```tw_image()```. Return the image tag with optional wrappers, source sets, etc. It supports usage of hidden image sizes and optimized for maximum speed and compatibility. It is much faster than the built-in WordPress functions.
- ```tw_image_link()```. It's useful when you need to have only an image link.
- ```tw_image_attribute()```. This function is used mostly to inject the background images or mask images.

### ACF

This library includes optimizations for Advanced Custom Fields (ACF) data. It allows complex fields like Repeater, Group, or Flexible Content fields to be stored in a single metadata row instead of multiple rows. This approach makes ACF fields compatible with the WordPress Metadata API.

For example, if you have a repeater field, you can simply get its value as an array using the ```get_post_meta()``` call.

Another important feature is optimized field key storage. By default, ACF stores each field key in a separate row. So, if you have 20 ACF fields, there will be at least 40 metadata rows. This library optimizes the process by storing all field keys in one field as an array.

All these optimizations use ACF filters, ensuring full compatibility with the ACF plugin and related plugins. Revisions are also supported.

### ACFE

This library includes a host of ACF customizations and manages integration with the ACF Extended plugin.

### Block

It offers functions to handle page builders based on the Flexible Content field, along with helper functions to process common fields like Content or Buttons.

### Metadata

This library provides a set of functions to speed up metadata processing:

- Optimized functions to get, update, or remove metadata, which rely on caching and operate much faster than default WordPress implementations.
- Metadata mapping functions, useful when you need metadata for a large number of records. Instead of making a separate database query for each record, it
  retrieves the metadata for all records, splits it into partitions, and uses a B-tree index for faster searching.
- Cache processing logic to ensure all optimizations remain reliable.

### Post

This library provides tools to work with post data. It includes post data mapping functions, a post terms library, a term thread builder, and other functionality essential for performance optimization.

### Term

This library includes functions for working with terms, such as building and flattening a term tree, retrieving parent and child terms, accessing posts associated with specific terms, getting term links, and more. It's crucial for achieving optimal performance.

### Others

In addition to the previously mentioned libraries, there is a wide range of other essential functionalities designed to accelerate development and significantly enhance site performance.

## About

Author: Andrii Toniievych

Contact: [toniyevych@gmail.com](mailto:toniyevych@gmail.com)

Feel free to contact me if you have any questions.


## Contribution

* Fork this repository
* Commit your changes
* Push it to the branch
* Create the new pull request


## License

**Twee** is released under the MIT Public License.

Note: The "About" section in `README.md` and the author (`@author`) notice in the file-headers shall not be edited or deleted without permission. Thank you!