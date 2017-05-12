<?php

namespace Taco\AddMany;


Trait Mixins {


  private static function getPostTypeStructure($class_method) {
    $post_type_structure = explode('::',$class_method);
    if (count($post_type_structure) === 1) {
        $post_type_structure[] = 'getPairs';
    }
    return $post_type_structure;
  }


  public function getSubPostsWithRefs($ID, $field_key, $field) {
    $subposts = \Taco\AddMany::getChildPosts($ID, $field_key);
    uasort($subposts, function($a, $b) {
      return ($a->order - $b->order);
    });
    $post_class = $this->getPostClassFromAddBySearchConfig($field);
    $helper = new $post_class;
    $linked_posts = array_map(function($s) use ($helper, $field_key, $ID) {
      $object = $helper::find($s->post_reference_id);

      $subfields = \Taco\AddMany::getFieldDefinitionKeys(
        $field_key, $ID, $s->get('fields_variation')
      );
      $subpost_fields = $s->getFields();
      $original_fields = $object->getFields();
      $original_field_key_values = [];

      foreach($subfields as $subfield_key) {
        if(array_key_exists($subfield_key, $original_fields)) {
          $original_field_key_values[$subfield_key] = $object->get($subfield_key);
        }
        if($s->get($subfield_key)) {
          $object->set($subfield_key, $s->get($subfield_key));
        }
      }
      $object->set('original_fields', (object) $original_field_key_values);
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


  public function hasFallBackMethod() {
    return method_exists($this, 'getFallBackRelatedPosts');
  }


  public function hasOneRelationship($field) {
    if(!\Taco\Util\Arr::iterable($field)) {
      return false;
    }
    if(!array_key_exists('config_addmany', $field)) {
      return false;
    }
    if(!array_key_exists('limit_range', $field['config_addmany'])) {
      return false;
    }
    if(!\Taco\Util\Arr::iterable($range = $field['config_addmany']['limit_range'])) {
      return false;
    }
    if($range[0] === 1 && $range[1] === 1) {
      return true;
    }
    if($range[0] === 0 && $range[1] === 1) {
      return true;
    }
    return false;
  }


  /**
   * Get pairs by term
   * @param  string $taxonomy  the taxonomy slug
   * @param  string $term_slug the term slug
   * @param  string $keywords  optional param that allows querying by keywords
   * @return array - collection of posts
   */
  public static function getPairsByTerm($taxonomy, $term_slug, $keywords='') {
    $results = self::getWhere([
      'tax_query' => [
        [
          'taxonomy' => $taxonomy,
          'field'    =>  'slug',
          'terms'    => $term_slug
        ]
      ],
      'orderby' => 'post_date',
      'order' => 'DESC',
      's' => $keywords
    ]);
    if(!\Taco\Util\Arr::iterable($results)) {
      return [];
    }
    return array_combine(
      \Taco\Util\Collection::pluck($results, 'ID'),
      \Taco\Util\Collection::pluck($results, 'post_title')
    );
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

      if(\Taco\Util\Arr::iterable($field)
        && array_key_exists('data-addmany', $field)
        && $field['data-addmany'] === true
        && !is_admin()
      ){
        $relations = $this->getRelations($key, $field);

        if(!\Taco\Util\Arr::iterable($relations) && $this->hasFallBackMethod()) {
          $relations = $this->getFallBackRelatedPosts($key);
        }
        if(!$relations) {
          return null;
        }
        if($this->hasOneRelationship($field)) {
          $relations = current($relations);
        }
        return $relations;
      }
      if (!$convert_value) {
          if (!$field) {
            return $val;
          }
          if (array_key_exists('default', $field)) {
            return $field['default'];
          }
      }
      if(
        array_key_exists('options', $field)
        && array_key_exists($val, $field['options'])
      ){
          return $field['options'][$val];
      }

      return $val;
  }

    public function getRenderMetaBoxField($name, $field=null) 
    {
        if (array_key_exists('data-addmany', $field)) {
            unset($field['config_addmany']);
        }
        return parent::getRenderMetaBoxField($name, $field);
    }
}
