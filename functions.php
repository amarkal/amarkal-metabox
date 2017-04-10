<?php
/**
 * WordPress Metabox
 *
 * A set of utility functions for creating WordPress metaboxes.
 * This is a module within the Amarkal framework.
 *
 * @package   amarkal-metabox
 * @depends   amarkal-ui
 * @author    Askupa Software <hello@askupasoftware.com>
 * @link      https://github.com/askupasoftware/amarkal-metabox
 * @copyright 2017 Askupa Software
 */

// Prevent direct file access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Prevent loading the library more than once
 */
if( defined( 'AMARKAL_METABOX' ) ) return false;
define( 'AMARKAL_METABOX', true );

if(!function_exists('amarkal_add_meta_box'))
{
    /**
     * Add a meta box to a given post type
     * 
     * @param string $id
     * @param array $args
     */
    function amarkal_add_meta_box( $id, array $args )
    {
        $mb = Amarkal\Metabox\Manager::get_instance();
        $mb->add( $id, $args );
    }
}

if(!function_exists('amarkal_get_meta_box_value'))
{
    /**
     * Get the value of the given field, optionally returning the default value
     * if none was set.
     * 
     * @param string $metabox_id
     * @param string $name
     * @param number $post_id
     * @return mix
     */
    function amarkal_get_meta_box_value( $metabox_id, $name, $post_id )
    {
        $mb = Amarkal\Metabox\Manager::get_instance();
        return $mb->get_meta_box_value( $metabox_id, $name, $post_id );
    }
}