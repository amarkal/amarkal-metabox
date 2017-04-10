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
        if( !in_array($id, $this->metaboxes) )
        {
            $this->metaboxes[$id] = array_merge($this->default_args(), $args);
            $this->metaboxes[$id]['form'] = new \Amarkal\UI\Form($args['fields']);
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
        
        // Print the errors from the previous processing
        $this->print_errors($post->ID, $metabox);
        
        // Update component values before rendering
        $this->update_form($metabox, $post->ID);
        
        // Render the metabox with a nonce
        wp_nonce_field(self::NONCE_ACTION, $args['id'].'_nonce');
        
        $template = new \Amarkal\UI\Template(
            array('components' => $metabox['form']->get_components()),
            __DIR__.'/Form.phtml'
        );
        
        $template->render(true);
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
        $nonce_name   = $id.'_nonce';
        $nonce_value  = filter_input(INPUT_POST, $nonce_name);
        $new_instance = filter_input_array(INPUT_POST);
        
        // Check if our nonce is set and verify it
        if( null === $nonce_value || !wp_verify_nonce($nonce_value, self::NONCE_ACTION) ) 
        {
            return $post_id;
        }

        $this->update_form($metabox, $post_id, $new_instance);
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
     * Print all errors stored in a transient for a given post ID.
     * 
     * @param number $post_id
     * @param array $metabox
     */
    public function print_errors( $post_id, $metabox )
    {
        $errors  = \get_transient("amarkal_metabox_errors_$post_id");
        
        if( $errors )
        {
            foreach( $errors as $name => $error )
            {
                $component = $metabox['form']->get_component($name);
                echo "<div class=\"notice notice-error\"><p><strong>{$component->title}</strong> $error</p></div>";
            }
        }
        
        \delete_transient("amarkal_metabox_errors_$post_id");
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
     * Update the form data for the given given metabox. If the $new_instance
     * contains new data, it will be saved into the db and if there are any 
     * validation errors they will be printed.
     * 
     * @param array $metabox
     * @param number $post_id
     * @param array $new_instance
     */
    private function update_form( $metabox, $post_id, array $new_instance = array() )
    {
        $old_instance   = $this->get_old_instance($metabox, $post_id);
        $final_instance = $metabox['form']->update( $new_instance, $old_instance );

        // Update db if there is new data to be saved
        if( array() !== $new_instance )
        {
            $this->update_post_meta($final_instance, $post_id);
            
            /**
             * We need to store all errors in a transient since WordPress does
             * a redirect to post.php and then back to our post, which clears
             * the execution thread. See https://www.sitepoint.com/displaying-errors-from-the-save_post-hook-in-wordpress/
             */
            \set_transient( "amarkal_metabox_errors_$post_id", $metabox['form']->get_errors(), 60 );
        }
    }
    
    /**
     * Update post meta for the given post id
     * 
     * @param type $final_instance
     * @param type $post_id
     */
    private function update_post_meta( $final_instance, $post_id )
    {
        foreach( $final_instance as $name => $value )
        {
            \update_post_meta( $post_id, $name, $value );
        }
    }
    
    /**
     * Get existing meta values from the database.
     * 
     * @param array $metabox
     * @param number $post_id
     * @return array
     */
    private function get_old_instance( $metabox, $post_id )
    {
        $old_instance = array();
        
        foreach( $metabox['fields'] as $field )
        {
            if( in_array($field['name'], get_post_custom_keys($post_id)) )
            {
                $old_instance[$field['name']] = \get_post_meta( $post_id, $field['name'], true );
            }
        }
        
        return $old_instance;
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