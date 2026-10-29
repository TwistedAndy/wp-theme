# Twee theme: instructions for agents

This repository holds Twee, a WordPress theme framework built around Advanced Custom Fields (ACF). The theme is in
`theme`. A site copies that folder, renames it, and builds its pages from blocks.

All paths below are relative to the theme folder, `theme`, unless they start with `tests/` or name the repository
root. [README.md](README.md) describes the features and the libraries.

## Priorities

1. **Reuse first.** Start from an existing block, part, function or class. A different color, spacing, text or item
   count is not a reason for a new block.
2. **Put code where its subject is.** A block is a template, a stylesheet and an ACF layout. A feature is a file in
   `includes/theme`. Configuration is in `functions.php`.
3. **Keep it fast.** Read data in bulk with the theme's functions, not record by record. Load a script or a library
   only on the pages that need it.
4. **Keep it simple.** Write the plain form: a variable, an `if`, a loop. Do not add a function, an option or a layer
   for a use nobody has yet.

## Where code goes

| You write | Where |
| --- | --- |
| Assets, image sizes, menus, sidebars, post types and taxonomies | `functions.php` |
| The template of a block | `blocks/<layout>.php` |
| A part that several templates share, such as a card | `parts/<name>.php` |
| A feature of the theme, such as breadcrumbs or the loader | `includes/theme/<subject>.php` |
| Small changes of one site: hooks, filters, permalinks | `includes/theme/custom.php` |
| WooCommerce code, in a shop | `includes/woo/<subject>.php`, templates in `woocommerce/`, styles in `assets/styles/woo.scss` |
| A widget | `includes/widgets/<Name>.php` |
| The ACF field groups | `includes/acf/*.json`, written by ACF |
| The styles of a block | `assets/styles/blocks/<box>.scss` |
| The custom properties | `assets/styles/includes/properties.scss` |
| Global classes, section modifiers and placeholders | `assets/styles/base/`, `assets/styles/elements/` |
| A module that every page loads | `assets/scripts/<module>.js` |
| A library, or a module that only some pages load | `assets/plugins/<name>/`, with an `index.php` |
| Images and icons | `assets/images/`, as `pic_*` and `ico_*` |
| The previews of blocks in the admin | `assets/preview/<layout>.webp` |

- `functions.php` includes every PHP file in `includes/core`, `includes/theme` and `includes/widgets` with
  `tw_app_include()`. A new file there needs no `require`. A shop adds `includes/woo` the same way, inside
  `if (function_exists('WOO'))`.
- `includes/core` is the framework. Change it only to fix the framework, and cover the change with a test in
  `tests/core`.
- Functions of the theme start with `tw_`. Text is translated in the `twee` text domain:
  `__('Show More', 'twee')`.

## Blocks

A page is a list of blocks. A block is a layout of the Flexible Content field `blocks`, in the field group
"Content Blocks", `includes/acf/group_61f667952e55c.json`.

- A page template reads the field and renders it: `echo tw_block_render(get_field('blocks', $object));`.
- `tw_block_render()` includes `blocks/<layout>.php` for each block. The values of the block are in `$block`.
- The "Block Set" layout, `set`, renders the blocks of a `block_set` post. It lets several pages share the same
  blocks.

### The common fields

The field group "Common Fields", `includes/acf/group_61f6630b70b82.json`, is inactive. Its fields exist only to be
cloned into the layouts:

| Field | Subfields | Rendered by |
| --- | --- | --- |
| `contents` | `title`, `tag` (`h1`, `h2` or `h3`), `caption`, `text`, `buttons` | `tw_block_contents($block)` |
| `buttons` | A repeater of `type`, `icon` and `link` | `tw_block_buttons($buttons)` |
| `settings` | `background`, `options` (`hidden`, `border`), `top`, `bottom`, `block_id` | `tw_block_attributes($class, $block)` |

`tw_block_attributes()` turns the settings into the attributes of the section:

- `background`: `is_light` or `is_dark`;
- `top`: `has_top`, `has_top_half` or `has_no_top`;
- `bottom`: `has_bottom`, `has_bottom_half` or `has_no_bottom`;
- `options`: `has_border`;
- `block_id`: the `id` of the section, for a link to it.

A block with the `hidden` option is not shown on the site.

### Creating a block

1. **Look for a block to reuse.** Read `blocks/` and `assets/styles/blocks/` first. Add an option or a modifier to an
   existing block when the difference is small.
