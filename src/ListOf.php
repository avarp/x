<?php declare(strict_types=1);
namespace Precise;

use ArrayAccess;
use Countable;
use Iterator;

class ListOf extends TypedValue implements Countable, ArrayAccess, Iterator
{
  use Traits\Countable;
  use Traits\Iterator;

  /**
   * Create a typed list
   * @param array $values
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $values, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($values, $type, $unsafe);

    // Save values
    $elementsType = $this->_type[0];
    foreach ($values as $v) {
      $this->_ir[] = purify($elementsType, $v);
    }
  }

  /**
   * Check if the value is a typed list.
   * @internal
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function checkType(array $type, $value, string $path = '$value'): bool
  {
    if (!is_array($value)) {
      self::$lastTypeError = errMsg($path, 'an array', $value);
      return false;
    }
    if (!array_is_list($value)) {
      self::$lastTypeError = errMsg($path, 'a list', $value);
      return false;
    }
    $elementsType = $type[0];
    foreach ($value as $i => $v) {
      if (!Type::check($elementsType, $v, $path . "[$i]")) {
        self::$lastTypeError = Type::getLastError();
        return false;
      }
    }
    return true;
  }

  /**
   * Equality check
   * @param mixed $value
   */
  public function equal($value): bool
  {
    if (
      (is_array($value) && count($value) == count($this->_ir)) ||
      (is_object($value) && $value instanceof TypedValue && $value->getType() == $this->_type)
    ) {
      foreach ($value as $i => $v2) {
        $v1 = $this->_ir[$i];
        if (!equal($v1, $v2, $this->_type[0])) {
          return false;
        }
      }
      return true;
    }
    return false;
  }

  /**
   * ArrayAccess::offsetExists implementation
   */
  public function offsetExists($offset): bool
  {
    return key_exists($offset, $this->_ir);
  }

  /**
   * ArrayAccess::offsetGet implementation
   */
  public function offsetGet($offset): mixed
  {
    if (!key_exists($offset, $this->_ir)) {
      err("Offset \"$offset\" does not exist.", OFFSET_DOESNT_EXIST);
    }
    return $this->_ir[$offset];
  }

  /**
   * ArrayAccess::offsetSet implementation
   */
  public function offsetSet($offset, $value): void
  {
    // check offset
    if (is_null($offset)) {
      $offset = count($this->_ir);
    } elseif (!is_int($offset) || $offset < 0 || $offset > count($this->_ir)) {
      err("Offset \"$offset\" is out of bounds.", OFFSET_OUT_OF_BOUNDS);
    }
    // check value
    $elementsType = $this->_type[0];
    if (Type::check($elementsType, $value)) {
      $this->_ir[$offset] = purify($elementsType, $value);
    } else {
      err(Type::getLastError(), TYPE_ERROR);
    }
  }

  /**
   * ArrayAccess::offsetUnset implementation
   */
  public function offsetUnset($offset): void
  {
    if (!is_int($offset) || $offset < 0 || $offset > count($this->_ir) - 1) {
      err("Offset \"$offset\" is out of bounds.", OFFSET_OUT_OF_BOUNDS);
    }
    array_splice($this->_ir, $offset, 1);
  }

  /**
   * Split the list into chunks
   * @return ListOf
   */
  public function chunk(int $length): ListOf
  {
    $chunks = array_chunk($this->_ir, $length);
    return new ListOf($chunks, [$this->_type], true);
  }

  /**
   * Return the values of key from Records or Maps in the list
   * @param mixed $key
   * @return Map
   */
  // public function column($key): Map
  // {
  // }

  /**
   * Reindex list of Records or Maps using the key
   * @param mixed $key
   * @return Map
   */
  // public function reindex($key): Map
  // {
  // }

  /**
   * Returns elements mapped to their frequency in the list
   * @return Map
   */
  // public function frequency(): Map
  // {
  // }

  /**
   * Computes the difference of lists
   * @param array $otherLists
   * @param callable $areEqual the function for checking equality
   * @return ListOf
   */
  public function diff(...$args): ListOf
  {
    if (empty($args)) {
      return clone $this;
    }
    $areEqual = end($args);
    if (!is_callable($areEqual) || count($args) < 2) {
      $areEqual = function ($a, $b) {
        return serialize($a) <=> serialize($b);
      };
    } else {
      array_pop($args);
    }
    $otherLists = [];
    foreach ($args as $i => $arg) {
      if ($arg instanceof TypedValue) {
        if ($arg->getType() == $this->_type) {
          $otherLists[] = $arg->toArray();
        } else {
          err("Argument #$i has different type than the original list.", LIST_DIFF_TYPE_ERROR);
        }
      } else {
        if (is_array($arg) && array_is_list($arg)) {
          if (self::checkType($this->_type, $arg, "\$args[$i]")) {
            $otherLists[] = $arg;
          } else {
            err(self::$lastTypeError, LIST_DIFF_TYPE_ERROR);
          }
        }
      }
    }
    $otherLists[] = $areEqual;
    return new ListOf(array_udiff($this->_ir, ...$otherLists));
  }

  /**
   * Create a list filled with value repeated N times
   * @param int $count
   * @param mixed $value
   * @param mixed $type, optional
   */
  public static function fill(int $count, $value, $type = null): ListOf
  {
    if (!$type) {
      $type = [Type::of($value)];
    }
    return new ListOf(array_fill(0, $count, $value), $type);
  }

