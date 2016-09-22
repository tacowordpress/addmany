<?php
/*
 * AddMany
 * Description: AddMany is a TacoWordPress add-on that allows you to
 * create an arbitrary number of fields for custom posts in the WordPress admin.
 */

namespace JasandPereza;
use \Taco\Util\Arr;
use \Taco\Util\Collection;

class AddMany {
  const VERSION = '006';
  public static $field_definitions = [];
  public static $wp_tiny_mce_settings = null;
  public static $path_url = null;
  public function init() {
    if(is_null(self::$path_url)) {
      self::$path_url = '/'.strstr(dirname(__FILE__), 'vendor');
    }

    wp_register_script(
      'addmanyjs',
      '/addons/dist/addmany.min.js',
      false,
      self::VERSION,
      true);
    wp_enqueue_script('addmanyjs');

    wp_register_style(
      'addmany',
      '/addons/dist/addmany.min.css',
      false,
      self::VERSION
    );
    wp_enqueue_style('addmany');

    self::loadFieldDefinitions();

    wp_localize_script(
      'addmanyjs',
      'field_definitions',
      self::$field_definitions
    );

    // Allow this plugin to access the Wordpress TinyMCE settings
    wp_localize_script(
      'addmanyjs',
      'wp_tiny_mce_settings',
      self::$wp_tiny_mce_settings
    );

    // Allow this script to use admin-ajax.php
    wp_localize_script(
      'addmanyjs',
      'AJAXSubmit',
      array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'AJAXSubmit_nonce' => wp_create_nonce(
          'AJAXSubmit-posting'
        )
      )
    );
  }

  public function loadFieldDefinitions() {
    global $post;
    if(!$post) {
      return false;
    }
    if(!array_key_exists('post_type', $post)) {
      return false;
    }
    $class = str_replace(
      ' ',
      '',
      ucwords(
        str_replace(
          \Taco\Base::SEPARATOR,
          ' ',
          $post->post_type
        )
      )
    );

    if(class_exists($class)) {
      $custom_post = \Taco\Post\Factory::create($post);
      $fields = $custom_post->getFields();

      foreach($fields as $k => $v) {
        if(!\Taco\Util\Arr::iterable($v)) continue;
        foreach($v as $key => $value) {
          if(!\Taco\Util\Arr::iterable($value)) continue;
          //if(!array_key_exists('fields', $value)) continue;
          if(array_key_exists('is_addbysearch', $value)) {
            self::$field_definitions[$k]['is_addbysearch'] = true;
          }
          if(array_key_exists('class_method', $value)) {
            self::$field_definitions[$k]['class_method'] = $value['class_method'];
          }
          self::$field_definitions[$k][$key] = $value['fields'];
        }
      }
    }
  }


  public static function createNewSubPost($post_data) {
    if(!array_key_exists('field_assigned_to', $post_data)) {
      return false;
    }
    $subpost = new \SubPost;
    $subpost->set('post_title', 'AddMany subpost '.md5(mt_rand()));
    $subpost->set('post_parent', $post_data['parent_id']);
    $subpost->set(
      'field_assigned_to',
      trim($post_data['field_assigned_to'])
    );
    $subpost->set(
      'fields_variation',
      trim($post_data['current_variation'])
    );
    $subpost->set(
      'post_reference_id',
      trim($post_data['post_reference_id'])
    );

    $id = $subpost->save();
    return self::getSingleAJAXSubPost(
      $post_data['field_assigned_to'],
      $subpost, $post_data['parent_id']
    );
  }

  public static function AJAXSubmit() {
    // is this an addbysearch request
    if(array_key_exists('class_method', $_POST)
      && array_key_exists('is_addbysearch', $_POST)
      && $_POST['is_addbysearch']
      && array_key_exists('field_assigned_to', $_POST)
      && array_key_exists('parent_id', $_POST)
    ) {
      return self::getAJAXPostsUsingAddBySearch(
        $_POST['class_method'],
        $_POST['field_assigned_to'],
        $_POST['parent_id'],
        $_POST['keywords']
      );
    }
    // is this a subpost request
    if(array_key_exists('get_by', $_POST)
      && array_key_exists('field_assigned_to', $_POST)
      && array_key_exists('parent_id', $_POST)
    ) {
      return self::getAJAXSubPosts(
        $_POST['field_assigned_to'],
        $_POST['parent_id']
      );
    }
    return self::createNewSubPost($_POST);
  }

  // This is if a user creates new sub-posts but then leaves the page
  // without hitting the publish or update button
  public static function removeAbandonedPosts() {
    $sub_posts = \SubPost::getWhere(array('post_parent' => 0));
    foreach($sub_posts as $sp) {
      wp_delete_post($sp->ID, true);
    }
  }

  public static function getAJAXPostsUsingAddBySearch($class_method, $field_assigned_to, $parent_id, $keywords='') {

    $class_method_config = self::getPostTypeStructure($class_method);

    if(array_key_exists('original_post_class', $class_method_config)) {
      $results = self::getPairsWithKeyWords($keywords,  $class_method_config['original_post_class']);
    } else {
      $helper = new $class_method_config[0];
      $method = $class_method_config[1];
      $results = $helper->$method($keywords);
    }

    $subfields = self::getFieldDefinitionKeys($field_assigned_to, $parent_id, 'default_variation');
    $formatted_records = [];
    $fields_attribs = self::getFieldDefinitionKeyAttribs($field_assigned_to, $parent_id, 'default_variation', true);

    if(\Taco\Util\Arr::iterable($results)) {

      foreach($results as $id => $title) {
        $array_fields_values = [];
        foreach($subfields as $key) {
          $array_fields_values[$key] = array(
            'value' => null,
            'attribs' => $fields_attribs[$key]
          );
        }
        $formatted_records[] = array_merge(
          array('fields' => $array_fields_values),
          array(
            'postId' => $id,
            'postTitle' => $title,
            'fields_variation' => 'default_variation'
          )
        );
      }

      header('Content-Type: application/json');
      echo json_encode(
        array(
          'success' => true,
          'posts' => $formatted_records
        )
      );
      exit;
    }

    header('Content-Type: application/json');
    echo json_encode(
      array(
        'success' => false
      )
    );
    exit;
  }

  public static function getPairsWithKeyWords($keywords, $class) {
    $helper = new $class;
    $results = $helper->getWhere([
      's' => $keywords
    ]);
    return array_combine(
      \Taco\Util\Collection::pluck($results, 'ID'),
      \Taco\Util\Collection::pluck($results, 'post_title')
    );
  }

  public static function getPostTypeStructure($class_method) {
    $post_type_structure = explode('::', $class_method);
    if (count($post_type_structure) === 1) {
        $post_type_structure['original_post_class'] = $post_type_structure[0];
    }
    if(count($post_type_structure) > 1 && $post_type_structure[1] == 'getPairs') {
      $post_type_structure['original_post_class'] = $post_type_structure[0];
    }
    return $post_type_structure;
  }

  private static function getSubPostsSafe($field_assigned_to, $parent_id) {
    global $wpdb;
    $query = sprintf(
      "SELECT ID, post_content, post_title from %s
      LEFT JOIN %s on post_id = ID
      WHERE meta_key = 'field_assigned_to'
      AND meta_value = '%s'
      AND post_parent = %d",
      $wpdb->posts,
      $wpdb->postmeta,
      $field_assigned_to,
      $parent_id
    );
    return $wpdb->get_results($query, OBJECT);
  }

  private static function getFieldDefinitionKeys($field_assigned_to, $parent_id, $fields_variation) {
    $post_parent = \Taco\Post\Factory::create($parent_id);
    $post_parent->loaded_post = $post_parent;
    return array_keys(
      $post_parent ->getFields()[$field_assigned_to][$fields_variation]['fields']
    );
  }

  private static function getFieldDefinitionKeyAttribs($field_assigned_to, $parent_id, $fields_variation, $exclude_value=false) {

    $post_parent = \Taco\Post\Factory::create($parent_id);
    $post_parent->loaded_post = $post_parent;

    $record_fields = $post_parent->getFields()[$field_assigned_to][$fields_variation]['fields'];
    $fields_attribs = [];

    if(!Arr::iterable($record_fields)) return [];
    foreach($record_fields as $k => $attribs) {
      if(!Arr::iterable($attribs)) continue;
      foreach($attribs as $a => $v) {
        if($a == 'value' && $exclude_value === false) continue;
        $fields_attribs[$k][$a] = $v;
      }
    }
    return $fields_attribs;
  }


  public static function getChildPosts($parent_id, $field_assigned_to) {
    return \Taco\Post\Factory::createMultiple(
      get_posts(
        [
          'post_type' => 'sub-post',
          'post_status' => 'publish',
          'post_parent' => $parent_id,
          'meta_query' => [
            [
              'key' => 'field_assigned_to',
              'value' => $field_assigned_to
            ]
          ],
          'orderby' => 'meta_value',
          'meta_key' => 'order',
          'order' => 'ASC'
        ]
      )
    );
  }


  private static function getSingleAJAXSubPost($field_assigned_to, $subpost, $parent_id) {

    $post_title = $subpost->post_title;

    $fields_variation = $subpost->get('fields_variation');
    $fields_attribs = self::getFieldDefinitionKeyAttribs($field_assigned_to, $parent_id, $fields_variation);
    //if(!Arr::iterable($fields_attribs)) return [];
    $subfields = self::getFieldDefinitionKeys($field_assigned_to, $parent_id, $fields_variation);
    if(!Arr::iterable($subfields)) {
      $subfields = array();
    }
    if(isset($subpost->post_title) && preg_match('/[&\']{1,}/', $subpost->post_title)) {
      $post_title = stripslashes($subpost->post_title);
    }

    $array_fields_values = [];
    foreach($subfields as $key) {
      $array_fields_values[$key] = array(
        'value' => $subpost->get($key),
        'attribs' => $fields_attribs[$key]
      );
    }

    $post_reference_info  = (object)[];
    if($subpost->get('post_reference_id')) {
      $object_post_reference = \Taco\Post::find((int) $subpost->get('post_reference_id'));
      $post_reference_info = [
        'postId' => $object_post_reference->ID,
        'postTitle' => $object_post_reference->post_title
      ];
    }

    $formatted = array_merge(
      array('fields' => (object) $array_fields_values),
      array(
        'postId' => $subpost->ID,
        'fieldsVariation' => $subpost->get('fields_variation')
      )
    );

    header('Content-Type: application/json');
    echo json_encode(
      array(
        'success' => true,
        'postReferenceInfo' => $post_reference_info,
        'posts' => array(array_filter($formatted))
      )
    );
    exit;
  }

  private static function getAJAXSubPosts($field_assigned_to, $parent_id) {
    //self::removeAbandonedPosts();
    $all_records = self::getChildPosts($parent_id, $field_assigned_to);
    // filter out the fields we don't need
    $filtered = array_map(function($subpost) use ($field_assigned_to, $parent_id) {
      $post_title = $subpost->post_title;
      $fields_variation = $subpost->get('fields_variation');
      $fields_attribs = self::getFieldDefinitionKeyAttribs($field_assigned_to, $parent_id, $fields_variation);
      // if(!Arr::iterable($fields_attribs)) return [];
      $subfields = self::getFieldDefinitionKeys($field_assigned_to, $parent_id, $fields_variation);

      if(!Arr::iterable($subfields)) {
        $subfields = array();
      }
      if(isset($subpost->post_title) && preg_match('/[&\']{1,}/', $subpost->post_title)) {
        $post_title = stripslashes($subpost->post_title);
      }

      $array_fields_values = [];
      foreach($subfields as $key) {
        $array_fields_values[$key] = array(
          'value' => $subpost->get($key),
          'attribs' => $fields_attribs[$key]
        );
      }
      $post_reference_info  = null;
      if($subpost->get('post_reference_id')) {
        $object_post_reference = \Taco\Post::find((int) $subpost->get('post_reference_id'));
        $post_reference_info = [
          'postId' => $object_post_reference->ID,
          'postTitle' => $object_post_reference->post_title
        ];
      }
      return array_merge(
        array('fields' => (object) $array_fields_values),
        array(
          'postId' => $subpost->ID,
          'order' => (int) $subpost->get('order'),
          'fieldsVariation' => $subpost->get('fields_variation'),
          'postReferenceInfo' =>$post_reference_info
        )
      );
    }, $all_records);

    header('Content-Type: application/json');
    echo json_encode(
      array(
        'success' => true,
        'posts' => $filtered
      )
    );
    exit;
  }

  public static function updateSubPosts($post_id, $fields_values, $object_post_parent=null) {
    $post_id = trim(preg_replace('/\D/', '', $post_id));
    $subpost = \SubPost::find($post_id);

    $field_assigned_to = $subpost->get('field_assigned_to');

    $subpost_fields = \Taco\Post\Factory::create($object_post_parent->ID)
      ->getFields()[$field_assigned_to][$subpost->get('fields_variation')]['fields'];

    if(!array_key_exists('order', $subpost_fields)) {
      $subpost_fields['order'] = (int) $fields_values['order'];
    }

    if(!array_key_exists('post_reference_id', $subpost_fields)) {
      $subpost_fields['post_reference_id'] = (int) $fields_values['post_reference_id'];
    }

    if(wp_is_post_revision($post_parent)) return false;
    $array_remove_values = array_diff(array_keys($subpost_fields),array_keys($fields_values));

    foreach($fields_values as $k => $v) {
      update_post_meta($post_id, $k, $v);
    }

    foreach($array_remove_values as $field_key) {
      delete_post_meta($post_id, $field_key);
    }
    remove_action('save_post', 'AddMany::saveAll');

    return true;
  }

  private static function areThereDeletedIds() {
    if(!array_key_exists('addmany_deleted_ids', $_POST)) return false;
    if(!strlen('addmany_deleted_ids')) return false;
    return true;
  }

  private static function deleteSubPosts($string_ids) {
    $ids = explode(',', $string_ids);
    if(!Arr::iterable($ids)) return false;
    foreach($ids as $id) {
      wp_delete_post((int) $id, true);
    }
    return true;
  }

  public static function saveAll($post_id) {
    if(self::areThereDeletedIds()) {
      self::deleteSubPosts($_POST['addmany_deleted_ids']);
    }
    if(!array_key_exists('subposts', $_POST)) return false;

    $source = $_POST;
    $subposts = $source['subposts'];
    if(!Arr::iterable($subposts)) return false;
    foreach($subposts as $record) {
      if(!Arr::iterable($record)) continue;
      foreach($record as $k => $v) {
        self::updateSubPosts(
          $k,
          $v,
          $record
        );
      }
    }
    return true;
  }

  public static function getSubPosts($fieldname, $post_id) {

    $record = \Taco\Post::find($post_id);
    $subposts = self::getSubPostsSafe($fieldname, $post_id);
    $subpost_ids = Collection::pluck($subposts, 'ID');

    $filtered = [];
    foreach($field_ids as $fid) {
      if(!in_array($fid, $subpost_ids)) continue;
      $filtered[$fid] = \Taco\Post::find($fid);
    }
    return $filtered;
  }
}