2. **Add the layout in ACF.** Open WP Admin → ACF → Field Groups → Content Blocks, and add a layout to `blocks`:
   - The name of the layout is the name of its template: `faq` renders `blocks/faq.php`. Use lowercase letters and
     underscores.
   - The first field is a Clone of `contents` from "Common Fields": display Seamless, no prefix.
   - Then the fields of the block, such as an `items` repeater, an `image` or a `columns` button group.
   - A choice that changes the look of the block is an `options` checkbox, or a button group such as `layout` or
     `columns`.
   - The last field is a Clone of `settings` from "Common Fields": display Seamless, no prefix.

   ACF saves the group to `includes/acf/group_61f667952e55c.json`. Keep that file with the change. Edit the JSON by
   hand only when the admin is not available. Then copy an existing layout, and give every new field and layout a key
   of its own: `field_` or `layout_` and 13 hexadecimal characters.
3. **Write the template**, `blocks/<layout>.php`:

   ```php
   <?php

   if (!empty($block['contents']) and !empty($block['contents']['buttons'])) {
       $buttons = $block['contents']['buttons'];
       unset($block['contents']['buttons']);
   } else {
       $buttons = [];
   }

   $contents = tw_block_contents($block);
   $buttons = tw_block_buttons($buttons);

   ?>
   <section <?php echo tw_block_attributes('faq_box', $block); ?>>

       <div class="fixed">

           <?php echo $contents; ?>

           <?php if (!empty($block['items'])) { ?>
               <div class="items">
                   <?php foreach ($block['items'] as $item) { ?>
                       <div class="item">
                           <h3><?php echo $item['title']; ?></h3>
                           <div class="content"><?php echo $item['text']; ?></div>
                       </div>
                   <?php } ?>
               </div>
           <?php } ?>

           <?php echo $buttons; ?>

       </div>

   </section>
   ```

   - The section has one `_box` class. Its name is the name of the box, which is usually the name of the layout.
   - The order inside `.fixed` is the contents, the items, then the buttons. The code above moves the buttons of
     `contents` below the items. Leave them in `contents` when they belong beside the title.
   - Turn the options into classes: a box modifier goes to the class list of `tw_block_attributes()`, such as
     `is_reversed`. A modifier of an element goes to that element, such as `items_3`.
   - Load what the block needs, where it renders: `tw_asset_enqueue('embla')` for a carousel.
   - Use the shared parts for repeated markup: `tw_app_template('card', ['item' => $item])` renders `parts/card.php`,
     with the keys of the array as its variables.
   - Check a field with `!empty()` before you print it. Every field of a block may be empty.
4. **Write the styles**, `assets/styles/blocks/<box>.scss`, under `.<box>_box`, as "Styles" says. Run `npm run blocks`
   in `assets`. The theme loads `build/blocks/<box>.css` on every page that has the class `<box>_box`. You do not
   enqueue it.
5. **Write a script** only if the block has behavior, as "Scripts" says.
6. **Make the preview.** Put the block on a page, and run `node screens.js http://<project>.test/<page>/?preview`
   from the repository root. The script finds the site in `Z:/`, by the host name. It saves
   `assets/preview/<layout>.webp` into the theme of that site, and ACF Extended shows it in the list of layouts.

## Styles

