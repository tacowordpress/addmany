<?php
require __DIR__.'/../vendor/autoload.php';

class AddManyFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryCreate()
    {
      $array_create = \Taco\AddMany\Factory::create([
          'default_variation' => [
            'first_name' => ['type' => 'text'],
            'bio' => ['type' => 'textarea', 'class' => 'wysiwyg']
        ]
      ])->toArray();

      // test if AddMany config exists
      $this->assertArrayHasKey('config_addmany', $array_create);

      // test if the array returned by factory::create contains what was passed in
      $this->assertArraySubset(
        $array_create['config_addmany']['field_variations'],
        [
          'default_variation' => [
            'fields' => [
              'first_name' => ['type' => 'text'],
              'bio' => ['type' => 'textarea', 'class' => 'wysiwyg']
            ]
          ]
        ]
      );
    }

    public function testFactoryAddBySearchCreate() {
      $array_create = \Taco\AddMany\Factory::createWithAddBySearch('Post')->toArray();

      // does the returned array contain the AddBySearch interface and class_method
      $this->assertArraySubset(
        $array_create['config_addmany']['interfaces'],
        ['addbysearch' => [ 'class_method' => 'Post']]
      );
    }

    public function testFactoryAddBySearchCreateWithFields() {
      $array_create = \Taco\AddMany\Factory::createWithAddBySearch('Post::customGetPairs',[
        'first_name' => ['type' => 'text'],
        'image_path' => ['type' => 'image'],
        'bio' => ['type' => 'textarea']
      ])->toArray();

      // does the returned array contain the AddBySearch interface and class_method
      $this->assertArraySubset(
        $array_create['config_addmany']['interfaces'],
        ['addbysearch' => [ 'class_method' => 'Post::customGetPairs']]
      );

      // test if the array returned by factory::createWithAddBySearch contains the fields passed in
      $this->assertArraySubset(
        $array_create['config_addmany']['field_variations'],
        [
          'default_variation' => [
            'fields' => [
              'first_name' => ['type' => 'text'],
              'image_path' => ['type' => 'image'],
              'bio' => ['type' => 'textarea']
            ]
          ]
        ]
      );
    }

    public function testAddFieldVariations() {

      $field_variations =  [
        'variation_1' => [
          'first_name' => ['type' => 'text'],
          'image_path' => ['type' => 'image'],
          'bio' => ['type' => 'textarea']
        ],
        'variation_2' => [
          'last_name' => ['type' => 'text'],
          'bio' => ['type' => 'textarea']
        ],
      ];

      $actual_field_variations =  [
        'variation_1' => [
          'fields' => [
            'first_name' => ['type' => 'text'],
            'image_path' => ['type' => 'image'],
            'bio' => ['type' => 'textarea']
          ]

        ],
        'variation_2' => [
          'fields' => [
            'last_name' => ['type' => 'text'],
            'bio' => ['type' => 'textarea']
          ]
        ],
      ];

      // assert that the field variations added are equal to the returned array
      $returned_variations = \Taco\AddMany\Factory::create()
        ->addFieldVariations($field_variations)
        ->toArray()['config_addmany']['field_variations'];

      $this->assertArraySubset($returned_variations, $actual_field_variations);

    }
}
