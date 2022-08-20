<?php declare(strict_types=1);
namespace Precise\Type;

class FloatType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return $literal === 'float';
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return null;
  }


  public static function toLiteral($typeDetails)
  {
    return 'float';
  }


  public static function infer($value): ?TypeSignature
  {
    if (is_float($value) || is_int($value)) return new TypeSignature('float');
  }


  public static function check($typeDetails, $value, string $path): string
  {
    if (is_float($value) || is_int($value)) return '';
    return Error::typeError($path, 'a float', $value);
  }
}