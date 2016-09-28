#AddMany:
In simplest terms allows relationships between posts. 
The visual interface gives WordPress admin the ability to assign one-to-many relationships with children that share no other parents. You can also allow many-to-many relationships where children may have many parents and vice versa. You can even create shared fields between parent and children which is important for things like products that may change price at different store locations. More on that later.

Similar to ACF (Advanced Custom Fields), AddMany has the ability to create and repeat sets of fields. The main difference being, it puts control back into the hands of the developer.

**Requirements**

AddMany would not be possible without The TacoWordPress framework – An ORM for custom post types. This is a requirement.

####Example Usage

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

```php
// Example configuration for an AddMany field with AddBySearch

  public function getFields() {
    return [
      'employees' => \Taco\AddMany\Factory::createWithAddBySearch('Employee')
    ];
  }
 ```
 
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


Documentation still in progress. Check back later for more info.



