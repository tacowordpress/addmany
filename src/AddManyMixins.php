<?php

namespace Taco;


Trait AddManyMixins {


  private static function getPostTypeStructure($class_method) {
    $post_type_structure = explode('::',$class_method);
    if (count($post_type_structure) === 1) {
        $post_type_structure[] = 'getPairs';
    }
    return $post_type_structure;
  }


  public function getSubPostsWithRefs($ID, $field_key, $field) {
    $subposts = \Taco\AddMany::getChildPosts($ID, $field_key);

    $post_class = $this->getPostClassFromAddBySearchConfig($field);
    $helper = new $post_class;
    $linked_posts = array_map(function($s) use ($helper, $field_key) {
      $object = $helper::find($s->post_reference_id);

      $subfields = \Taco\AddMany::getFieldDefinitionKeys(
        $field_key, $ID, $s->get('fields_variation')
      );
      $subpost_fields = $s->getFields();
      $shared_fields = [];

      foreach($subfields as $subfield_key) {
        $object->set($subfield_key, $s->get($subfield_key));
      }
      return $object;
    }, $subposts);
    
    return $linked_posts;
  }


  public function getRelations($field_key, $field) {
    if($this->isAddBySearch($field)) {
      return $this->getSubPostsWithRefs($this->ID, $field_key, $field);
    }
    return \Taco\AddMany::getChildPosts($this->ID,  $field_key);
  }

  public function isAddBySearch($field) {
    if(!array_key_exists('interfaces', $field['config_addmany'])) {
      return false;
    }
    if(!array_key_exists('addbysearch', $field['config_addmany']['interfaces'])) {
      return false;
    }
    if(!array_key_exists('class_method', $field['config_addmany']['interfaces']['addbysearch'])) {
      return false;
    }
    return true;
  }



  public static function getPostClassFromAddBySearchConfig($field) {
    $class_method = $field['config_addmany']['interfaces']['addbysearch']['class_method'];
    return explode('::', $class_method)[0];
  }


  /**
   * Get a single field - Overrides parent method
   * @param string $key
   * @param bool $convert_value Convert the value (for select fields)
   */
  public function get($key, $convert_value = false)
  {
      $val = (array_key_exists($key, $this->_info))
          ? $this->_info[$key]
          : null;
      if (!is_null($val) && $val !== '' && !$convert_value) {
          return $val;
      }

      $field = $this->getField($key);
      if(\Taco\Util\Arr::iterable($field) && array_key_exists('data-addmany', $field) && $field['data-addmany'] === true) {
        return $this->getRelations($key, $field);
      }
      if (!$convert_value) {
          if (!$field) {
              return $val;
          }
          if (array_key_exists('default', $field)) {
              return $field['default'];
          }
      }
      return (array_key_exists('options', $field) && array_key_exists($val, $field['options']))
          ? $field['options'][$val]
          : $val;
  }
}
