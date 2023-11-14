<?php declare(strict_types=1);
namespace Precise;

class Enum extends TypedValue
{
  /**
   * Create a variant
   * @param int|string $value
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(int|string $value, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($value, $type, $unsafe);
    
    // Unify variants
    foreach ($this->_type as $n => $variant) {
      $this->_type[$n] = ':'.ucfirst(ltrim($variant, ':'));
    }

    // Save values
    $this->_ir = self::getVariantIndex($value, $this->_type);
  }

  /**
   * Check the variant
   * @param mixed $variant
   * @param array $type
   * @return bool
   */
  protected static function isCorrectVariant($variant, array $type): bool
  {
    if (is_string($variant) && (in_array(':' . ucfirst($variant), $type) || in_array(':' . lcfirst($variant), $type))) {
      return true;
    }
    if (is_int($variant) && $variant >= 0 && $variant < count($type)) {
      return true;
    }
    return false;
  }

  /**
   * Get index of the variant
   * @param string|int the variant
   * @param array $type
   * @return int
   */
  protected static function getVariantIndex(string|int $variant, array $type): int
  {
    if (is_int($variant)) {
      return $variant;
    } else {
      $result = array_search(':' . ucfirst($variant), $type);
      if ($result === false) {
        $result = array_search(':' . lcfirst($variant), $type);
      }
      return $result;
    }
  }

  /**
   * Check if the value is a variant with a payload of a given type.
   * @internal
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  public static function checkType(array $type, $value, string $path = '$value'): bool
  {
    if (!self::isCorrectVariant($value, $type)) {
      if (is_int($value)) {
        self::$lastTypeError = errMsg($path, 'an integer in range [0...' . (count($type) - 1) . ']', $value);
      } elseif (is_string($value)) {
        $variants = array_map(function ($x) {
          return ucfirst(ltrim($x, ':'));
        }, $type);
        self::$lastTypeError = errMsg(
          $path,
          'any value from list ["' . implode('", "', $variants) . '"]',
          $value
        );
      } else {
        self::$lastTypeError = errMsg($path, 'a string or an integer', $value);
      }
      return false;
    }
    return true;
  }

  /**
   * Equality check
   * @param mixed $value
   * @return bool
   */
  public function equal($value): bool
  {
    if (
      is_int($value) ||
      is_string($value) ||
      (is_object($value) && $value instanceof self && $value->getType() == $this->_type)
    ) {
      if (is_object($value)) {
        return $this->_ir == $value->toInt();
      } else {
        if (self::isCorrectVariant($value, $this->_type)) {
          return $this->_ir == self::getVariantIndex($value, $this->_type);
        }
      }
    }
    return false;
  }

  /**
   * Convert to integer
   * @return int
   */
  public function toInt(): int
  {
    return $this->_ir;
  }

  /**
   * Convert to string
   * @return string
   */
  public function toString(): string
  {
    return ltrim($this->_type[$this->_ir], ':');
  }

  /**
   * Methods is{variant}()
   */
  public function __call(string $name, array $_): mixed
  {
    if (substr($name, 0, 2) == 'is' && self::isCorrectVariant(substr($name, 2), $this->_type)) {
      return $this->_ir == self::getVariantIndex(substr($name, 2), $this->_type);
    }
    err('Unknown method ' . static::class . "::$name", ENUM_UNKNOWN_METHOD);
  }

  /**
   * Get all cases
   * @return array list of all possible variants of enum
   */
  public static function cases(): array
  {
    if (static::$type) {
      return array_map(function($x) {
        return new static(ltrim($x, ':'));
      }, static::$type);
    }
    err('Unknown static method ' . static::class . "::cases", ENUM_UNKNOWN_METHOD);
  }

  /**
   * Constructor shortcuts
   */
  public static function __callStatic(string $name, array $_): mixed
  {
    if (static::$type) {
      if (self::isCorrectVariant($name, static::$type)) {
        $variantIndex = self::getVariantIndex($name, static::$type);
        return new static($variantIndex); 
      }
    }
    err('Unknown static method ' . static::class . "::$name", ENUM_UNKNOWN_METHOD);
  }
}