The styles follow CSS_BOX. The [CSS_BOX Starter Kit](https://github.com/TwistedAndy/starter-kit) has the same folders,
and its `AGENTS.md` holds the full rules with examples. In short:

- Every section is a box: one element with a class that ends in `_box`, such as `.faq_box`. An element has at most one
  `_box` class.
- The box, its file and its root selector match: `.faq_box` → `assets/styles/blocks/faq.scss` → `.faq_box { … }`. All
  the styles of the box are under that root.
- Inner elements have short classes that say what they are: `.contents`, `.content`, `.items`, `.item`, `.image`,
  `.details`, `.title`, `.text`, `.buttons`, `.button`, `.label`, `.field`. Do not repeat the name of the block in
  them. Plain tags, such as `h3` or `p`, take no class.
- Modifiers and states:
  - a box takes `is_*` for its kind, scheme or state, and `has_*` for what it has: `.is_dark`, `.is_reversed`,
    `.has_no_top`;
  - an inner element takes `<element>_<modifier>`: `.button_outline`, `.items_3`, `.media_video`;
  - an inner element takes a plain word for its state: `.active`, `.opened`, `.loading`, `.hidden`;
  - no class starts with `box_`.
- Use the custom properties of `assets/styles/includes/properties.scss` for colors, sizes, gaps, radii and shadows.
  Use `--color-*`, not `--theme-*`, in a block. A dark or light section overrides `--color-*`, and its elements
  follow.
- A value used in one place stays a literal. Do not add a property for it.
- Items take their columns from `.items_1` to `.items_6`, or from `--columns-count` on the `.items` of the block.
- Reuse the placeholders of `assets/styles/elements`, such as `%image`, `%contents` or `%button_outline`. The build
  adds the imports that a block stylesheet needs by itself.
- Use the breakpoint mixins, `desktop_large` to `phone_small`, and `hover` and `hoverless`.
- Nest two or three levels deep inside a box. A child selector names its parent: `& > .caption`.
- The WordPress, WooCommerce and Gravity Forms classes stay as they are. Their styles are in
  `assets/styles/woo.scss` and `assets/plugins/gravity`.

## Scripts

Behavior is written in modules with `Twee.addModule()`. The loader is the plugin `assets/plugins/app`.

```js
Twee.addModule('faq', '.faq_box', function($, wrapper) {

	wrapper.on('click', '.title', function() {
		$(this).closest('.item').toggleClass('opened');
	});

});
```

- A module runs once for each element that matches its selector. Bind it to a `_box`, or to `html` for a behavior of
  the whole page.
- Scope queries and events to the bound element. Do not query the whole document for the behavior of a block.
- The fourth argument lists what the module waits for: other modules, such as `['sticky']`, or the globals of a
  library.
- Values from PHP are in `tw_template`: `tw_template.ajaxurl`, `tw_template.nonce` and the strings. They are set by
  the `localize` function of the `scripts` asset in `functions.php`. Add a new value there.
- Use the helpers of `Twee`: `debounce()`, `throttle()`, `lockScroll()`, `unlockScroll()`, `runOnce()`,
  `setCookie()` and `getCookie()`. After a script adds markup, run `Twee.runModules(wrapper)`.
- A module that every page needs goes in `assets/scripts`, which is joined into `build/scripts.js`.
- A library, or a module that only some blocks need, is a plugin. `assets/plugins/<name>/index.php` returns its files
  and its dependencies:

  ```php
  return [
      'script' => [
          'embla-carousel.umd.js',
          'carousel.js',
      ],
      'deps' => ['app']
  ];
  ```

  The template that needs it loads it with `tw_asset_enqueue('<name>')`.

## PHP

- Indent with tabs. Write `and` and `or`, as the rest of the theme does.
- In a template, write PHP blocks with braces: `<?php if (!empty($item['title'])) { ?> … <?php } ?>`.
- Read data in bulk:
  - `tw_meta_get($type, $id, $key)` reads one value from the preloaded metadata;
  - `tw_meta($type, $key)` returns the values of a key for all records, for a list;
  - the post and term functions of `includes/core` fetch posts, terms and their relations at once.
- A Repeater, Group or Flexible Content field is stored in one row. `get_field()` and `get_post_meta()` both return
  it as an array.
- Print images with `tw_image()`, `tw_image_link()` or `tw_image_attribute()`. Use an image size from
  `tw_image_sizes()` in `functions.php`, or add a hidden size for a size that only a few blocks need.
- Print links with `tw_content_link($link, $class)`. It takes the array of an ACF link field.
- Add structured data with `tw_microdata_add()`, as `blocks/faq.php` does.

## Building and testing

Run these in `theme/assets`:

| Command | Does |
| --- | --- |
| `npm run build` | Builds all the styles and scripts. |
| `npm run blocks` | Builds the stylesheets of the blocks, `build/blocks/*.css`. |
| `npm run theme` | Builds `build/base.css` and `build/other.css`. |
| `npm run scripts` | Builds `build/scripts.js`. |
| `npm run watch` | Builds again on each change. |

The built files in `assets/build` and `assets/plugins` are kept in git. Never edit them by hand.

The tests of the framework are in `tests/`. Run them in the repository root:

```bash
vendor/bin/phpunit --no-coverage
```

They need the database that `tests/config.php` names, and they wipe it.

## Before you finish

- The block has a layout in ACF, a template, and a stylesheet named after its box.
- The ACF JSON in `includes/acf` is part of the change.
- No element has two `_box` classes, and no class starts with `box_`.
- The styles are scoped under the box, and use the custom properties.
- Every field is checked before it is printed.
- A script or library loads only where it is needed.
- `npm run build` succeeds, and the built files are part of the change.
- The preview of a new block is in `assets/preview`.
