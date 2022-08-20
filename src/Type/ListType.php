<?php declare(strict_types=1);
namespace Precise\Type;

class ListType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    return is_array($literal) && array_is_list($literal) && count($literal) == 1 && TypeSignature::isKnownLiteral($literal[0]);
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return new TypeSignature($literal[0], $context);
  }


  public static function toLiteral($innerType)
  {
    return [$innerType->toLiteral()];
  }


  public static function infer($value): ?TypeSignature
  {
    return null;
  }


  public static function check($innerType, $value, string $path): string
  {
     
    return '';
  }
}