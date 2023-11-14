<?php declare(strict_types=1);
namespace Precise;

abstract class TypedValue implements \JsonSerializable
{
  /**
   * @var ?array static type
   */
  static $type = null;

  /**
   * @var ?array type of the instance
   */
  protected ?array $_type = null;

  /**
   * @var mixed internal representation of the instance
   */
  protected $_ir = null;

  /**
   * Create a typed value
   * @param mixed $value
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct($value, ?array $type = null, bool $unsafe = false)
  {
    // Get type
    if (static::$type) {
      $this->_type = static::$type;
    } elseif ($type) {
      $this->_type = $type;
    } else {
      err('Please specify type of the typed value', TYPE_NOT_DEFINED);
    }

    // Check type
    $class = Type::getComplexTypeClass($this->_type);
    if ($unsafe == false && $class != static::class && !in_array($class, class_parents($this))) {
      err('Given type doesn\'t match the typed value class', TYPE_DOESNT_MATCH);
    }

    // Check values
    if ($unsafe == false && !static::checkType($this->_type, $value)) {
      err(static::$lastTypeError, TYPE_MISMATCH);
    }
  }

  /**
   * Check if the value matches the type
   * @internal
   * @param array $type a complex type signature
   * @param mixed $value to check
   * @param string $path the name of variable for error messages
   * @return bool
   */
  abstract public static function checkType(array $type, $value, string $path = '$value'): bool;

  /**
   * Check equality of this value with other value
   * @param mixed $value
   * @return bool
   */
  abstract public function equal($value): bool;

  /**
   * Get the type of the instance
   * @return ?array a complex type signature
   */
  public function getType(): ?array
  {
    return $this->_type;
  }

  /**
   * @var string last type error
   */
  protected static string $lastTypeError = '';

  /**
   * Get last type error
   * @internal
   * @return string
   */
  public static function getLastTypeError(): string
  {
    return self::$lastTypeError;
  }

  /**
   * Convert typed value to JSON-serializeable value
   * @return mixed
   */
  public function jsonSerialize(): mixed
  {
    if (is_array($this->_ir)) {
      $result = [];
      foreach ($this->_ir as $key => $value) {
        if (is_object($value) && $value instanceof TypedValue) {
          $result[$key] = $value->jsonSerialize();
        } else {
          $result[$key] = $value;
        }
      }
      return $result;
    } else {
      return $this->_ir;
    }
  }
}
