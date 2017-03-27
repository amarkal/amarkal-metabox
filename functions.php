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
    function amarkal_add_meta_box( $id, $args )
    {
        $mb = Amarkal\Metabox\Manager::get_instance();
        $mb->add( $id, $args );
    }
}