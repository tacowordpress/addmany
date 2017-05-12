<?php

namespace Taco\AddMany;

class Factory {

  public static $inventory;
  public $current_object = null;


  public static function create($fields_array=[], $other_options=[]) {
    $factory_instance = new \Taco\AddMany\Factory;
    $factory_instance->current_object = new \Taco\AddMany;

    if(!\Taco\Util\Arr::iterable($fields_array)) {
      return $factory_instance;
    }

    if(!self::usesFieldVariations($fields_array)) {
      $fields = ['default_variation' => $fields_array];
    } else {
      $fields = $fields_array;
    }

    if(
      array_key_exists('limit_range', $other_options)
      && count($other_options['limit_range']) == 2)
    {
      $factory_instance->setLimitRange(
        $other_options['limit_range']
      );
    }

    $factory_instance->addFieldVariations($fields);

    return $factory_instance;
  }


  public function setLimitRange($limit_range=false) {
    $this->current_object->limit_range = $limit_range;
  }

  public static function usesFieldVariations($args) {
    if(!\Taco\Util\Arr::iterable($args)) {
      return false;
    }
    if(!\Taco\Util\Arr::iterable(current($args))) {
      return false;
    }
    foreach($args as $a) {
      if(\Taco\Util\Arr::iterable($a) && array_key_exists('type', $a)) {
        return false;
      }
    }
    return true;
  }

  public static function createAndGet($fields_array=[], $other_options=[]) {
    return self::create($fields_array, $other_options)->toArray();
  }


  public static function createWithAddBySearch($post_class_method=null, $fields=null, $other_options=[]) {
    $factory_instance = new \Taco\AddMany\Factory;
    $factory_instance->current_object = new \Taco\AddMany;
    if(strlen($post_class_method)) {
      $factory_instance->setAddBySearchMethod($post_class_method);
    }
    if(\Taco\Util\Arr::iterable($fields)) {
      $factory_instance->addFieldVariations(['default_variation' => $fields]);
    } else {
      $factory_instance->addFieldVariations(['default_variation' => []]);
    }

    if(
      array_key_exists('limit_range', $other_options)
      && count($other_options['limit_range']) == 2)
    {
      $factory_instance->setLimitRange(
        $other_options['limit_range']
      );
    }

    return $factory_instance;
  }

  public static function createAndGetWithAddBySearch($post_class_method=null, $fields=null, $other_options=[]) {
    return self::createWithAddBySearch(
      $post_class_method, $fields, $other_options
    )->toArray();
  }

  private function setAddBySearchMethod($post_class_method) {
    $addbysearch = ['addbysearch' => ['class_method' => $post_class_method]];
    $this->current_object->interfaces = array_merge_recursive(
      $this->current_object->interfaces, $addbysearch
    );
    return $this;
  }

  public function toArray() {
    $array = $this->current_object->toArray();
    return $array;
  }

  public function setButtons($buttons_array) {
    $this->current_object->buttons = $buttons_array;
    return $this;
  }

  public function addFieldVariation($key, $fields) {
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
    foreach($variations as $key => $variation) {
      $this->addFieldVariation($key, $variation);
    }
    return $this;
  }
}
