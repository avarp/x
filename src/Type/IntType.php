<?php declare(strict_types=1);
namespace Precise\Type;

class IntType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return $literal === 'int';
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return null;
  }


  public static function toLiteral($typeDetails)
  {
    return 'int';
  }


  public static function infer($value): ?TypeSignature
  {
    if (is_int($value)) return new TypeSignature('int');
  }


  public static function check($typeDetails, $value, string $path): string
  {
    if (is_int($value)) return '';
    return Error::typeError($path, 'an integer', $value);
  }
}