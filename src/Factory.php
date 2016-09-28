<?php

namespace Taco\AddMany;

class Factory {

  public static $inventory;
  public static $current_object = null;
  public $use_cached = false;
  public $key = null;

  public static function create($key, $post_class=null, $field_variations=null) {
    if(\Taco\Util\Arr::iterable(self::$inventory)) {
      foreach(self::$inventory as $i) {
        if($i->key == $key) {
          $factory_instance = new \Taco\AddMany\Factory;
          $factory_instance->key = $key;
          $factory_instance->use_cached = true;
          $factory_instance->current_object = new \Taco\AddMany($key);
          return $factory_instance;
        }
      }
    }
    $factory_instance = new \Taco\AddMany\Factory;
    $factory_instance->key = $key;
    $factory_instance->current_object = new \Taco\AddMany($key);
    if(strlen($post_class)) {
      $factory_instance->setAddBySearchMethod($post_class);
    }
    if(\Taco\Util\Arr::iterable($field_variations)) {
      $factory_instance->addFieldVariations($field_variations);
    }
    return $factory_instance;
  }

  public function setAddBySearchMethod($post_class) {
    if($this->use_cached === true) {
      return $this;
    }
    $addbysearch = ['addbysearch' => ['class_method' => $post_class]];
    $this->current_object->interfaces = array_merge_recursive(
      $this->current_object->interfaces, $addbysearch
    );
    return $this;
  }

  public function toArray() {
    if($this->use_cached === true) {
      foreach(self::$inventory as $i) {
        if($i->key === $this->key) {
          return $i->toArray();
        }
      }
    }
    $array = $this->current_object->toArray();
    $this->saveToInventory();
    return $array;
  }

  public function setButtons($buttons_array) {
    if($this->use_cached === true) {
      return $this;
    }
    $this->current_object->config['buttons'] = $buttons_array;
    return $this;
  }

  public function addFieldVariation($key, $fields) {
    if($this->use_cached === true) {
      return $this;
    }
    if(array_key_exists($key, $this->current_object->field_variations)) {
      return $this;
    }
    $set = [$key => ['fields' => $fields]];
    $this->current_object->field_variations = array_merge_recursive(
      $this->current_object->field_variations,
      $set
    );
    return $this;
  }

  public function addFieldVariations($variations) {
    if($this->use_cached === true) {
      return $this;
    }
    foreach($variations as $key => $variation) {
      $this->addFieldVariation($key, $variation);
    }
    return $this;
  }


  public function saveToInventory() {
    self::$inventory[$this->key] = $this->current_object;
    return $this;
  }
}
