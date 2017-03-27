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
            static::$instance->init();
        }
        return static::$instance;
    }
    
    public function add( $id, $args )
    {
        if( !in_array($id, $this->metaboxes) )
        {
            $this->metaboxes[$id] = array_merge($this->default_args(), $args);
        }
        else throw new \RuntimeException("A metabox with id '$id' has already been registered.");
    }
    
    public function render( $post, $args )
    {
        foreach( $this->metaboxes[$args['id']]['fields'] as $field )
        {
            $field_template = new Field($field);
            echo $field_template->render();
        }
    }
    
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
    
    public function save_meta_boxes()
    {
        
    }
    
    function print_style() 
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
    
    private function init()
    {
        \add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        \add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        \add_action( 'admin_footer', array( $this, 'print_style' ) );
    }
    
    private function default_args()
    {
        return array(
            'id'       => null,
            'title'    => null,
            'screen'   => null,
            'context'  => 'advanced',
            'priority' => 'default',
            'fields'   => array()
        );
    }
}