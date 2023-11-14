<?php declare(strict_types=1);
namespace Precise;

class Map extends TypedValue implements \Countable, \ArrayAccess, \Iterator
{
  use MethodsForMap;

  /**
   * Create a map
   * @param array $array
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $array, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($array, $type, $unsafe);

    // Get keys and values
    if (!array_is_list($array)) {
      $keys = array_keys($array);
      $values = array_values($array);
    } else {
      $keys = $values = [];
      foreach ($array as $n => $pair) {
        $keys[] = $pair[0];
        $values[] = $pair[1];
      }
    }

    // Save keys and map of values
    $valuesType = $this->_type[2];
    $this->_ir = [
      'keys' => $keys,
      'strKeys' => [],
      'values' => [],
    ];
    foreach ($values as $n => $value) {
      $key = self::keyToString($keys[$n]);
      $this->_ir['strKeys'][] = $key;
      $this->_ir['values'][$key] = purify($valuesType, $value);
    }
  }

  /**
   * Convert map to array of pairs
   * @return mixed
   */
  public function jsonSerialize(): mixed
  {
    $result = [];
    $n = 0;
    foreach ($this->_ir['values'] as $value) {
      if (is_object($value) && $value instanceof TypedValue) {
        $value = $value->jsonSerialize();
      }
      $key = $this->_ir['keys'][$n++];
      $result[] = [$key, $value];
    }
    return $result;
  }

  /**
   * Convert map to array of pairs
   * @return array
   */
  public function toArray(bool $recursive = false): array
  {
    if ($recursive) {
      return $this->jsonSerialize();
    } else {
      $result = [];
      $n = 0;
      foreach ($this->_ir['values'] as $value) {
        $key = $this->_ir['keys'][$n++];
        $result[] = [$key, $value];
      }
      return $result;
    }
  }

