# Twee

**Twee** is the advanced WordPress starter theme focused on speeding up and simplifying the custom theme development.


## Main features
* Built-in asset management system allowing you to manage your scripts, stylesheets and localization strings in an easy way
* Resizing and caching the thumbnails. It may be useful when you need to output the thumbnail with the custom size and don't want to register it in the system
* Widget class to simplify the custom widget creation
* Libraries to work with taxonomies, terms, and content
* Breadcrumbs with built-in JSON-LD and microdata support
* Pagination for posts, pages, and comments with custom query support
* AJAX modules to load more posts and process the contact form
* All the configuration is placed in one file. It allows you to specify all your image sizes, assets, menu locations, sidebars, custom post types, taxonomies and other things in one place


## Usage

Custom theme development begins with the configuration in **`settings.php`** file. All the theme settings are divided into several groups:


### Menu locations

```php
$settings['menu'] = array(
	'main' => 'Main menu',
	'footer' => 'Footer',
);
```

You can specify as many menu locations as you want. The array key is the menu location name, the value is the label.


### Thumbnail sizes

```php
$settings['thumbs'] = array(
	'post' => array(
		'label' => 'Category',
		'width' => 240,
		'height' => 180,
		'thumb' => true,
		'crop' => array('center', 'center')
	),
	'slide' => array(
		'width' => 500,
		'height' => 360,
		'hidden' => true
	)
);
```

Each array represents one thumbnail size. Let's take a look at each setting.

Array key (`post`, for example) is the size name
 
`label` - the label for the size displayed in WordPress Media editor

`width` and `height` - the thumbnail dimensions in pixels

`crop` - the cropping method. See [add_image_size](https://developer.wordpress.org/reference/functions/add_image_size/) for details. Default: `true`.

`thumb` - use this size for the default WordPress thumbnails. See [set_post_thumbnail_size](https://codex.wordpress.org/Function_Reference/set_post_thumbnail_size) for details. Default: `false`.

`hidden` - mark the image size as hidden. If you set this value to `true` the image size will **NOT** be registered in the WordPress, but you can still use it in `tw_thumb` function. In this case, a thumbnail will be created and cached on the first function call. This may be useful when you don't want to register an additional image size for all the attachments. Default: `false`. 


### Assets

```php
$settings['assets'] = array(
	'template' => array(
		'deps' => array('jquery'),
		'style' => array(
            'css/style.css',
        ),
		'script' => array(
            'scripts/theme.js',
        ),
		'localize' => array(
			'ajaxurl' => admin_url('admin-ajax.php')
		),
		'footer' => true,
		'display' => true
	),
	'nivo' => true,
	'styler' => array(
		'style' => '',
		'display' => true
	)
);
```

One item in the array represents one asset. The key is the asset name, the value may be a boolean value or an array. Let's take a closer look how it works.

Each array may have this fields:

`deps` - an array of registered script and style handles this asset depends on. Possible values:

- Name of the registered asset, script or style
- Array with names
- Array with 'script' and/or 'style' elements:

```php
'deps' => array(
    'script' => 'jquery',
    'style' => array('dashicons', 'wp-color-picker')
)
``` 
 
 Default value:
 ```php
'deps' => array(
	'style' => array(),
	'script' => array()
),
```

`style` - a string or an array of strings with the path to stylesheet files. The files will be enqueued in the same order as you specify them. Default: `''`

`script` - a string or an array of strings with the path to JavaScript files. Default: `''`

`localize` - an array with JavaScript data. It will be available as `$asset_name` array (`template.ajaxurl`, for example). See [wp localize script](https://codex.wordpress.org/Function_Reference/wp_localize_script). Also you can specyfy the function that returns an array. It will be called on script enqueuing. Default: `array()`
 
`footer` - output the asset in the footer. Default: `true`

`version` - the asset version number. Default: `null`

`display` - enqueue the asset. If you set this value to `false` all the asset scripts and styles will be registered, but not enqueued. In this case you can enqueue them manually using the `tw_enqueue_asset` or the default `wp_enqueue_script` and `wp_enqueue_style` functions. Also you can specify the function that returns `true` or `false`. It will be called on script enqueuing. Default: ``false``

Each asset may have the default configuration in `assets/plugins/{$asset_name}/index.php`. If this file exists, the asset will be automatically registered. The default settings can be modified by specifying an array in this configuration. Also you can specify the `true` value just to enqueue the asset.


### Sidebars, custom post types, and taxonomies

You can specify the arrays with settings in `$settings['sidebars']`, `$settings['types']` and `$settings['taxonomies']`. The settings are described here: [register_sidebar](https://codex.wordpress.org/Function_Reference/register_sidebar), [register_post_type](https://codex.wordpress.org/Function_Reference/register_post_type) and [register_taxonomy](https://codex.wordpress.org/Function_Reference/register_taxonomy)


### Custom editor styles

```php
$settings['styles'] = array(
	array(
		'title' => 'Custom style',
		'block' => 'div',
		'classes' => 'custom_class',
		'wrapper' => true,
	),
);
```

You can specify your own styles for TinyMCE editor. If you create the `editor-style.css` stylesheet and place it in the root folder of your theme it will be automatically included to the pages with the editor. See [TinyMCE Custom Styles](https://codex.wordpress.org/TinyMCE_Custom_Styles) documentation for more details.


### AJAX handlers and modules

```php

$settings['modules'] = array(
	'acf' => array(
		'require_acf' => true,
		'json_enable' => true,
		'option_page' => true,
		'category_rules' => true,
		'include_subcats' => true,
	),
	'actions' => array(
		'caption_padding' => 0,
		'menu_clean' => false,
		'menu_active' => true,
		'fix_caption' => true,
		'clean_header' => true,
		'comment_reply' => false
	),
	'breadcrumbs' => array(
		'microdata' => 'json',
		'include_archive' => false,
		'include_current' => true
	),
	'cyrdate' => array(
		'english_convert' => false
	),
	'blocks' => array(
		'option_field' => 'blocks_default',
		'load_default' => true,
	),
	'pagination' => array(
		'prev' => '&#9668;',
		'next' => '&#9658;',
		'first' => false,
		'last' => false,
	),
);
```

All the AJAX handlers and modules are located in a separate files in `includes/ajax` and `includes/modules` folders. They are included automatically. Use the function `tw_get_setting` to get the module settings (`tw_get_setting('modules', 'pagination', 'first')`, for example). Also you can place some of your custom code in `custom.php` file.


### Widgets

```php
$settings['widgets'] = array(
	'posts' => false,
	'comments' => false
);
```

Widgets are located in `includes/widgets` folder. They will be included when the item value is set to `true`. The custom widget class should be named as `Twisted_Widget_{$file_name}` (`Twisted_Widget_Posts`, for example).

That's all. Now you're ready to build your own custom theme using **Twee**.


## About

Author: Toniievych Andrii (or *Toniyevych Andriy* in other transliteration)

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