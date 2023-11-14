<?php declare(strict_types=1);
namespace Precise;

class Record extends TypedValue implements \Iterator
{
  use Iterator;

  /**
   * Create a record
   * @param array $values
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $values, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($values, $type, $unsafe);

    // Save values
    ksort($this->_type);
    $this->_ir = [];
    foreach ($this->_type as $propName => $propType) {
      $this->_ir[$propName] = purify($propType, $values[$propName]);
    }
  }

  /**
   * Check if the value is a record with given properties and types. Method is only for internal purposes.
   * @internal
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function checkType(array $type, $value, string $path = '$value'): bool
  {
    if (!is_array($value) || array_is_list($value)) {
      self::$lastTypeError = errMsg($path, 'an associative array', $value);
      return false;
    }
    $validKeys = array_keys($type);
    $givenKeys = array_keys($value);
    $missedKeys = array_diff($validKeys, $givenKeys);
    $extraKeys = array_diff($givenKeys, $validKeys);
    if ($missedKeys || $extraKeys) {
      self::$lastTypeError = "$path has";
      if ($missedKeys) {
        self::$lastTypeError .=
          ' missing required key' . (count($missedKeys) > 1 ? 's' : '') . ' "' . implode('", "', $missedKeys) . '"';
      }
      if ($extraKeys) {
        self::$lastTypeError .=
          ($missedKeys ? ' and' : '') .
          ' unknown key' .
          (count($extraKeys) > 1 ? 's' : '') .
          ' "' .
          implode('", "', $extraKeys) .
          '"';
      }
      self::$lastTypeError .= '.';
      return false;
    }
    foreach ($type as $propName => $elementType) {
      if (!Type::check($elementType, $value[$propName], $path . "->$propName")) {
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
      (is_array($value) && $this->irHasSameKeysAs($value)) ||
      (is_object($value) && $value instanceof self && $value->getType() == $this->_type)
    ) {
      foreach ($value as $propName => $v2) {
        $v1 = $this->_ir[$propName];
        if (!equal($v1, $v2, $this->_type[$propName])) {
          return false;
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Check that IR has same keys as given array
   * @param array $value
   * @return bool
   */
  protected function irHasSameKeysAs(array $value): bool
  {
    $keys = array_keys($value);
    asort($keys);
    return $keys === array_keys($this->_ir);
  }

  /**
   * Record property getter
   * @return mixed
   */
  public function __get(string $propName): mixed
  {
    if (key_exists($propName, $this->_ir)) {
      return $this->_ir[$propName];
    } else {
      err("Unknown property \"$propName\"", RECORD_UNKNOWN_PROPERTY);
    }
  }

  /**
   * Record property setter
   * @param string $propName
   * @param mixed $value
   */
  public function __set(string $propName, mixed $value): void
  {
    if (key_exists($propName, $this->_ir)) {
      $propType = $this->_type[$propName];
      if (Type::check($propType, $value)) {
        $this->_ir[$propName] = purify($propType, $value);
      } else {
        err(Type::getLastError(), TYPE_MISMATCH);
      }
    } else {
      err("Unknown property \"$propName\"", RECORD_UNKNOWN_PROPERTY);
    }
  }

  /**
   * Check if property exists
   * @param string $propName
   * @return bool
   */
  public function __isset(string $propName): bool
  {
    return key_exists($propName, $this->_ir);
  }

  /**
   * Unsetting properties is prohibited
   * @throws Exception
   */
  public function __unset(string $propName): void
  {
    err("Record property \"$propName\" can't be removed", RECORD_UNSET_UNSUPPORTED);
  }
}