  /**
   * Create a list containing a range of elements
   * @param string|int|float $start
   * @param string|int|float $end
   * @param int|float $step, optional, default is 1
   * @return ListOf
   */
  public static function range(string|int|float $start, string|int|float $end, int|float $step = 1): ListOf
  {
    if (is_string($start) && is_string($end) && is_int($step)) {
      return new ListOf(range($start, $end, $step), ['string']);
    }
    if (is_int($start) && is_int($end) && is_int($step)) {
      return new ListOf(range($start, $end, $step), ['int']);
    }
    if (!is_string($start) && !is_string($end)) {
      return new ListOf(range($start, $end, $step), ['float']);
    }
    err('Parameters\' types are incompatible with each other', LIST_RANGE_WRONG_PARAMS);
  }

  /**
   * Filters elements of the list using a callback function
   * @param callable $filter
   * @return ListOf the new list
   */
  public function filter(callable $filter): ListOf
  {
    return new ListOf(array_values(array_filter($this->_ir, $filter)), $this->_type, true);
  }

  /**
   * Flip indices and elements
   * @return Map where keys are elements and values are indices
   */
  // public function flip(): Map
  // {
  // }

  /**
   * Computes the intersection of lists
   * @param array $otherLists
   * @param callable $isEqual the function for checking equality
   * @return ListOf
   */
  // public function intersect(...$args): ListOf
  // {
  // }

  /**
   * Check whether the list includes the value
   * @param mixed $value
   * @return bool
   */
  public function includes($value): bool
  {
    if (count($this->_ir) == 0) {
      return false;
    }
    if (Type::isScalar($this->_type[0])) {
      return in_array($value, $this->_ir, true);
    } else {
      return in_array(serialize($value), array_map('serialize', $this->_ir));
    }
  }

  /**
   * Applies the callback to the elements of the list
   * @param callable $callback
   * @param mixed $type, optional the type of values that callback returns
   * @return ListOf
   */
  // public function map(callable $callback, $type=null): ListOf|array
  // {
  // }

  /**
   * Add lists into the end of this list
   * @param array $otherLists
   * @return ListOf
   */
  // public function add(...$otherLists): ListOf
  // {
  // }

  /**
   * Pad the list to the specified length with a value
   * @param int $length
   * @param mixed $value
   * @return this
   */
  // public function pad(int $length, $value): ListOf
  // {
  // }

  /**
   * Pop the element off the end of array
   * @return this
   */
  public function pop(): ListOf
  {
    array_pop($this->_ir);
    return $this;
  }

  /**
   * Calculate the product of values in the list
   * @return int|float
   */
  public function product(): int|float
  {
    if ($this->_type[0] == 'int' || $this->_type[0] == 'float') {
      return array_product($this->_ir);
    } else {
      err(
        'The product of values can be calculated only for lists of numbers (int or float).',
        LIST_PRODUCT_FAIL
      );
    }
  }

  /**
   * Push one or more elements onto the end of the list
   * @param array $values
   * @return this
   */
  // public function push(...$values): ListOf
  // {
  // }

  /**
   * Iteratively reduce the list to a single value using a callback function
   * @param callable $callback
   * @param mixed $initial optional initial value, default is null
   * @return mixed
   */
  // public function reduce(callable $callback, $initial=null)
  // {
  // }

  /**
   * Replace values with other values
   * @param mixed $from
   * @param mixed $to
   * @return this
   * or
   * @param Map map of replacements
   * @return this
   */
  // public function replace(...$args): ListOf
  // {
  // }

  /**
   * Reverse elements order in the list
   * @return this
   */
  public function reverse(): ListOf
  {
    $this->_ir = array_reverse($this->_ir);
    return $this;
  }

  /**
   * Search for the value
   * @param mixed $value
   * @return MaybeInt
   */
  // public function search($value): MaybeInt
  // {
  // }

  /**
   * Shift an element off the beginning of the list
   * @return this
   */
  public function shift(): ListOf
  {
    array_shift($this->_ir);
    return $this;
  }

  /**
   * Extract a slice of the list
   * @param int $offset
   * @param ?int $length
   * @return ListOf a new list
   */
  public function slice(int $offset, ?int $length = null): ListOf
  {
    return new ListOf(array_slice($this->_ir, $offset, $length), $this->_type, true);
  }

  /**
   * Remove a portion of the list and replace it with something else
   * @param int $offset
   * @param ?int $length
   * @param ListOf|array $replacement
   * @return this
   */
  // public function splice(int $from, int? $length=null, ListOf|array $replacement=[]): ListOf
  // {
  // }

  /**
   * Calculate the sum of values in the list
   * @return int|float
   */
  public function sum(): int|float
  {
    if ($this->_type[0] == 'int' || $this->_type[0] == 'float') {
      return array_sum($this->_ir);
    } else {
      err(
        'The sum of values can be calculated only for lists of numbers (int or float).',
        LIST_SUM_FAIL
      );
    }
  }

  /**
   * Removes duplicate values from the list
   * @return ListOf a new list
   */
  // public function unique(): ListOf
  // {
  // }

  /**
   * Prepend one or more elements to the beginning of the list
   * @param array $values
   * @return this
   */
  // public function unshift(...$values): ListOf
  // {
  // }

  /**
   * Sort the list
   * @param callable? $callback comparison function
   * @return this
   */
  // public function sort(callable? $callback=null): ListOf
  // {
  // }
}
