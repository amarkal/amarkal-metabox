<?php

namespace Amarkal\Metabox;

/**
 * Metabox Manager adds metaboxes to WordPress posts
 */
class Manager
{
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instance;
    
    /**
     * @var Array Stores all the registered metaboxes
     */
    private $metaboxes = array();
    
    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function get_instance()
    {
        if( null === static::$instance ) 
        {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * Private constructor to prevent instantiation
     */
    private function __construct() 
    {
        $this->init();
    }
    
    /**
     * Add a metabox.
     * 
     * @param string $id
     * @param array $args
     * @throws \RuntimeException if the given metabox id has already been registered
     */
    public function add( $id, array $args )
    {
        if( \array_key_exists($id, $this->metaboxes) )
        {
            throw new \RuntimeException("A metabox with id '$id' has already been registered.");
        }
        $this->metaboxes[$id] = new Metabox($id, $args);
    }
    
    /**
     * Render a metabox.
     * 
     * @param WP_Post $post
     * @param array $args
     */
    public function render( $post, $args )
    {
        $metabox = $this->metaboxes[$args['id']];
        $metabox->render($post->ID);
    }
    
    /**
     * Internally used to register metaboxes.
     */
    public function add_meta_boxes()
    {
        foreach( $this->metaboxes as $id => $mb )
        {
            \add_meta_box(
                $id,
                $mb->title,
                array($this, 'render'),
                $mb->screen,
                $mb->context,
                $mb->priority
            );
        }
    }
    
    /**
     * Save metaboxes data for a given page.
     * 
     * @param number $post_id
     */
    public function save_meta_boxes( $post_id )
    {
        /**
         * A note on security:
         * 
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times. since metaboxes can 
         * be removed - by having a nonce field in only one metabox there is no 
         * guarantee the nonce will be there. By placing a nonce field in each 
         * metabox you can check if data from that metabox has been sent 
         * (and is actually from where you think it is) prior to processing any data.
         * @see http://wordpress.stackexchange.com/a/49460/25959
         */
 
        $post_type = filter_input(INPUT_POST, 'post_type');
        
        /**
         * Bail if this is an autosave, or if the current user does not have 
         * sufficient permissions
         */
        if( (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) ||
            (null !== $post_type && !current_user_can('edit_'.$post_type, $post_id)) ) 
        {
            return;
        }

        // Update the meta fsields.
        foreach( $this->metaboxes as $id => $metabox )
        {
            $metabox->save($post_id);
        }
    }
    
    /**
     * Get the value of the given field.
     * 
     * @param string $metabox_id
     * @param string $name
     * @param number $post_id
     * @return mix
     */
    public function get_meta_box_value( $metabox_id, $name, $post_id )
    {
        // Check if the meta key exists
        if( in_array($name, \get_post_custom_keys($post_id)) )
        {
            return \get_post_meta( $post_id, $name, true );
        }
        
        // If no meta key exists in the db, use default value
        $component = $this->metaboxes[$metabox_id]->form->get_component_list()->get_by_name($name);
        return $component->default;
    }
    
    /**
     * Print custom metabox style.
     */
    public function print_style() 
    {
        $cs = get_current_screen();
        
        foreach( $this->metaboxes as $metabox )
        {
            if( $metabox->screen === $cs->id )
            {
                echo '<style>';
                include 'metabox.css';
                echo '</style>';
                return;
            }
        }
    }
    
    /**
     * Initiate the metaboxes by adding action hooks for printing and saving.
     */
    private function init()
    {
        \add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        \add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        \add_action( 'admin_footer', array( $this, 'print_style' ) );
    }
}