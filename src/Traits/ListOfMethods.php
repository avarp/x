<?php declare(strict_types=1);
namespace Precise\Traits;
use function Precise\err;

trait ListOfMethods
{
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
          err("Argument #$i has different type than the original list.", \Precise\LIST_DIFF_TYPE_ERROR);
        }
      } else {
        if (is_array($arg) && array_is_list($arg)) {
          if (self::checkType($this->_type, $arg, "\$args[$i]")) {
            $otherLists[] = $arg;
          } else {
            err(self::$lastTypeError, \Precise\LIST_DIFF_TYPE_ERROR);
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
    err('Parameters\' types are incompatible with each other', \Precise\LIST_RANGE_WRONG_PARAMS);
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
        \Precise\LIST_PRODUCT_FAIL
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
      err('The sum of values can be calculated only for lists of numbers (int or float).', \Precise\LIST_SUM_FAIL);
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
