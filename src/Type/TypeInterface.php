<?php declare(strict_types=1);
namespace Precise\Type;

interface TypeInterface
{
  /**
   * Check whether the literal matches the type
   * @param mixed $literal literal type definition
   * @return bool
   */
  public static function matchLiteral($literal): bool;

  /**
   * Get any details needed for the type
   * @param mixed $literal literal type definition that matches this type
   * @param string $context the class with defined type-variables
   * @return mixed
   */
  public static function getTypeDetails($literal, string $context);

  /**
   * Get literal form of the type
   * @param mixed $typeDetails the same value that was returned by getTypeDetails
   */
  public static function toLiteral($typeDetails);

  /**
   * Infer type for the value
   * @param mixed $value
   * @return ?TypeSignature the type or null ifthe value does not belong to the type
   */
  public static function infer($value): ?TypeSignature;

  /**
   * Check type of the value
   * @param mixed $typeDetails the same value that was returned by getTypeDetails
   * @param mixed $value
   * @return string a human-readable error message or empty string if type matches
   */
  public static function check($typeDetails, $value): string;
}