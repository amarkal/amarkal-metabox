<?php

namespace Amarkal\Metabox;

class Field
extends \Amarkal\UI\AbstractComponent
{
    public function default_model() 
    {
        return array(
            'type'          => '',
            'label'         => '',
            'description'   => ''
        );
    }
    
    protected function on_created() 
    {
//        $this->model['value'] = \get_term_meta($this->term_id, $this->name, true);
    }
    
    public function get_template_path()
    {
        return __DIR__.'/Field.phtml';
    }
}