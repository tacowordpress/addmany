#AddMany:
In simplest terms allows relationships between posts. 
The visual interface gives WordPress admin the ability to assign one-to-many relationships with children that share no other parents. You can also allow many-to-many relationships where children may have many parents and vice versa. You can even create shared fields between parent and children which is important for things like products that may change price at different store locations. More on that later.

Similar to ACF (Advanced Custom Fields), AddMany has the ability to create and repeat sets of fields. The main difference being, it puts control back into the hands of the developer.

**Use Cases**
 * control the order of posts (custom post types)
 * assign modules or panels to a layout that are customizable
 * relate posts to other posts
 * repeat an arbitrary number of fields (like ACF repeater)
 * overriding a post(s) fields on a case by case basis without affecting the original
 * create site navigation (coming soon)

**Requirements**

AddMany would not be possible without [The TacoWordPress framework – An ORM for custom post types.] (https://github.com/tacowordpress/tacowordpress) This is a requirement.

######Other requirements:
 * PHP >= 5.4 
 * Knowledge of requiring packages through Composer
 * Object-oriented programming 

**Installation (Coming soon)**

####Example Usage


######One-to-Many
```php

// Example configuration for a basic AddMany Field

  public function getFields() {
    return [
      'staff_members' => \Taco\AddMany\Factory::create(
        [
          'first_name' => ['type' => 'text'],
          'last_name' => ['type' => 'text'],
          'bio' => ['type' => 'textarea']
        ], 
        ['limit_range' => [2, 3]] // Enforce a minimum of 2 items, but no more than 3.
       )->toArray() 
    ];
  }
```

######Many-to-Many (AddBySearch)

```php
// Example configuration for an AddMany field with AddBySearch 
// Adds a search field for querying posts via AJAX

  public function getFields() {
    return [
      'employees' => \Taco\AddMany\Factory::createWithAddBySearch('Employee')
    ];
  }
 ```
 
######Many-to-Many with unique common fields between parent and child (like a junction table)
 
 ```php
// Example AddBySearch with shared fields

  public function getFields() {
    return [
      'products' => \Taco\AddMany\Factory::createWithAddBySearch('Product',[
        'price' => ['type' => 'text'],
        'tax' => ['type' => 'text']
      ]);
    ];
  }
 ```
 
######One-to-Many with field variations
 
 ```php

// Example AddMany field with field variations – Adds a dropdown for users to select

  public function getFields() {
    return [
      'staff_members' => \Taco\AddMany\Factory::create(
        [
          'board_members' => [
            'first_name' => ['type' => 'text'],
            'last_name' => ['type' => 'text'],
            'bio' => ['type' => 'textarea']
          ],
          'general_staff' => [
            'first_name' => ['type' => 'text'],
            'last_name' => ['type' => 'text'],
            'department' => ['type' => 'select', 'options' => $this->getDepartments()]
          ],
        ]
       )->toArray() 
    ];
  }
```

######One-to-One
```php

// You can simulate a one-to-one relationship by limiting the number of items to 1

class Person {
  public function getFields() {
    return [
      'spouse' => \Taco\AddMany\Factory::create(
        [
          'first_name' => ['type' => 'text'],
          'phone' => ['type' => 'text']
        ], 
        ['limit_range' => [0, 1]] // Do not allow more than 1 item to be added
       )->toArray() 
    ];
  }
 }
```


Documentation still in progress. Check back later for more info.



