# Twee 4.3

**Twee** is a WordPress theme framework for large, fast sites. It is built around Advanced Custom Fields (ACF). It
stores ACF data in fewer database rows, reads metadata in bulk and from the object cache, and loads only the styles
that a page uses.

The theme in the `theme` folder is a starting point. Copy it, rename it, and build the site on it. Its markup and styles
follow the CSS_BOX approach of the [CSS_BOX Starter Kit](https://github.com/TwistedAndy/starter-kit), and its `AGENTS.md`
holds the rules for markup, styles and scripts.

## Features

### Metadata storage for ACF

- A Repeater, Group, Flexible Content or Clone field is stored in one metadata row, not in a row for each subfield.
- The value of such a field can be read as an array with `get_post_meta()`.
- The field keys of a record are stored in one row, not in a row for each field.
- All of this works through ACF filters, so the ACF API, ACF add-ons and revisions keep working.

### Metadata, posts and terms

- The metadata API is compatible with WordPress, but faster. It preloads metadata into memory and uses the WordPress
  metadata cache.
- An update writes to the database only when the value has changed.
- Metadata for many records is read in one query, split into partitions, and searched with a binary search.
- The post functions fetch the data of many posts at once, to avoid N+1 queries. They fetch the terms of posts and the
  posts of terms in one request.
- The term functions build and cache term trees, flatten them, and find the parents and children of a term.

### Assets

- Scripts and styles are registered in one place, with their dependencies.
- Each box, a section with a class that ends in `_box`, has a stylesheet of its own. A page loads the stylesheet of a
  box only when its HTML has that class.
- The custom properties and the fonts are put inline into the head, so the page renders without waiting for them.
- The version of a file follows its content, so you never bump it by hand.

### Images

- Hidden image sizes are made on the first request, so the uploads folder holds only the sizes that are used.
- `srcset` and `sizes` are built from the layout.
- SVG uploads get correct metadata.
- WebP conversion is built in, and the popular image optimization plugins are supported.

### Other tools

- Blocks for pages, built with the ACF Flexible Content field, with previews in the admin.
- Pagination for custom queries.
- Breadcrumbs with JSON-LD structured data.
- A Load More button for lists of posts.
- Accessibility fixes for menus.
- A base class for widgets.
- A logger that is compatible with WooCommerce.
- Unit tests for the core libraries.

## Requirements

- WordPress, with PHP 8.0 or newer.
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/).
- [Advanced Custom Fields: Extended](https://wordpress.org/plugins/acf-extended/), for the previews of blocks in the admin.
- [Node.js](https://nodejs.org/) 16 or newer, to build the assets.
- An object cache, such as Redis, is recommended.

## Installation

1. Copy the `theme` folder into `wp-content/themes`, and rename it.
2. Change the theme name in `style.css`, and add a `screenshot.jpg` to the theme folder.
3. Install and activate ACF Pro, and ACF Extended if you want block previews.
4. Activate the theme.
5. Sync the field groups: WP Admin → ACF → Field Groups → Sync available → Sync. The groups are stored as JSON in
   `includes/acf`. See [Synchronized JSON](https://www.advancedcustomfields.com/resources/synchronized-json/).
6. Install the packages for the assets:

   ```bash
   cd assets
   npm install
   ```

## Folders

| Path | Holds |
| --- | --- |
| `functions.php` | The configuration of the theme: assets, image sizes, menus, post types and taxonomies. |
| `includes/core/` | The core libraries: app, asset, content, image, logger, meta, post, term and widget. |
| `includes/theme/` | The features of the theme: ACF, blocks, breadcrumbs, gallery, the loader, pagination, previews and others. |
| `includes/acf/` | The ACF field groups, as JSON. |
| `includes/widgets/` | The widgets of the theme. |
| `blocks/` | One template for each layout of the Flexible Content field, such as `media.php`. |
| `parts/` | The parts that templates share: the header, the footer, buttons, contents and posts. |
| `assets/styles/` | The styles: properties, base styles, elements and one file for each box in `blocks/`. |
| `assets/scripts/` | The modules that every page loads. |
| `assets/plugins/` | Libraries and the modules that only some pages load, each with an `index.php`. |
| `assets/build/` | The built styles and scripts. Do not edit them by hand. |
| `assets/preview/` | The previews of blocks, `<layout>.webp`, shown in the admin. |

## Configuration

Everything is configured in `functions.php`.

### Scripts and styles

`tw_asset_register()` registers one or more assets. An asset is a set of scripts, styles and the data they need:

```php
tw_asset_register([
	'base' => [
		'style' => 'base.css',
		'footer' => false,
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

The keys of an asset:

- `script` and `style` name a file, or a list of files, in `directory`.
- `deps` lists the assets or WordPress scripts that load first.
- `display` set to `true` loads the asset on every page. Any other asset is loaded with `tw_asset_enqueue('<name>')`.
- `footer` loads the asset at the end of the page.
- `localize` is an array, or a function that returns one. Its data is available to scripts as the object named in
  `object`.
- `inline` holds styles, or a function that returns them, that are put inline when the asset is loaded.

The theme also registers by itself:

- each folder in `assets/plugins`, as an asset of the same name. Its `index.php` returns the keys above;
- each stylesheet in `assets/build/blocks`, as the asset `<name>_box`. It loads when a page has that class.

### Image sizes

`tw_image_sizes()` adds an image size, or changes an existing one. A size with `'hidden' => true` is not registered in
WordPress. It is made and cached the first time `tw_image()`, `tw_image_link()` or `tw_image_attribute()` asks for it.
Use it for a size that only a few sections need.

### Post types, taxonomies, menus and widgets

- `tw_app_type()` and `tw_app_taxonomy()` register post types and taxonomies. They take the same arguments as
  [register_post_type()](https://developer.wordpress.org/reference/functions/register_post_type/) and
  [register_taxonomy()](https://developer.wordpress.org/reference/functions/register_taxonomy/), and register them at
  the right time.
- `tw_app_menus()` registers menus, and `tw_app_sidebar()` registers sidebars.
- `tw_app_widget()` registers a widget by its class name. See `includes/widgets/Posts.php`.

## Blocks

A page is built from blocks: the layouts of an ACF Flexible Content field.

- `tw_block_render($blocks)` renders the blocks. Each layout has its template, `blocks/<layout>.php`.
- A template writes its section with `tw_block_attributes('<name>_box', $block)`. This adds the classes that the
  settings of the block choose:
  - the background: `is_light` or `is_dark`;
  - the top spacing: `has_top`, `has_top_half` or `has_no_top`;
  - the bottom spacing: `has_bottom`, `has_bottom_half` or `has_no_bottom`;
  - the border: `has_border`.
- `tw_block_contents()` renders the title, the text and the buttons of a block. `tw_block_buttons()` renders the
  buttons alone.
- A block marked as hidden is not shown on the site.

### Previews

ACF Extended shows a preview image for each layout, `assets/preview/<layout>.webp`. `screens.js` makes them with
Puppeteer from a page of the site that shows the blocks:

```bash
node screens.js http://project.test/?preview
```

The URL must end in `.test`. The script finds the project in `Z:/` by its host name. A second argument sets the
selector of the sections, and a third sets the theme folder when its name differs from the host.

## Building the assets

Run these in `theme/assets`:

| Command | Does |
| --- | --- |
| `npm run watch` | Builds again on each change. |
| `npm run build` | Builds all the styles and scripts. |
| `npm run theme` | Builds the base styles, `build/base.css` and `build/other.css`. |
| `npm run blocks` | Builds a stylesheet for each box in `build/blocks`. |
| `npm run woo` | Builds the WooCommerce styles, `build/woo.css`. |
| `npm run plugins` | Builds the styles in `plugins`. |
| `npm run scripts` | Joins the modules in `scripts` into `build/scripts.js`. |
| `npm run imagemin` | Compresses the images. It needs `imagemin-cli`, installed globally. |
| `npm run svgo` | Minifies the SVG icons. It needs `svgo`, installed globally. |

The build of a box stylesheet adds the imports it needs by itself: the mixins, and the elements that it extends.

## Scripts

Behavior is written in modules with `Twee.addModule()`. The loader is the plugin `assets/plugins/app`:

```js
Twee.addModule('faq', '.faq_box', function($, wrapper) {

	wrapper.on('click', '.title', function() {
		$(this).closest('.item').toggleClass('opened');
	});

});
```

A module runs once for each element that matches its selector, including the elements that a script adds later with
`Twee.runModules()`. The `AGENTS.md` of the starter kit describes the rules in full.

## Libraries

The functions are grouped by subject in `includes/core` and `includes/theme`. The most used ones:

- **Asset**: registers and loads assets, resolves their dependencies, and puts styles inline. `tw_asset_enqueue()`
  loads an asset on the page.
- **Content**: works with titles, text, links and dates.
- **Image**:
  - `tw_image()` returns an image tag with its wrappers and `srcset`. It supports hidden sizes, and is much faster than
    the WordPress functions.
  - `tw_image_link()` returns only the link of an image.
  - `tw_image_attribute()` returns an image as a custom property, such as a background or a mask.
- **ACF**: stores Repeater, Group and Flexible Content fields in one row each, and the field keys in one row.
- **ACFE**: customizes ACF and integrates ACF Extended.
- **Block**: renders the blocks of the Flexible Content field and their common fields.
- **Meta**: reads, updates and deletes metadata through the cache, and maps the metadata of many records at once.
- **Post**: maps post data, the terms of posts and the threads of terms.
- **Term**: builds and flattens term trees, and finds parent and child terms, the posts of terms and term links.

## Tests

The tests use the WordPress test library, and run against a database that they wipe. Set its connection in
`tests/config.php` or `phpunit.xml.dist`. Then install the packages and run the tests in this folder:

```bash
composer install
composer test
```

`composer test` loads the `pcov` extension for the coverage report. Without it, run `vendor/bin/phpunit --no-coverage`.

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
