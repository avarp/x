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
    'matchLiteralOrder' => [
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
    'inferOrder' => [
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
   * @param string $type name of the class implementing TypeInterface
   * @param string $beforeThisWhenConstruct 
   * @param string $beforeThisWhenInfer 
   */
  public static function registerType(string $type, string $beforeThisWhenConstruct = '', string $beforeThisWhenInfer = ''): void
  {
    if (!class_exists($type)) Error::classNotExist("Class $type does not exist.");
    if (!in_array(TypeInterface::class, class_implements($type))) error(Error::CLASS_ILLEGAL, "Class $type does not implement ".TypeInterface::class.".");
    if (false !== $pos = array_search($type, self::$constructableTypes)) {
      array_splice(self::$constructableTypes, $pos, 1);
    }
    if (false !== $pos = array_search($type, self::$inferrableTypes)) {
      array_splice(self::$inferrableTypes, $pos, 1);
    }
    if ($beforeThisWhenConstruct && false !== $pos = array_search($beforeThisWhenConstruct, self::$constructableTypes)) {
      array_splice(self::$constructableTypes, $pos, 0, $type);
    } else {
      self::$constructableTypes[] = $type;
    }
    if ($beforeThisWhenInfer && false !== $pos = array_search($beforeThisWhenInfer, self::$inferrableTypes)) {
      array_splice(self::$inferrableTypes, $pos, 0, $type);
    } else {
      self::$inferrableTypes[] = $type;
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
    foreach (self::$types->matchLiteralOrder as $type) if ($type::matchLiteral($literal)) {
      $this->type = $type;
      break;
    }
    if (!$this->type) error(Error::INCORRECT_TYPE_SIGNATURE, 'Type signature is incorrect.');

    // get details of the type
    $this->details = $this->type::getTypeDetails($literal, $context);
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
    foreach (self::$inferrableTypes as $type) if ($result = $type::infer($value)) {
      return $result;
    }
    return AnyType::infer($value);
  }


  /**
   * Type check
   */
  public function check($value): string
  {
    return $this->type::check($value);
  }
}