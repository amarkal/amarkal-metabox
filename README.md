# amarkal-metabox [![Build Status](https://scrutinizer-ci.com/g/amarkal/amarkal-metabox/badges/build.png?b=master)](https://scrutinizer-ci.com/g/amarkal/amarkal-metabox/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/amarkal/amarkal-metabox/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/amarkal/amarkal-metabox/?branch=master) [![License](https://img.shields.io/badge/license-GPL--3.0%2B-red.svg)](https://raw.githubusercontent.com/amarkal/amarkal-metabox/master/LICENSE)
Add metaboxes with [amarkal-ui](https://github.com/amarkal/amarkal-ui) components to any post type in WordPress.

**Tested up to:** WordPress 4.7  
**Dependencies**: *[amarkal-ui](https://github.com/amarkal/amarkal-ui)*

![amarkal-metabox](https://askupasoftware.com/wp-content/uploads/2015/04/amarkal-metabox.png)

## Overview
**amarkal-metabox** lets you add metaboxes to any post type (posts, pages & custom post types) using UI components from [amarkal-ui](https://github.com/amarkal/amarkal-ui/).

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

Download [amarkal-ui](https://github.com/amarkal/amarkal-ui/archive/master.zip) and [amarkal-metabox](https://github.com/amarkal/amarkal-metabox/archive/master.zip) from github and include them in your project.

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
This function can be used to add metaboxes to a given post type, and it uses arguments similar to WordPress' [`add_meta_box()`](https://developer.wordpress.org/reference/functions/add_meta_box/). However, as oppose to `add_meta_box()`, this function accepts a list of UI fields, which it will render and handle the saving process of. See [amarkal-ui](https://github.com/amarkal/amarkal-ui/) for supported field types, or register your own field type using `amarkal_ui_register_component`.

**Parameters**  
* `$id` (*String*) Specifies metabox's ID.
* `$args` (*Array*)  Specifies a list of metabox arguments:
  * `title` (*String*)  Specifies the title of the meta box.
  * `screen` (*String|Array|WP_Screen*) Specifies the screen or screens on which to show the box (such as a post type, 'link', or 'comment'). Accepts a single screen ID, WP_Screen object, or array of screen IDs. Defaults to `null`.
  * `context` (*String*) Specifies the context within the screen where the boxes should display. Available contexts vary from screen to screen. Post edit screen contexts include 'normal', 'side', and 'advanced'. Comments screen contexts include 'normal' and 'side'. Menus meta boxes (accordion sections) all use the 'side' context. Defaults to 'advanced'.
  * `priority` (*String*) Specifies the priority within the context where the boxes should show ('high', 'low'). Defaults to `'default'`.
  * `fields` (*Array*) Array of arrays. Specifies a list of [amarkal-ui](https://github.com/amarkal/amarkal-ui) component array arguments. Each array should have the original UI component arguments as specified in [amarkal-ui](https://github.com/amarkal/amarkal-ui), as well as the following arguments:
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
            'description' => 'The page\'s subtitle.',
            'default'     => 'Some default value',
            'filter'      => function($v) {
                return sanitize_text_field($v);
            },
            'validation'  => function($v,&$e) {
                return true;
            }
        )
    )
));

// Then you can retrieve the data using:
$subtitle = amarkal_get_meta_box_value( 'my_meta_box', 'page_subtitle', $post_id );
```

### amarkal_get_meta_box_value
*Get the value of a given field, optionally returning the default value if no value exists in the database.*
```php
amarkal_get_meta_box_value( $metabox_id, $name, $post_id )
```
This function can be used to retrieve the value of a given meta field and a post id. If there is no value in the database for the given meta field and post id, the default field value will be returned.

**Parameters**  
* `$metabox_id` (*String*) Specifies the metabox's ID.
* `$name` (*String*)  Specifies the field's name.
* `$post_id` (*Number*)  Specifies the post's ID.

**Example Usage**
```php
$value = amarkal_get_meta_box_value( 'my_meta_box', 'field_name', $post_id );
```
