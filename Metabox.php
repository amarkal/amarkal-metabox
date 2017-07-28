<?php

namespace Amarkal\Metabox;

/**
 * Metabox Manager adds metaboxes to WordPress posts
 */
class Metabox
{
    /**
     * The metabox' configuration.
     *
     * @var [array]
     */
    private $config;

    /**
     * The metabox' unique ID
     *
     * @var [string]
     */
    public $id;

    /**
     * The form instance
     *
     * @var [\Amarkal\UI\Form]
     */
    public $form;

    /**
     * Security nonce action
     */
    const NONCE_ACTION = 'amarkal_metabox';

    public function __construct( $id, $args )
    {
        $this->id       = $id;
        $this->config   = array_merge($this->default_args(), $args);
        $this->form     = new \Amarkal\UI\Form(
            new \Amarkal\UI\ComponentList($this->config['fields'])
        );
    }

    /**
     * Get a config variable
     *
     * @param string $name
     * @return void
     */
    public function __get( $name )
    {
        return $this->config[$name];
    }

    /**
     * Render this metabox
     *
     * @param number $post_id
     * @return void
     */
    public function render( $post_id )
    {
        // Print the errors from the previous processing
        $this->print_errors($post_id);

        // Update component values before rendering
        $this->update_form($post_id);

        include __DIR__.'/Metabox.phtml';
    }

    /**
     * Save the data of this metabox to the database.
     * 
     * @param number $post_id
     */
    public function save( $post_id )
    {
        $nonce_name   = $this->id.'_nonce';
        $nonce_value  = filter_input(INPUT_POST, $nonce_name);
        
        // Check if our nonce is set and verify it
        if( null === $nonce_value || !wp_verify_nonce($nonce_value, self::NONCE_ACTION) ) 
        {
            return;
        }

        $this->update_form($post_id);
    }

    /**
     * Update the form data for the given given metabox. If the $new_instance
     * contains new data, it will be saved into the db and if there are any 
     * validation errors they will be printed.
     * 
     * @param number $post_id
     */
    public function update_form( $post_id )
    {
        $new_instance   = $this->get_new_instance();
        $old_instance   = $this->get_old_instance($post_id);
        $final_instance = $this->form->update( $new_instance, $old_instance );

        // Update db if there is new data to be saved
        if( array() !== $new_instance )
        {
            $this->update_post_meta($final_instance, $post_id);
            
            /**
             * We need to store all errors in a transient since WordPress does
             * a redirect to post.php and then back to our post, which clears
             * the execution thread. See https://www.sitepoint.com/displaying-errors-from-the-save_post-hook-in-wordpress/
             */
            \set_transient( "amarkal_metabox_errors_{$post_id}_{$this->id}", $this->form->get_errors(), 60 );
        }

        return $post_id;
    }

    /**
     * Print all errors stored in a transient for a given post ID.
     * 
     * @param number $post_id
     * @param array $metabox
     */
    public function print_errors( $post_id )
    {
        $errors  = \get_transient("amarkal_metabox_errors_{$post_id}_{$this->id}");
        
        if( $errors )
        {
            $cl = $this->form->get_component_list();
            foreach( $errors as $name => $error )
            {
                $component = $cl->get_by_name($name);
                echo "<div class=\"notice notice-error\"><p><strong>{$component->title}</strong> $error</p></div>";
            }
        }
        
        \delete_transient("amarkal_metabox_errors_{$post_id}_{$this->id}");
    }

    /**
     * Get existing meta values from the database.
     * 
     * @param number $post_id
     * @return array
     */
    private function get_old_instance( $post_id )
    {
        $old_instance = array();
        $pck = \get_post_custom_keys($post_id);
        
        foreach( $this->form->get_component_list()->get_value_components() as $comp )
        {
            $name = $comp->name;
            if( null !== $pck && in_array($name, $pck) )
            {
                $old_instance[$name] = \get_post_meta( $post_id, $name, true );
            }
        }
        
        return $old_instance;
    }

    /**
     * Get the new instance values for this metabox' components.
     * If no form was submited, an empty array will be returned.
     *
     * @return array
     */
    private function get_new_instance()
    {
        $new_instance = array();
        $data = filter_input_array(INPUT_POST);
        
        if(null === $data)
        {
            return $new_instance;
        }

        foreach($this->form->get_component_list()->get_value_components() as $c)
        {
            if(\array_key_exists($c->name, $data))
            {
                $new_instance[$c->name] = $data[$c->name];
            }
        }

        return $new_instance;
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