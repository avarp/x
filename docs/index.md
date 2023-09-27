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
$numbers[] = 'not-a-number'; // Throws type error
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
    'id' => 'int',
    'name' => 'string',
  ];
}

$user = new User(['id' => 123, 'name' => 'John']);
```

Mutable

```php
$user->name = 'Jane';
```

Work with foreach

```php
foreach ($user as $prop => $value) {
  echo "$prop: $value\n";
}
```

Is type safe

```php
$user->id = 'not-a-number'; // Throws type error
$user->name = 0.5; // Throws type error
```

Is index safe

```php
$x = $user->blabla; // Throws "property is not known" error
$user->blabla = 'bla'; // Throws "property is not known" error
```

Methods of `Record` objects

1. `toArray(bool $recursive=false): array`

### 3) Tuple

Creation

```php
class Point3D extends Tuple
{
  static $type = ['float', 'float', 'float'];
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
$origin[0] = 'not-a-number'; // Throws type error
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
    ':just' => 'int',
    ':nothing' => null,
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
    'id' => 'int|string',
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

### Alternatives for PHP array functions

php: `array_change_key_case(array $array, int $case = CASE_LOWER): array`
list: N/A
map: `changeKeyCase(int $case = CASE_LOWER): Map<string, V>`

php: `array_chunk(array $array, int $length, bool $preserve_keys = false): array`
list: `chunk(int $length): ListOfListOf<T>`
map: `chunk(int $length): ListOfMap<K, V>`

php: `array_column(array $array, int|string|null $column_key, int|string|null $index_key = null): array`
list: `column(mixed $key): Map<typeof $key, T[$key]>`
map: `column(mixed $key): Map<typeof $key, V[$key]>`

php: `array_combine(array $keys, array $values): array`
list: N/A
map: `combine(array|ListOf<K> $keys, array|ListOf<V> $values): Map<K, V>`

php: `array_count_values(array $array): array`
list:`count(): int`
map: `count(): int`

php: `array_diff_assoc(array $array, array ...$arrays): array`
list: N/A
map: `diff(array|Map $a1, ...): Map`

php: `array_diff_key(array $array, array ...$arrays): array`
list: N/A
map: `diffKey(array|Map $a1, ...): Map`

php: `array_diff_uassoc(array $array, array ...$arrays, callable $key_compare_func): array`
list: N/A
map: `diff(array|Map $a1, ...,callable $key_compare_func): Map`

php: `array_diff_ukey(array $array, array ...$arrays, callable $key_compare_func): array`
list: N/A
map: `diffKey(array|Map $a1, ...,callable $key_compare_func): Map`

php: `array_diff(array $array, array ...$arrays): array`
list: `diff(ListOf|array $a1, ...): ListOf`
map: N/A

php: `array_fill_keys(array $keys, mixed $value): array`
list: N/A
map: `static fill(ListOf|array $keys, mixed $value): Map`

php `array_fill(int $start_index, int $count, mixed $value): array`
list: `static fill(int $count, T $value): ListOf<T>`
map: N/A

php `array_filter(array $array, ?callable $callback = null, int $mode = 0): array`
list: `filter(?callable $callback = null): List`
map: N/A

php `array_flip(array $array): array`
list: `flip(): Map<T, int>`
map: `flip(): Map<V, K>`

php `array_intersect_assoc(array $array, array ...$arrays): array`
list: N/A
map: `intersect(array|Map $a1, ...): Map`

php `array_intersect_key(array $array, array ...$arrays): array`
list: N/A
map: `intersectKey(array|Map $a1, ...): Map`

php `array_intersect_uassoc(array $array, array ...$arrays, callable $key_compare_func): array`
list: N/A
map: `intersect(array|Map $a1, ...,callable $key_compare_func): Map`

php `array_intersect_ukey(array $array, array ...$arrays, callable $key_compare_func): array`
list: N/A
map: `intersectKey(array|Map $a1, ...,callable $key_compare_func): Map`

php `array_intersect(array $array, array ...$arrays): array`
list: `intersect(ListOf<T>|array $a1, ...): ListOf<T>`
map: N/A

php `array_is_list(array $array): bool`
list: N/A use instanceof instead
map: N/A use instanceof instead

php `array_key_exists(string|int $key, array $array): bool`
list: N/A
map: `keyExists(mixed $key): bool`

php `array_key_first(array $array): int|string|null`
list: N/A
map: `keyFirst(): mixed`

php `array_key_first(array $array): int|string|null`
list: N/A
map: `keyFirst(): mixed`

php `array_key_last(array $array): int|string|null`
list: N/A
map: `keyLast(): mixed`

php `array_keys(array $array): array`
list: N/A
map: `keys(): ListOf`

php `array_map(?callable $callback, array $array, array ...$arrays): array`
list: `map(callable $fn): ListOf`
map: `map(callable $fn): Map`

php `array_merge_recursive(array ...$arrays): array`
list: `mergeRecursively(ListOf|array $a1, ...): ListOf`
map: `mergeRecursively(Map|array $a1, ...): Map`

php `array_merge(array ...$arrays): array`
list: `merge(ListOf|array $a1, ...): ListOf`
map: `merge(Map|array $a1, ...): Map`

php `array_multisort( array &$array1, mixed $array1_sort_order = SORT_ASC, mixed $array1_sort_flags = SORT_REGULAR, mixed ...$rest ): bool`
list: TBD
map: TBD

php `array_pad(array $array, int $length, mixed $value): array`
list: `pad(int $count, T $value): this`
map: N/A

php `array_pop(array &$array): mixed`
list: `pop(): this`
map: N/A

php `array_product(array $array): int|float`
list: `product(): int|float`
map: N/A

php `array_push(array &$array, mixed ...$values): int`
list: `push(T $value, ...): ListOf<T>`
map: N/A

php `array_rand(array $array, int $num = 1): int|string|array`
list: `rand(int $num = 1): mixed`
map: `rand(int $num = 1): mixed`

php `array_reduce(array $array, callable $callback, mixed $initial = null): mixed`
list: `reduce(callable $callback, mixed $initial = null): mixed`
map: `reduce(callable $callback, mixed $initial = null): mixed`

php `array_replace_recursive(array $array, array ...$replacements): array`
list `replaceRecursive(Map|array $replacement, ...): ListOf`
map `replaceRecursive(Map|array $replacement, ...): Map`

php `array_replace(array $array, array ...$replacements): array`
list `replace(Map|array $replacement, ...): ListOf`
map `replace(Map|array $replacement, ...): Map`

php `array_reverse(array $array, bool $preserve_keys = false): array`
list `reverse(): ListOf`
map `reverse(): Map`

php `array_search(mixed $needle, array $haystack, bool $strict = false): int|string|false`
list `search(mixed $needle): int|null`
map `search(mixed $needle): mixed`

php `array_shift(array &$array): mixed`
list `shift(): ListOf`
map N/A

php `array_slice(array $array, int $offset, ?int $length = null, bool $preserve_keys = false): array`
list `slice(int $offset, ?int $length = null): ListOf`
map N/A

php `array_splice(array $&array, int $offset, ?int $length = null, mixed $replacement = []): array`
list `slice(int $offset, ?int $length = null): ListOf`
map N/A

php `array_sum(array $array): int|float`
list: `sum(): int|float`
map: N/A

php: `array_udiff_assoc(array $array, array ...$arrays, callable $value_compare_func): array`
list: N/A
map: `diff(array|Map $a1, ..., null, callable $value_compare_func): Map`

php: `array_udiff_uassoc(array $array, array ...$arrays, callable $value_compare_func, callable $key_compare_func): array`
list: N/A
map: `diff(array|Map $a1, ..., callable $key_compare_func, callable $value_compare_func): Map`

php: `array_udiff(array $array, array ...$arrays, callable $value_compare_func): array`
list: `diff(ListOf|array $a1, ..., callable $value_compare_func): ListOf`
map: N/A

php `array_uintersect_assoc(array $array, array ...$arrays, callable $value_compare_func): array`
list: N/A
map: `intersect(array|Map $a1, ..., null, callable $value_compare_func): Map`

php `array_uintersect_uassoc(array $array, array ...$arrays, callable $value_compare_func, callable $key_compare_func): array`
list: N/A
map: `intersect(array|Map $a1, ..., callable $key_compare_func, callable $value_compare_func): Map`

php `array_uintersect(array $array, array ...$arrays, callable $value_compare_func): array`
list: `intersect(ListOf<T>|array $a1, ..., callable $value_compare_func): ListOf<T>`
map: N/A

php `array_unique(array $array, int $flags = SORT_STRING): array`
list `unique(): ListOf`
map: N/A

php `array_unshift(array &$array, mixed ...$values): int`
list `unshift(...$values): ListOf`
map: N/A

php `array_values(array $array): array`
list N/A
map: `values(): ListOf`

php `array_walk_recursive(array|object &$array, callable $callback, mixed $arg = null): bool`
list N/A
map N/A

php `array_walk(array|object &$array, callable $callback, mixed $arg = null): bool`
list N/A
map N/A

php `arsort(array &$array, int $flags = SORT_REGULAR): true`
list `sortDesc(): ListOf`
map `sortDesc(): Map`

php `asort(array &$array, int $flags = SORT_REGULAR): true`
list `sortAsc|sort(): ListOf`
map `sortAsc|sort(): Map`

php `compact(array|string $var_name, array|string ...$var_names): array`
list N/A
map `compact(...string $varName)`

php `count(Countable|array $value, int $mode = COUNT_NORMAL): int`
list `count(): int`
map `count(): int`

php `current(array|object $array): mixed`
list `current(): mixed`
map `current(): mixed`

php `end(array|object &$array): mixed`
list `end(): mixed`
map `end(): mixed`

php `extract(array &$array, int $flags = EXTR_OVERWRITE, string $prefix = ""): int`
list N/A
map N/A

php `in_array(mixed $needle, array $haystack, bool $strict = false): bool`
list `inList(mixed $needle): bool`
map `hasValue(mixed $needle): bool`

php `key_exists(string|int $key, array $array): bool`
list: N/A
map: `keyExists(mixed $key): bool`

php `key(array|object $array): int|string|null`
list: `key(): mixed`
map: `key(): mixed`

php `krsort(array &$array, int $flags = SORT_REGULAR): true`
list: N/A
map: `sortByKeyDesc(): Map`

php `ksort(array &$array, int $flags = SORT_REGULAR): true`
list: N/A
map: `sortByKeyAsc|sortByKey(): Map`

php `list(mixed $var, mixed ...$vars = ?): array`
list N/A
map N/A

php `natcasesort(array &$array): true`
list `natCaseSort(): ListOf`
map `natCaseSort(): Map`

php `natsort(array &$array): true`
list `natSort(): ListOf`
map `natSort(): Map`

php `next(array|object &$array): mixed`
list: `next(): void`
map: `next(): void`

php `pos(array|object $array): mixed`
list `current(): mixed`
map `current(): mixed`

php `prev(array|object &$array): mixed`
list N/A
map N/A

php `range(string|int|float $start, string|int|float $end, int|float $step = 1): array`
list `range(string|int|float $start, string|int|float $end, int|float $step = 1): ListOf`
map N/A

php `reset(array|object &$array): mixed`
list `rewind(): void`
map `rewind(): void`

php `rsort(array &$array, int $flags = SORT_REGULAR): true`
list `sortDesc(): ListOf`
map `sortDesc(): Map`

php `shuffle(array &$array): true`
list `shuffle(): ListOf`
map `shuffle(): Map`

php `sizeof(Countable|array $value, int $mode = COUNT_NORMAL): int`
list `count(): int`
map `count(): int`

php `sort(array &$array, int $flags = SORT_REGULAR): true`
list `sortAsc|sort(): ListOf`
map `sortAsc|sort(): Map`

php `uasort(array &$array, callable $callback): true`
list `sort(callable $value_compare_func): ListOf`
map `sort(callable $value_compare_func): Map`

php `ksort(array &$array, int $flags = SORT_REGULAR): true`
list: N/A
map: `sortByKey(callable $value_compare_func): Map`

php `usort(array &$array, callable $callback): true`
list `sort(callable $value_compare_func): ListOf`
map `sort(callable $value_compare_func): Map`