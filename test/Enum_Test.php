<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class Color extends Enum {
  static $type = [':Red', ':Green', ':Blue'];
}

class Enum_Test extends TestCase
{
  public function test___construct()
  {
    $x = new Enum('green', [':Red', ':Green', ':blue']);
    $this->assertSame([':Red', ':Green', ':Blue'], getPrivateProp($x, '_type'));
    $this->assertSame(1, getPrivateProp($x, '_ir'));
  }

  public function test_checkType()
  {
    $type = [':red', ':green', ':blue'];

    // Not int nor string
    $this->assertFalse(Enum::checkType($type, null));
    $this->assertSame(
      '$value is expected to be a string or an integer but it is NULL.',
      Enum::getLastTypeError()
    );

    // String, but not one of variants
    $this->assertFalse(Enum::checkType($type, 'Yellow'));
    $this->assertSame(
      '$value is expected to be any value from list ["Red", "Green", "Blue"] but it is "Yellow".',
      Enum::getLastTypeError()
    );

    // Int, but too large
    $this->assertFalse(Enum::checkType($type, 3));
    $this->assertSame(
      '$value is expected to be an integer in range [0...2] but it is 3.',
      Enum::getLastTypeError()
    );

    // Correct
    $this->assertTrue(Enum::checkType($type, 'Green'));
    $this->assertTrue(Enum::checkType($type, 0));
  }

  public function test_equal()
  {
    $a = new Enum('Green', [':Red', ':Green', ':Blue']);
    $b = new Enum('green', [':red', ':green', ':Blue']);
    $this->assertTrue($a->equal($b));
    $this->assertTrue($b->equal($a));
    $this->assertTrue($a->equal("green"));
    $this->assertTrue($a->equal(1));
    $this->assertFalse($a->equal(2));
    $this->assertFalse($a->equal("red"));
    $this->assertFalse($a->equal(null));
  }

  public function test_toInt()
  {
    $x = new Enum('Blue', [':Red', ':Green', ':Blue']);
    $this->assertSame(2, $x->toInt());
  }

  public function test_toString()
  {
    $x = new Enum('Blue', [':Red', ':Green', ':blue']);
    $this->assertSame('Blue', $x->toString());
  }

  public function test___call()
  {
    $x = new Enum('Green', [':Red', ':Green', ':Blue']);
    $this->assertTrue($x->isGreen());
  }

  public function test___call_Exception_ENUM_UNKNOWN_METHOD()
  {
    $this->expectExceptionCode(ENUM_UNKNOWN_METHOD);
    $x = new Enum('Green', [':Red', ':Green', ':Blue']);
    $y = $x->isYellow();
  }

  public function test_cases()
  {
    $this->assertSame(['Red', 'Green', 'Blue'], array_map(function($x) {
      return $x->toString();
    }, Color::cases()));
  }

  public function test_cases_Exception_ENUM_UNKNOWN_METHOD()
  {
    $this->expectExceptionCode(ENUM_UNKNOWN_METHOD);
    $cases = Enum::cases();
  }

  public function test___callStatic()
  {
    $x = Color::green();
    $this->assertSame('Green', $x->toString());
  }

  public function test___callStatic_Exception_ENUM_UNKNOWN_METHOD()
  {
    $this->expectExceptionCode(ENUM_UNKNOWN_METHOD);
    $x = Color::yellow();
  }
}