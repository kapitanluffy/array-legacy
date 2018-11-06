# ArrayLegacy

A class for converting arrays into fault-tolerant objects.
Its aim is to access arrays as usual but without checking if a key is "set".

```php
// No more checking if the key is set
if (isset($foo['baz')) {
    $baz = $foo['baz'];
}

// No more setting a default value
if (isset($foo['bar') == false) {
    $foo['bar'] = 'default value';
}
```
#### What are "legacy" arrays?
These are arrays that are used like objects. After working with "legacy" codes, I have seen many of them use arrays to store data.
They (the developers) did not bother creating objects that would "model" their data because there's "not enough time" or "too much refactoring".

With ArrayLegacy, we can avoid this..
> **NOTICE** Undefined index: Foo on line number 5


#### Usage

Convert an array into an ArrayLegacy
```php
$foo = [
    "title" => "FooBar",
    "in_stock" => 1000,
    "price" => 100
];

$foo = ArrayLegacy::make($foo);
```

Use like an array
```php
$foo['title'] = "FooBaz";
// Output: FooBaz
echo $foo['title'];
```

Use a getter/setter method
```php
$foo->setInStock(999);
// Output: 999
echo $foo->getInStock();
```

Use get/set methods
```php
$foo->set("price", 99);
// Output: 99
echo $foo->get("price");
```

With `get()` you can also specify a default value if the key does not exist
```php
// Output: This is a default description
echo $foo->get("description", "This is a default description");
```

Remove a key using `unset()`
```php
unset($foo["title"]);
// Output: null
echo $foo["title"];
```

Use a mutator when accessing/modifying a key
```php
// Note that mutators should directly access `attributes` property
class Foo extends ArrayLegacy {
    public function setTitle($value) {
        $this->attributes['title'] = strtolower($value);
    }

    public function getTitle() {
        return ucwords($this->attributes['title']);
    }
}

$foo = new Foo($foo);
echo $foo->setTitle("This is a test");

// Output: This Is A Test
echo $foo->getTitle();
```
