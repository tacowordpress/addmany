#AddMany:
In simplest terms allows relationships between posts. 
The visual interface gives WordPress admin the ability to assign one-to-many relationships with children that share no other parents. You can also allow many-to-many relationships where children may have many parents and vice versa. You can even create shared fields between parent and children which is important for things like products that may change price at different store locations. More on that later.

Similar to ACF (Advanced Custom Fields), AddMany has the ability to create and repeat sets of fields. The main difference being, it puts control back into the hands of the developer.

**Requirements**

AddMany would not be possible without The TacoWordPress framework – An ORM for custom post types. This is a requirement.

```php

// Example configuration

public function getFields() {
    return [
      'employees' => [
        'type' => 'text',
        'data-addmany' => true,
        'config_addmany' => [
          'interfaces' => [
            'addbysearch' => [
              'class_method' => 'Post'
            ]
          ],
          'buttons' => ['sorting_reverse','sorting_alpha'],
          'field_variations' => [
            'default_variation' => [
              'fields' => [
                'first_name' => ['type' => 'text'],
                'bio' => ['type' => 'textarea'],
                'gender' => ['type' => 'select', 'options' => ['male', 'female', 'other']]
              ]
            ]
          ]
        ]
      ]
    ];
  }
```


Documentation still in progress. Check back later for more info.



