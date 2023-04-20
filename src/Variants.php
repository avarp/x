<?php declare(strict_types=1);
namespace Precise;

class Variants extends TypedValue
{
  /**
   * Allowed methods and property names
   */
  protected array $isVariantMethods = [];
  protected array $variantOrMethods = [];
  protected array $variantProperties = [];

  /**
   * Create a variant
   * @param array $values
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $values, ?array $type = null, bool $unsafe = false)
  {
    parent::__construct($values, $type, $unsafe);
    ksort($this->_type);

    foreach ($type as $variant => $variantType) {
      $variant = ltrim($variant, ':');
      $lcfirstVariant = lcfirst($variant);
      $ucfirstVariant = ucfirst($variant);
      if (is_null($variantType)) {
        $isVariantMethods[] = $variantOrMethods[] = $variantProperties[] = null;
      } else {
        $isVariantMethods[] = 'is' . $ucfirstVariant;
        $variantOrMethods[] = $lcfirstVariant . 'Or';
        $variantProperties[] = $lcfirstVariant;
      }
    }

    // Save values
    $variantIndex = self::getVariantIndex($values[0], $this->_type);
    $variantType = self::getVariantType($values[0], $this->_type);
    $this->_ir = [$variantIndex, is_null($values[1]) ? null : purify($variantType, $values[1])];
  }

  /**
   * Check the variant
   * @param mixed $variant
   * @param array $type
   * @return bool
   */
  protected static function isCorectVariant($variant, array $type): bool
  {
    if (is_string($variant) && in_array(':' . $variant, array_keys($type))) {
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
      return array_search(':' . $variant, array_keys($type));
    }
  }

  /**
   * Get variant type
   * @param string|int $variant
   * @param array $type
   * @return mixed variant type
   */
  protected static function getVariantType(string|int $variant, array $type): mixed
  {
    if (is_int($variant)) {
      return $type[array_keys($type)[$variant]];
    } else {
      return $type[':' . $variant];
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
    if (!is_array($value) || !array_is_list($value) || count($value) != 2) {
      self::$lastTypeError = errMsg($path, 'an array of 2 elements: a variant and its value', $value);
      return false;
    }
    if (!is_string($value[0]) && !is_int($value[0])) {
      self::$lastTypeError = errMsg($path . '[0]', 'a string or an integer', $value);
      return false;
    }
    if (!self::isCorectVariant($value[0], $type)) {
      if (is_int($value[0])) {
        self::$lastTypeError = errMsg($path . '[0]', 'an integer in range [0...' . (count($type) - 1) . ']', $value);
      } else {
        $variants = array_map(function ($x) {
          return ltrim($x, ':');
        }, array_keys($type));
        self::$lastTypeError = errMsg(
          $path . '[0]',
          'any value from list ["' . implode('", "', $variants) . '"]',
          $value
        );
      }
      return false;
    }
    $variantType = self::getVariantType($value[0], $type);
    if (is_null($variantType)) {
      if (!is_null($value[1])) {
        self::$lastTypeError = errMsg($path . '[1]', 'null', $value);
        return false;
      }
    } else {
      if (!Type::check($variantType, $value[1], $path . '[1]')) {
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
  public function equal($value): bool
  {
    if (
      (is_array($value) &&
        array_is_list($value) &&
        count($value) == 2 &&
        self::isCorectVariant($value[0], $this->_type)) ||
      (is_object($value) && $value instanceof TypedValue && $value->getType() == $this->_type)
    ) {
      if (is_object($value)) {
        $value = $value->toArray();
      } else {
        $value[0] = self::getVariantIndex($value[0], $this->_type);
      }
      [$variant1, $v1] = $this->_ir;
      [$variant2, $v2] = $value;
      if ($variant1 != $variant2) {
        return false;
      }
      $variantType = self::getVariantType($variant1, $this->_type);
      if (is_null($variantType)) {
        if (!is_null($v1) || !is_null($v2)) {
          return false;
        }
      } elseif (!equal($v1, $v2, $variantType)) {
        return false;
      }
      return true;
    }
    return false;
  }

  /**
   * Methods is{variant}() and {variant}Or(mixed $default)
   */
  public function __call(string $name, array $arguments): mixed
  {
    if (false !== ($variantIndex = array_search($name, $this->isVariantMethods))) {
      return $this->_ir[0] == $variantIndex;
    }
    if (false !== ($variantIndex = array_search($name, $this->variantOrMethods))) {
      if (count($arguments) != 1) {
        err('Missing required parameter for method ' . static::class . "::$name", VARIANT_MISSED_PARAM);
      }
      return $this->_ir[0] == $variantIndex ? $this->_ir[1] : $arguments[0];
    }
    err('Unknown method  ' . static::class . "::$name", VARIANT_UNKNOWN_METHOD);
  }

  /**
   * Constructor shortcuts
   */
  public static function __callStatic(string $name, array $arguments): mixed
  {
    static $allowedMethods = null;
    if (is_null($allowedMethods)) {
      if (static::$type) {
        $allowedMethods = array_map(function ($x) {
          return lcfirst(ltrim($x, ':'));
        }, array_keys(static::$type));
      } else {
        $allowedMethods = [];
      }
    }
    if (false !== ($variantIndex = array_search($name, $allowedMethods))) {
      $variantValue = count($arguments) > 0 ? $arguments[0] : null;
      return new static([$variantIndex, $variantValue]);
    } else {
      err('Unknown static method  ' . static::class . "::$name", VARIANT_UNKNOWN_METHOD);
    }
  }

  /**
   * Variant value getter
   * @param string $propName
   * @return mixed
   */
  public function __get(string $propName): mixed
  {
    if (false !== ($variantIndex = array_search($propName, $this->variantProperties))) {
      if ($this->_ir[0] == $variantIndex) {
        return $this->_ir[1];
      }
    }
    err('Unknown property ' . static::class . "::\$$propName", VARIANT_UNKNOWN_PROPERTY);
  }

  /**
   * Check if property exists
   * @param string $propName
   * @return bool
   */
  public function __isset(string $propName): bool
  {
    if (false !== ($variantIndex = array_search($propName, $this->variantProperties))) {
      if ($this->_ir[0] == $variantIndex) {
        return true;
      }
    }
    return false;
  }

  /**
   * Modifying properties is prohibited
   * @throws Exception
   */
  public function __set(string $_, mixed $__): void
  {
    err('Variants are immutable.', VARIANT_IS_IMMUTABLE);
  }

  /**
   * Unsetting properties is prohibited
   * @throws Exception
   */
  public function __unset(string $_): void
  {
    err('Variants are immutable.', VARIANT_IS_IMMUTABLE);
  }
}
