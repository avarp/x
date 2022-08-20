<?php declare(strict_types=1);
namespace Precise\Type;

class StringType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return $literal === 'string';
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return null;
  }


  public static function toLiteral($typeDetails)
  {
    return 'string';
  }


  public static function infer($value): ?TypeSignature
  {
    if (is_string($value)) return new TypeSignature('string');
  }


  public static function check($typeDetails, $value, string $path): string
  {
    if (is_string($value)) return '';
    return Error::typeError($path, 'a string', $value);
  }
}