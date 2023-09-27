<?php declare(strict_types=1);
namespace Precise;

use ArrayAccess;
use Countable;
use Iterator;

class ListOf extends TypedValue implements Countable, ArrayAccess, Iterator
{
  use Traits\Countable;
  use Traits\Iterator;
  use Traits\ListOfMethods;

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
}
