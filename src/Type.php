<?php declare(strict_types=1);
namespace Precise;

abstract class Type
{
  private static string $lastError = '';
  private static array $scalarTypes = [];
  private static array $complexTypes = ['map' => Map::class];
  private static array $reservedWords = ['any', 'bool', 'int', 'float', 'string'];

  /**
   * Register new type
   * @param string $name
   * @param callable|string $definition
   */
  public static function register(string $name, callable|string $definition): void
  {
    if (
      in_array($name, self::$reservedWords) ||
      class_exists($name) ||
      key_exists($name, self::$scalarTypes) ||
      key_exists($name, self::$complexTypes)
    ) {
      err("Type \"$name\" already exists.", TYPE_ALREADY_EXISTS);
    }

    // Register scalar type
    if (is_string($definition) && class_exists($definition)) {
      if (in_array(TypedValue::class, class_parents($definition))) {
        self::$complexTypes[$name] = $definition;
        return;
      } else {
        err("Class \"$definition\" must implement interface \"" . TypedValue::class . '"', NOT_A_TYPED_VALUE);
      }
    }

    // Register complex type
    elseif (is_callable($definition, true)) {
      self::$scalarTypes[$name] = $definition;
      return;
    }

    // Unknown type definition
    err('Unknown type definition.', UNKNOWN_TYPE);
  }

  /**
   * Get last type error
   * @return string
   */
  public static function getLastError(): string
  {
    return self::$lastError;
  }

  /**
   * Check if type is scalar
   * @param mixed $type
   * @return bool
   */
  public static function isScalar($type): bool
  {
    return is_string($type) &&
      (in_array($type, self::$reservedWords) || class_exists($type) || key_exists($type, self::$scalarTypes));
  }

  /**
   * Get the class of a complex type
   * @param array $type
   * @return string class name or empty string
   */
  public static function getComplexTypeClass(array $type): string
  {
    if (key_exists(0, $type) && is_string($type[0]) && key_exists($type[0], self::$complexTypes)) {
      return self::$complexTypes[$type[0]];
    } else {
      if (array_is_list($type)) {
        if (count($type) == 1) {
          return ListOf::class;
        } else {
          return Tuple::class;
        }
      } else {
        $keys = array_keys($type);
        $allKeysAreStrings = true;
        $allKeysStartWithSemicolon = true;
        foreach ($keys as $key) {
          if (!is_string($key)) {
            $allKeysAreStrings = $allKeysStartWithSemicolon = false;
            break;
          } elseif ($key[0] != ':') {
            $allKeysStartWithSemicolon = false;
          }
        }
        if ($allKeysStartWithSemicolon) {
          return Variants::class;
        } elseif ($allKeysAreStrings) {
          return Record::class;
        } else {
          return '';
        }
      }
    }
  }

  /**
   * Type checking
   * @param mixed $type type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function check($type, $value, string $path = '$value'): bool
  {
    // Scalar types
    if (is_string($type)) {
      // any
      if ($type === 'any') {
        return true;
      }

      // bool
      if ($type === 'bool') {
        if (is_bool($value)) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'a boolean value', $value);
          return false;
        }
      }

      // int
      if ($type === 'int') {
        if (is_int($value)) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'an integer', $value);
          return false;
        }
      }

      // float
      if ($type === 'float') {
        if (is_int($value) || is_float($value)) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'an integer or a floating-point value', $value);
          return false;
        }
      }

      // string
      if ($type === 'string') {
        if (is_string($value)) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'a string', $value);
          return false;
        }
      }

      // an object of some class
      if (class_exists($type)) {
        if (is_object($value) && get_class($value) === $type) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'an instance of "' . $type . '"', $value);
          return false;
        }
      }

      // any registered scalar type
      if (key_exists($type, self::$scalarTypes)) {
        if (self::$scalarTypes[$type]($value)) {
          return true;
        } else {
          self::$lastError = errMsg($path, 'a value with type "' . $type . '"', $value);
          return false;
        }
      }
    }

    // Complex types
    elseif (is_array($type)) {
      if ($value instanceof TypedValue) {
        if ($type == $value->getType()) {
          return true;
        } else {
          self::$lastError = "$path is a typed value with incompatible type.";
        }
      }
      $typeClass = self::getComplexTypeClass($type);
      if ($typeClass) {
        if ($typeClass::checkType($type, $value, $path)) {
          return true;
        } else {
          self::$lastError = $typeClass::getLastTypeError();
          return false;
        }
      }
    }

    err('Unknown type signature.', UNKNOWN_TYPE);
  }

  /**
   * Infer a type of the value
   * @return mixed the type
   */
  public static function of($value)
  {
    if (is_bool($value)) {
      return 'bool';
    }
    if (is_int($value)) {
      return 'int';
    }
    if (is_float($value)) {
      return 'float';
    }
    if (is_string($value)) {
      return 'string';
    }
    if (is_object($value)) {
      if ($value instanceof TypedValue) {
        return $value->getType();
      } else {
        return get_class($value);
      }
    }
    if (is_array($value)) {
      $elTypes = array_map(function ($el) {
        return Type::of($el);
      }, $value);
      if (array_is_list($value)) {
        if (count($value) == 0) {
          return ['any'];
        }
        $uniqueTypes = array_values(array_unique($elTypes, SORT_REGULAR));
        if (count($uniqueTypes) == 1) {
          return $uniqueTypes;
        } else {
          return $elTypes;
        }
      } else {
        $keys = array_keys($value);
        $allKeysAreStrings = true;
        foreach ($keys as $key) {
          if (!is_string($key)) {
            $allKeysAreStrings = false;
            break;
          }
        }
        if ($allKeysAreStrings) {
          return $elTypes;
        }
      }
    }
    return 'any';
  }
}
