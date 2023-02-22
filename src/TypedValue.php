<?php declare(strict_types=1);
namespace Precise;

use Exception;

abstract class TypedValue
{
  /**
   * @var ?array static type
   */
  static ?array $type = null;

  /**
   * @var ?array type of the instance
   */
  protected ?array $itype = null;

  /**
   * @var mixed internal representation of the instance
   */
  protected $ir = null;

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
      $this->itype = static::$type;
    } elseif ($type) {
      $this->itype = $type;
    } else {
      throw new Exception('Please specify type of the typed value', ERR::TYPE_NOT_DEFINED);
    }

    // Check type
    if ($unsafe == false && Type::getComplexTypeClass($this->itype) != static::class) {
      throw new Exception('Given type doesn\'t match the typed value class', ERR::TYPE_DOESNT_MATCH);
    }

    // Check values
    if ($unsafe == false && !static::checkType($this->itype, $values)) {
      throw new TypeError(static::$lastTypeError, ERR::TYPE_ERROR);
    }
  }

  /**
   * Check if the value matches the type
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
    return $this->itype;
  }

  /**
   * @var string last type error
   */
  protected static string $lastTypeError = '';

  /**
   * Get last type error
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
    if (!is_array($this->ir)) {
      throw new Exception('Internal representation is not an array', ERR::IR_ISNT_ARRAY);
    }
    if ($recursive) {
      $result = [];
      foreach ($this->ir as $key => $value) {
        if (is_object($value) && $value instanceof TypedValue) {
          $result[$key] = $value->toArray(true);
        } else {
          $result[$key] = $value;
        }
      }
      return $result;
    } else {
      return $this->ir;
    }
  }
}
