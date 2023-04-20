<?php declare(strict_types=1);
namespace Precise;

abstract class TypedValue
{
  /**
   * @var ?array static type
   */
  static ?array $type = null;

  /**
   * @var ?array type of the instance
   */
  protected ?array $_type = null;

  /**
   * @var array internal representation of the instance
   */
  protected array $_ir = [];

  /**
   * Create a typed value
   * @param array $values
   * @param ?array $type
   * @param bool $unsafe
   */
  public function __construct(array $values, ?array $type = null, bool $unsafe = false)
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
    if ($unsafe == false && Type::getComplexTypeClass($this->_type) != static::class) {
      err('Given type doesn\'t match the typed value class', TYPE_DOESNT_MATCH);
    }

    // Check values
    if ($unsafe == false && !static::checkType($this->_type, $values)) {
      err(static::$lastTypeError, TYPE_ERROR);
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
   * Convert typed value to array
   * @return array
   */
  public function toArray(bool $recursive = false): array
  {
    if ($recursive) {
      $result = [];
      foreach ($this->_ir as $key => $value) {
        if (is_object($value) && $value instanceof TypedValue) {
          $result[$key] = $value->toArray(true);
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
