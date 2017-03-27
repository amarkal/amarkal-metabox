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
     * Security nonce action
     */
    const NONCE_ACTION = 'amarkal_metabox';
    
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
            static::$instance->init();
        }
        return static::$instance;
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
        if( !in_array($id, $this->metaboxes) )
        {
            $this->metaboxes[$id] = array_merge($this->default_args(), $args);
        }
        else throw new \RuntimeException("A metabox with id '$id' has already been registered.");
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
        wp_nonce_field(self::NONCE_ACTION, $args['id'].'_nonce');
        foreach( $metabox['fields'] as $field )
        {
            $field['post_id'] = $post->ID;
            $field_template = new Field($field);
            echo $field_template->render();
        }
    }
    
    /**
     * Internally used to register metaboxes.
     */
    public function add_meta_boxes()
    {
        foreach( $this->metaboxes as $id => $args )
        {
            \add_meta_box(
                $id,
                $args['title'],
                array($this, 'render'),
                $args['screen'],
                $args['context'],
                $args['priority']
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
 
        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        {
            return $post_id;
        }

        // Check the user's permissions.
        $post_type = filter_input(INPUT_POST, 'post_type');
        if( null !== $post_type && !current_user_can('edit_'.$post_type, $post_id) )
        {
            return $post_id;
        }

        // Update the meta fields.
        foreach( $this->metaboxes as $id => $metabox )
        {
            $this->save_meta_box( $post_id, $id, $metabox );
        }
    }
    
    /**
     * Save the data of a single metabox.
     * 
     * @param number $post_id
     * @param string $id
     * @param array $metabox
     */
    public function save_meta_box( $post_id, $id, $metabox )
    {
        $nonce_name  = $id.'_nonce';
        $nonce_value = filter_input(INPUT_POST, $nonce_name);
        
        // Check if our nonce is set.
        if( null === $nonce_value ) 
        {
            return $post_id;
        }

        // Verify that the nonce is valid.
        if ( !wp_verify_nonce($nonce_value, self::NONCE_ACTION) ) 
        {
            return $post_id;
        }
        
        foreach( $metabox['fields'] as $field )
        {
            $data = filter_input( INPUT_POST, $field['name'] );
            \update_post_meta( $post_id, $field['name'], $data );
        }
    }
    
    /**
     * Print custom metabox style.
     */
    public function print_style() 
    {
        $cs = get_current_screen();
        
        foreach( $this->metaboxes as $metabox )
        {
            if( $metabox['screen'] === $cs->id )
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
    
    /**
     * Default arguments for the add() method.
     * 
     * @return array
     */
    private function default_args()
    {
        return array(
            'title'    => null,
            'screen'   => null,
            'context'  => 'advanced',
            'priority' => 'default',
            'fields'   => array()
        );
    }
}