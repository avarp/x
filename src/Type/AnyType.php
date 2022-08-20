<?php declare(strict_types=1);
namespace Precise\Type;

class AnyType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return $literal === 'any';
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return null;
  }


  public static function toLiteral($typeDetails)
  {
    return 'any';
  }


  public static function infer($value): ?TypeSignature
  {
    return new TypeSignature('any');
  }


  public static function check($typeDetails, $value, string $path): string
  {
    return '';
  }
}