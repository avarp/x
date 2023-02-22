<?php declare(strict_types=1);
namespace Precise;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use TypeError;

class Tuple extends TypedValue implements Countable, ArrayAccess, Iterator
{
  /**
   * Create a tuple of values
   * @param array $values
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $values, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($values, $type, $unsafe);

    // Save values
    foreach ($this->itype as $i => $elementType) {
      $v = $values[$i];
      if (Type::isScalar($elementType)) {
        $this->ir[$i] = $elementType == 'float' ? (float) $v : $v;
      } else {
        $typeClass = Type::getComplexTypeClass($elementType);
        if ($typeClass) {
          $this->ir[$i] = new $typeClass($v, $elementType, true);
        } else {
          throw new Exception('Unknown type of elements.', ERR::UNKNOWN_TYPE);
        }
      }
    }
  }

  /**
   * Check if the value is a tuple of values with given types. Method is only for internal purposes.
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function checkType(array $type, $value, string $path = '$value'): bool
  {
    if (!is_array($value)) {
      self::$lastTypeError = Type::errMsg($path, 'an array', $value);
      return false;
    }
    if (!array_is_list($value)) {
      self::$lastTypeError = Type::errMsg($path, 'a list', $value);
      return false;
    }
    if (count($value) != count($type)) {
      self::$lastTypeError = Type::errMsg($path, 'a list of ' . count($type) . ' values', $value);
      return false;
    }
    foreach ($type as $i => $elementType) {
      if (!Type::check($elementType, $value[$i], $path . "[$i]")) {
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
      (is_array($value) && count($value) == count($this->ir)) ||
      (is_object($value) && $value instanceof TypedValue && $value->getType() == $this->itype)
    ) {
      foreach ($value as $i => $v2) {
        $v1 = $this->ir[$i];
        if (Type::isScalar($this->itype[$i])) {
          if ($v1 !== $v2) {
            return false;
          }
        } else {
          if (!$v1->equal($v2)) {
            return false;
          }
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Countable::count implementation
   */
  public function count(): int
  {
    return count($this->ir);
  }

  /**
   * ArrayAccess::offsetExists implementation
   */
  public function offsetExists($offset): bool
  {
    return key_exists($offset, $this->ir);
  }

  /**
   * ArrayAccess::offsetGet implementation
   */
  public function offsetGet($offset): mixed
  {
    if (!key_exists($offset, $this->ir)) {
      throw new Exception("Offset \"$offset\" does not exist.", ERR::OFFSET_DOESNT_EXIST);
    }
    return $this->ir[$offset];
  }

  /**
   * ArrayAccess::offsetSet implementation
   */
  public function offsetSet($offset, $value): void
  {
    // check offset
    if (!is_int($offset) && key_exists($offset, $this->ir)) {
      throw new Exception("Offset \"$offset\" is out of bounds.", ERR::OFFSET_OUT_OF_BOUNDS);
    }
    // check value
    if (Type::check($this->itype[$offset], $value, "\$value[$offset]")) {
      $this->ir[$offset] = $value;
    } else {
      throw new TypeError(Type::getLastError(), ERR::TYPE_ERROR);
    }
  }

  /**
   * ArrayAccess::offsetUnset implementation
   */
  public function offsetUnset($offset): void
  {
    throw new Exception('Tuple does not support elements removal.', ERR::TUPLE_UNSET_UNSUPPORTED);
  }

  /**
   * Iterator::current implementation
   */
  public function current(): mixed
  {
    return current($this->ir);
  }

  /**
   * Iterator::key implementation
   */
  public function key(): mixed
  {
    return key($this->ir);
  }

  /**
   * Iterator::next implementation
   */
  public function next(): void
  {
    next($this->ir);
  }

  /**
   * Iterator::rewind implementation
   */
  public function rewind(): void
  {
    reset($this->ir);
  }

  /**
   * Iterator::valid implementation
   */
  public function valid(): bool
  {
    return !is_null(key($this->ir));
  }
}
