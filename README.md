# amarkal-metabox [![Build Status](https://scrutinizer-ci.com/g/askupasoftware/amarkal-metabox/badges/build.png?b=master)](https://scrutinizer-ci.com/g/askupasoftware/amarkal-metabox/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/askupasoftware/amarkal-metabox/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/askupasoftware/amarkal-metabox/?branch=master) [![License](https://img.shields.io/badge/license-GPL--3.0%2B-red.svg)](https://raw.githubusercontent.com/askupasoftware/amarkal-metabox/master/LICENSE)
A set of utility functions for taxonomies in WordPress.

**Tested up to:** WordPress 4.7  
**Dependencies**: *[amarkal-ui](https://github.com/askupasoftware/amarkal-ui)*

![amarkal-metabox](https://askupasoftware.com/wp-content/uploads/2015/04/amarkal-metabox.png)

## Overview
**amarkal-metabox** lets you add metaboxes to any post type (posts, pages & custom post types) using UI components from [amarkal-ui](https://github.com/askupasoftware/amarkal-ui/).

## Installation

### Via Composer

If you are using the command line:  
```
$ composer require askupa-software/amarkal-metabox:dev-master
```

Or simply add the following to your `composer.json` file:
```javascript
"require": {
    "askupa-software/amarkal-metabox": "dev-master"
}
```
And run the command 
```
$ composer install
```

This will install the package in the directory `vendors/askupa-software/amarkal-metabox`.
Now all you need to do is include the composer autoloader.

```php
require_once 'path/to/vendor/autoload.php';
```

### Manually

Download [amarkal-ui](https://github.com/askupasoftware/amarkal-ui/archive/master.zip) and [amarkal-metabox](https://github.com/askupasoftware/amarkal-metabox/archive/master.zip) from github and include them in your project.

```php
require_once 'path/to/amarkal-ui/bootstrap.php';
require_once 'path/to/amarkal-metabox/bootstrap.php';
```

## Reference

### amarkal_add_meta_box
*Add a meta box to a given post type.*
```php
amarkal_add_meta_box( $id, array $args )
```
This function can be used to add metaboxes to a given post type, and it uses arguments similar to WordPress' [`add_meta_box()`](https://developer.wordpress.org/reference/functions/add_meta_box/). However, as oppose to `add_meta_box()`, this function accepts a list of UI fields, which it will render and handle the saving process of. See [amarkal-ui](https://github.com/askupasoftware/amarkal-ui/) for supported field types, or register your own field type using `amarkal_ui_register_component`.

**Parameters**  
* `$id` (*String*) Specifies metabox's ID.
* `$args` (*Array*)  Specifies a list of metabox arguments:
  * `title` (*String*)  Specifies the title of the meta box.
  * `screen` (*String|Array|WP_Screen*) Specifies the screen or screens on which to show the box (such as a post type, 'link', or 'comment'). Accepts a single screen ID, WP_Screen object, or array of screen IDs. Defaults to `null`.
  * `context` (*String*) Specifies the context within the screen where the boxes should display. Available contexts vary from screen to screen. Post edit screen contexts include 'normal', 'side', and 'advanced'. Comments screen contexts include 'normal' and 'side'. Menus meta boxes (accordion sections) all use the 'side' context. Defaults to 'advanced'.
  * `priority` (*String*) Specifies the priority within the context where the boxes should show ('high', 'low'). Defaults to `'default'`.
  * `fields` (*Array*) Array of arrays. Specifies a list of [amarkal-ui](https://github.com/askupasoftware/amarkal-ui) component array arguments. Each array should have the original UI component properties as specified in [amarkal-ui](https://github.com/askupasoftware/amarkal-ui), as well as the following:
    * `type` (*String*) Specifies the type of the UI component. One of the core `amarkal-ui` components or a registered custom component.
    * `title` (*String*) Specifies the field's title.
    * `description` (*String*) Specifies a short description that will be printed below the field's title.

**Example Usage**
```php
// Add a metabox to the 'page' post type
amarkal_add_meta_box('my_meta_box', array(
    'title'     => 'My Meta Box',
    'screen'    => 'page',
    'context'   => 'normal',
    'priority'  => 'default',
    'fields'    => array(
    	array(
        	'type'        => 'text',
            'title'       => 'Page Subtitle',
            'name'        => 'page_subtitle',
            'description' => 'The page\'s subtitle.'
        )
    )
));

// Then you can retrieve the data using:
$subtitle = get_post_meta( $post_id, 'page_subtitle', true );
```
