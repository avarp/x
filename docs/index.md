# Features that PHP lacks

## 1) Typed arrays

Creation

```php
$numbers = new ListOfInt([1, 2]);
```

Work with foreach

```php
foreach ($numbers as $n) {
  echo $n;
}
```

Mutable

```php
$numbers[] = 3;
$numbers[0] = -1;
```

Is type safe

```php
$numbers[] = "not-a-number"; // Throws type error
$numbers[] = 0.5; // Throws type error
```

Is index safe

```php
$x = $numbers[-1]; // Throws "out of bounds" error
$numbers[999999999] = 1; // Throws "out of bounds" error
```

Methods of `ListOf<T>` objects:

1. `toArray(bool $recursive=false): array`
2. `chunk(int $length): ListOfListOf<T>`
3. `column(mixed $key): Map<typeof $key, T[$key]>`
4. `reindex(mixed $key): Map<typeof $key, T>`
5. `frequency(): Map<T, int>`
6. `diff(ListOf<T> $list, callable? $fn=null): ListOf<T>`
7. `static fill(int $count, T $value): ListOf<T>`
8. `static range(int $start, int $end, int $step=1): ListOfInt`
9. `static range(float $start, float $end, float $step=1): ListOfFloat`
10. `filter(callable $fn): ListOf<T>`
11. `flip(): Map<T, int>`
12. `intersect(ListOf<T> $list, callable? $fn=null): ListOf<T>`
13. `includes(T $what): bool`
14. `map(callable $fn): mixed`
15. `add(ListOf<T> $list): ListOf<T>`
16. `pad(int $count, T $value): ListOf<T>`
17. `pop(): ListOf<T>`
18. `product(): int|float`
19. `push(T $value, ...): ListOf<T>`
20. `reduce(callable $fn, mixed $initial=null): mixed`
21. `replace(T $from, T $to): ListOf<T>`
22. `replace(Map<T, T> $replacements): ListOf<T>`
23. `reverse(): ListOf<T>`
24. `search(T $what): MaybeInt`
25. `shift(): ListOf<T>`
26. `slice(int $from, int? $length=null): ListOf<T>`
27. `splice(int $from, int? $length=null, ListOf<T>? $replacement=null): ListOf<T>`
28. `sum(): int|float`
29. `unique(): ListOf<T>`
30. `unshift(T $value, ...): ListOf<T>`
31. `sort(callable? $fn=null): ListOf<T>`

### 2) Record

Creation

```php
class User extends Record
{
  static $type = [
    "id" => "int",
    "name" => "string",
  ];
}

$user = new User(["id" => 123, "name" => "John"]);
```

Mutable

```php
$user->name = "Jane";
```

Work with foreach

```php
foreach ($user as $prop => $value) {
  echo "$prop: $value\n";
}
```

Is type safe

```php
$user->id = "not-a-number"; // Throws type error
$user->name = 0.5; // Throws type error
```

Is index safe

```php
$x = $user->blabla; // Throws "property is not known" error
$user->blabla = "bla"; // Throws "property is not known" error
```

Methods of `Record` objects

1. `toArray(bool $recursive=false): array`

### 3) Tuple

Creation

```php
class Point3D extends Tuple
{
  static $type = ["float", "float", "float"];
}

$origin = new Point3D(0, 0, 0);
```

Mutable

```php
$origin[0] = 3.141;
```

Work with foreach

```php
foreach ($origin as $coord) {
  echo "$coord\n";
}
```

Is type safe

```php
$origin[0] = "not-a-number"; // Throws type error
```

Is index safe

```php
$t = $origin[3]; // Throws "out of bounds" error
$origin[-1] = 0.0; // Throws "out of bounds" error
$origin[] = 0.5; // Throws "out of bounds" error
```

Methods of `Tuple` objects

1. `toArray(bool $recursive=false): array`

### 4) Variants

Creation

```php
class MaybeInt extends Variants
{
  static $type = [
    ":just" => "int",
    ":nothing" => null,
  ];
}

$num = MaybeInt::just(5);
$empty = MaybeInt::nothing();
```

Usage

```php
echo $num->just; // outputs "5"
```

Mutable

```php
$num->just = 10;
```

Is type safe

```php
echo $empty->just; // Throws exception since $empty has no "just"
echo $empty->nothing; // Throws exception since "nothing" does not have a value
$num->just = 0.5; // Throws type error
```

### 5) Types notation

Primitive types

| Value               | Type                   |
| ------------------- | ---------------------- |
| `'int'`             | Integer numbers        |
| `'float'`           | Floating point numbers |
| `'string'`          | String                 |
| `'bool'`            | Boolean values         |
| Existing class name | Object of this class   |
| `'any'`             | Anything               |

Complex types

| Value                                    | Type                          |
| ---------------------------------------- | ----------------------------- |
| `[$x]`                                   | List of values with type `$x` |
| `['key1' => $x, 'key2' => $y]`           | Record                        |
| `[$x, $y, $z]`                           | Tuple                         |
| `[':variant1' => $a, ':variant2' => $b]` | Variants                      |
| `['map', $keys, $values]`                | Map `$keys`:`$values`         |

Type checking

```php
// If $x is list of integers
if (typeCheck(['int'], $x)) {
  // do this
} else {
  // do that
}
``` 

### 6) Custom scalar types

Custom scalar types are possible using function `addScalarType(string $name, callback $predicate): void`.

Example: type `email` that accepts only email addresses.

```php
// definition
addScalarType('email', function($value) {
  return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
})

// usage
class User extends Record {
  static $type = [
    'id' => 'int',
    'name' => 'string',
    'email' => 'email'
  ];
}

$user = new User([
  'id' => 10,
  'name' => 'Alex',
  'email' => 'alex@example.com'
]);

// Next line throws type error because empty string is not a valid email
$user->email = ''; 
```

### 7) Custom complex type

Custom complex types are possible using function `addComplexType(string $name, string $class): void`.

Example: let's create type `set` that will contain only unique values of some type.

Literal form of the type will be `['set', $t]` where `$t` can be any type.

