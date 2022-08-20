<?php declare(strict_types=1);
namespace Precise\Type;

use Precise\Error;

final class TypeSignature implements \JsonSerializable
{
  /**
   * @var string name of the class describing the type
   */
  private $type;

  /**
   * @var mixed additional data related to the type
   */
  private $details;

  /**
   * @var object known names of types
   */
  private static $types = [
    'matchLiteral' => [
      AnyType::class,
      IntType::class,
      FloatType::class,
      StringType::class,
      BoolType::class,
      ObjectType::class,
      ListType::class,
      TupleType::class,
      EnumType::class,
      MapType::class,
      VariantsType::class,
      RecordType::class
    ],
    'infer' => [
      BoolType::class,
      FloatType::class,
      IntType::class,
      StringType::class,
      EnumType::class,
      ListType::class,
      TupleType::class,
      MapType::class,
      VariantsType::class,
      RecordType::class,
      ObjectType::class
    ]
  ];


  /**
   * Register custom type
   * @param string $typeClass name of the class implementing TypeInterface
   */
  public static function registerType(string $typeClass): void
  {
    if (!class_exists($typeClass)) Error::classNotExist("Class $typeClass does not exist.");
    if (!in_array(TypeInterface::class, class_implements($typeClass))) Error::incompatibleClass("Class $typeClass does not implement ".TypeInterface::class.".");
    
    if (!in_array($typeClass, self::$types['matchLiteral'])) {
      if (defined($typeClass.'::MATCH_LITERAL_BEFORE')) {
        $before = $typeClass::MATCH_LITERAL_BEFORE;
        if (false !== $pos = array_search($before, self::$types['matchLiteral'])) {
          array_splice(self::$types['matchLiteral'], $pos, 0, $typeClass);
        } else {
          self::$types['matchLiteral'][] = $type;
        }
      }
    }

    if (!in_array($typeClass, self::$types['infer'])) {
      if (defined($typeClass.'::INFER_BEFORE')) {
        $before = $typeClass::INFER_BEFORE;
        if (false !== $pos = array_search($before, self::$types['infer'])) {
          array_splice(self::$types['infer'], $pos, 0, $typeClass);
        } else {
          self::$types['infer'][] = $type;
        }
      }
    }
  }


  /**
   * Constructor of the type signature
   * @param mixed $literal literal form of the type
   * @param string $context the class with defined type-variables
   */
  public function __construct($literal, string $context='')
  {
    // resolve type-variables
    if ($context) {
      if (is_string($literal) && strlen($literal) == 1 && property_exists($context, $literal)) {
        $literal = $context::$$literal;
      }
    }

    // search for literal match
    foreach (self::$types['matchLiteral'] as $type) if ($type::matchLiteral($literal)) {
      $this->type = $type;
      break;
    }
    if (!$this->type) Error::unknownTypeSignature('Type signature is unknown.');

    // get details of the type
    $this->details = $this->type::getTypeDetails($literal, $context);
  }


  /**
   * Check if the literal is known
   * @param mixed $literal literal form of the type
   * @return bool
   */
  public static function isKnownLiteral($literal): bool
  {
    foreach (self::$types['matchLiteral'] as $type) if ($type::matchLiteral($literal)) return true;
    return false;
  }


  /**
   * Check that type signature has given type
   * @param string $type class name of the type
   * @return bool
   */
  public function is(string $type): bool
  {
    return $this->type == $type;
  }


  /**
   * Get type details
   * @return mixed
   */
  public function getDetails()
  {
    return $this->details;
  }


  /**
   * Turn the type back to literal form
   * @return mixed
   */
  public function toLiteral()
  {
    return $this->type::toLiteral($this->details);
  }


  /**
   * Method of the JsonSerializable interface
   */
  public function jsonSerialize()
  {
    return json_encode($this->toLiteral());
  }


  /**
   * Infer the type
   */
  public static function infer($value): TypeSignature
  {
    foreach (self::$types['infer'] as $type) if ($result = $type::infer($value)) {
      return $result;
    }
    return AnyType::infer($value);
  }


  /**
   * Type check
   */
  public function check($value, $path='Value'): string
  {
    return $this->type::check($this->details, $value, $path);
  }
}