  /**
   * Check if this instance can be converted to an associative array
   * @return bool
   */
  public function canBeConvertedToAssocArray(): bool
  {
    $keysType = $this->_type[1];
    if (!in_array($keysType, ['string', 'int', 'any'])) {
      return false;
    }
    if ($keysType == 'any') {
      foreach ($this->_ir['keys'] as $key) {
        if (!in_array(gettype($key), ['string', 'integer'])) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Convert map to an associative array
   * @param bool $recursive true for recursive conversion.
   * @param bool $strict if true, it throws an exception for maps that can't be converted. Otherwise it uses toArray as a fallback.
   * @param string $path name of variable for error message.
   * @return array
   */
  public function toAssocArray(bool $recursive = false, bool $strict = false, string $path = '$this'): array
  {
    if ($this->canBeConvertedToAssocArray()) {
      $usePairs = false;
    } elseif (!$strict) {
      $usePairs = true;
    } else {
      err("$path can't be represented as PHP associative array.", MAP_CANT_BE_ASSOC);
    }
    $result = [];
    $n = 0;
    foreach ($this->_ir['values'] as $value) {
      $key = $this->_ir['keys'][$n++];
      if ($recursive && is_object($value) && $value instanceof TypedValue) {
        if ($value instanceof Map) {
          $keysType = $this->_type[1];
          if ($keysType === 'string') {
            $path .= "['" . $key . "']";
          } elseif ($keysType === 'int' || $keysType === 'float') {
            $path .= '[' . $key . ']';
          } elseif ($keysType === 'bool') {
            $path .= '[' . ($key ? 'true' : 'false') . ']';
          } else {
            $path .= '[' . nth($n) . ']';
          }
          $value = $value->toAssocArray(true, $strict, $path);
        } else {
          $value = $value->jsonSerialize();
        }
      }
      if ($usePairs) {
        $result[] = [$key, $value];
      } else {
        $result[$key] = $value;
      }
    }
    return $result;
  }

  /**
   * Check if the value is a map with given types of keys and values. Method is only for internal purposes.
   * @internal
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function checkType(array $type, $value, string $path = '$value'): bool
  {
    if (!is_array($value)) {
      self::$lastTypeError = errMsg($path, 'an associative array or list of pairs', $value);
      return false;
    }

    // Get keys and values
    if (!array_is_list($value)) {
      $keys = array_keys($value);
      $values = array_values($value);
    } else {
      $keys = $values = [];
      foreach ($value as $n => $pair) {
        if (!is_array($pair) || !array_is_list($pair) || count($pair) != 2) {
          self::$lastTypeError = errMsg($path . "[$n]", 'a pair of values', $pair);
          return false;
        }
        $keys[] = $pair[0];
        $values[] = $pair[1];
      }
    }

    // Check keys
    $keysType = $type[1];
    foreach ($keys as $n => $key) {
      if (!Type::check($keysType, $key, nth($n) . " key of $path")) {
        self::$lastTypeError = Type::getLastError();
        return false;
      }
    }

    // Check values
    $valuesType = $type[2];
    $nthValueOfPath = function ($n) use ($path, $keys, $keysType) {
      if ($keysType === 'string') {
        return $path . "['" . $keys[$n] . "']";
      } elseif ($keysType === 'int' || $keysType === 'float') {
        return $path . '[' . $keys[$n] . ']';
      } elseif ($keysType === 'bool') {
        return $path . '[' . ($keys[$n] ? 'true' : 'false') . ']';
      } else {
        return nth($n) . " value of $path";
      }
    };
    foreach ($values as $n => $value) {
      if (!Type::check($valuesType, $value, $nthValueOfPath($n))) {
        self::$lastTypeError = Type::getLastError();
        return false;
      }
    }
    return true;
  }

  /**
   * Equality check
   * @param mixed $value
   * @return bool
   */
  public function equal(mixed $value): bool
  {
    if (is_array($value) || (is_object($value) && $value instanceof self && $value->getType() == $this->_type)) {
      // Fast check on count
      if (count($this) != count($value)) {
        return false;
      }
      // Compare with array
      if (is_array($value)) {
        // Compare with array of pairs
        if (array_is_list($value)) {
          $seenKeys = array_combine(array_keys($this->_ir['values']), array_fill(0, count($this), false));
          foreach ($value as $pair) {
            if (!is_array($pair) || !array_is_list($pair) || count($pair) != 2) {
              return false;
            }
            $k = self::keyToString($pair[0]);
            if (!key_exists($k, $this->_ir['values'])) {
              return false;
            }
            $v1 = $this->_ir['values'][$k];
            $v2 = $pair[1];
            $valuesType = $this->_type[2];
            if (!equal($v1, $v2, $valuesType)) {
              return false;
            }
            $seenKeys[$k] = true;
          }
          foreach ($seenKeys as $seen) {
            if (!$seen) {
              return false;
            }
          }
        }
        // Compare with associative array
        else {
          $keysType = $this->_type[1];
          if ($keysType != 'string' && $keysType != 'int') {
            return false;
          }
          $valuesType = $this->_type[2];
          foreach ($this as $k => $v1) {
            if (!key_exists($k, $value)) {
              return false;
            }
            $v2 = $value[$k];
            if (!equal($v1, $v2, $valuesType)) {
              return false;
            }
          }
        }
      }
      // Compare with another Map
      else {
        $valuesType = $this->_type[2];
        foreach ($this as $k => $v1) {
          if (!isset($value[$k])) {
            return false;
          }
          $v2 = $value[$k];
          if (!equal($v1, $v2, $valuesType)) {
            return false;
          }
        }
      }
      return true;
    }
    return false;
  }

  /**
   * Turn key of any type to string
   * @param mixed $key
   * @return string
   */
  protected static function keyToString(mixed $key): string
  {
    if (is_string($key)) {
      return 's:' . $key;
    }
    if (is_bool($key)) {
      return $key ? 'b:1' : 'b:0';
    }
    if (is_null($key)) {
      return 'n';
    }
    if (is_int($key)) {
      return 'i:' . $key;
    }
    if (is_float($key)) {
      return 'f:' . $key;
    }
    if (is_resource($key)) {
      return 'r:' . get_resource_id($key);
    }
    if (is_object($key)) {
      return 'o:' . spl_object_id($key);
    }
    // Finally $key is either an array or typed value
    if (is_array($key) && !array_is_list($key)) {
      ksort($key);
    }
    if ($key instanceof TypedValue) {
      $key = $key->jsonSerialize(true);
    }
    if (array_is_list($key)) {
      return 'l:' . md5(implode('', array_map(self::keyToString(...), $key)));
    } else {
      return 'm:' .
        md5(implode('', array_map(self::keyToString(...), $key))) .
        ':' .
        md5(implode('', array_map(strval(...), array_keys($key))));
    }
  }

  /**
   * Iterator::current implementation
   */
  public function current(): mixed
  {
    return $this->_ir['values'][current($this->_ir['strKeys'])];
  }

  /**
   * Iterator::key implementation
   */
  public function key(): mixed
  {
    return $this->_ir['keys'][key($this->_ir['strKeys'])];
  }

  /**
   * Iterator::next implementation
   */
  public function next(): void
  {
    next($this->_ir['strKeys']);
  }

  /**
   * Iterator::rewind implementation
   */
  public function rewind(): void
  {
    reset($this->_ir['strKeys']);
  }

  /**
   * Iterator::valid implementation
   */
  public function valid(): bool
  {
    return !is_null(key($this->_ir['strKeys']));
  }

  /**
   * Countable::count implementation
   */
  public function count(): int
  {
    return count($this->_ir['values']);
  }

  /**
   * ArrayAccess::offsetExists implementation
   */
  public function offsetExists($offset): bool
  {
    return key_exists(self::keyToString($offset), $this->_ir['values']);
  }

  /**
   * ArrayAccess::offsetGet implementation
   */
  public function offsetGet($offset): mixed
  {
    $offset = self::keyToString($offset);
    if (!key_exists($offset, $this->_ir['values'])) {
      err('The map key does not exist.', OFFSET_DOESNT_EXIST);
    }
    return $this->_ir['values'][$offset];
  }

  /**
   * ArrayAccess::offsetSet implementation
   */
  public function offsetSet($offset, $value): void
  {
    // check offset
    $keysType = $this->_type[1];
    if (!Type::check($keysType, $offset, 'Offset')) {
      err(Type::getLastError(), TYPE_MISMATCH);
    }
    // check value
    $valuesType = $this->_type[2];
    if (Type::check($valuesType, $value)) {
      $offset = purify($keysType, $offset);
      $this->_ir['keys'][] = $offset;
      $this->_ir['values'][self::keyToString($offset)] = purify($valuesType, $value);
    } else {
      err(Type::getLastError(), TYPE_MISMATCH);
    }
  }

  /**
   * ArrayAccess::offsetUnset implementation
   */
  public function offsetUnset($offset): void
  {
    $offset = self::keyToString($offset);
    if (!key_exists($offset, $this->_ir['values'])) {
      err('The map key does not exist.', OFFSET_DOESNT_EXIST);
    }
    array_splice($this->_ir['keys'], array_search($offset, array_keys($this->_ir['values'])), 1);
    unset($this->_ir['values'][$offset]);
  }
}
