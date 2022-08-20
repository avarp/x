<?php declare(strict_types=1);
namespace Precise\Type;

class BoolType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return $literal === 'bool';
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return null;
  }


  public static function toLiteral($typeDetails)
  {
    return 'bool';
  }


  public static function infer($value): ?TypeSignature
  {
    if (is_bool($value)) return new TypeSignature('bool');
  }


  public static function check($typeDetails, $value, string $path): string
  {
    if (is_bool($value)) return '';
    return Error::typeError($path, 'a boolean', $value);
  }
}