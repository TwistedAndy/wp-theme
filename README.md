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