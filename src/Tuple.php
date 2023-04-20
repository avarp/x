<?php declare(strict_types=1);
namespace Precise;

use ArrayAccess;
use Countable;
use Iterator;

class Tuple extends TypedValue implements Countable, ArrayAccess, Iterator
{
  use Traits\Countable;
  use Traits\Iterator;

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
    foreach ($this->_type as $i => $elementType) {
      $this->_ir[$i] = purify($elementType, $values[$i]);
    }
  }

  /**
   * Check if the value is a tuple of values with given types.
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
    if (count($value) != count($type)) {
      self::$lastTypeError = errMsg($path, 'a list of ' . count($type) . ' values', $value);
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
      (is_array($value) && array_is_list($value) && count($value) == count($this->_ir)) ||
      (is_object($value) && $value instanceof TypedValue && $value->getType() == $this->_type)
    ) {
      foreach ($value as $i => $v2) {
        $v1 = $this->_ir[$i];
        if (!equal($v1, $v2, $this->_type[$i])) {
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
    if (!is_int($offset) && key_exists($offset, $this->_ir)) {
      err("Offset \"$offset\" is out of bounds.", OFFSET_OUT_OF_BOUNDS);
    }
    // check value
    $elementType = $this->_type[$offset];
    if (Type::check($elementType, $value)) {
      $this->_ir[$offset] = purify($elementType, $value);
    } else {
      err(Type::getLastError(), TYPE_ERROR);
    }
  }

  /**
   * ArrayAccess::offsetUnset implementation
   */
  public function offsetUnset($offset): void
  {
    err('Tuple does not support elements removal.', TUPLE_UNSET_UNSUPPORTED);
  }
}
