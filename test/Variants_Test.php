<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class MaybeInt extends Variants
{
  static $type = [
    ':Just' => 'int',
    ':Nothing' => null
  ];
}

class Variants_Test extends TestCase
{
  public function test___construct()
  {
    $x = new Variants(['Just', 5], [':Just' => 'int', ':Nothing' => null]);
    $this->assertTrue($x->isJust());
    $this->assertSame(5, $x->just);
  }

  public function test_checkType()
  {
    $type = [':just' => 'int', ':nothing' => null];

    // Not an array
    $this->assertFalse(Variants::checkType($type, null));
    $this->assertSame(
      '$value is expected to be an array of 2 elements: a variant and its value but it is NULL.',
      Variants::getLastTypeError()
    );
  
    // First value is not int nor string
    $this->assertFalse(Variants::checkType($type, [true, 5]));
    $this->assertSame(
      '$value[0] is expected to be a string or an integer but it is 1.',
      Variants::getLastTypeError()
    );

    // First value is too big
    $this->assertFalse(Variants::checkType($type, [2, 5]));
    $this->assertSame(
      '$value[0] is expected to be an integer in range [0...1] but it is 2.',
      Variants::getLastTypeError()
    );

    // First value is not known
    $this->assertFalse(Variants::checkType($type, ['Ok', 5]));
    $this->assertSame(
      '$value[0] is expected to be any value from list ["Just", "Nothing"] but it is "Ok".',
      Variants::getLastTypeError()
    );

    // Second value is not null
    $this->assertFalse(Variants::checkType($type, ['Nothing', 5]));
    $this->assertSame(
      '$value[1] is expected to be null but it is 5.',
      Variants::getLastTypeError()
    );

    // Second value has wrong type
    $this->assertFalse(Variants::checkType($type, ['Just', 5.1]));
    $this->assertSame(
      '$value[1] is expected to be an integer but it is 5.1.',
      Variants::getLastTypeError()
    );
  }

  public function test_equal()
  {
    $x1 = new Variants(['Just', 5], [':Just' => 'int', ':Nothing' => null]);
    $x2 = new Variants(['just', 5], [':nothing' => null, ':just' => 'int']);
    $this->assertTrue($x1->equal($x2));
    $this->assertTrue($x2->equal($x1));
    $this->assertTrue($x1->equal([0, 5]));
    $this->assertFalse($x1->equal([1, null]));
    $this->assertFalse($x1->equal([0, 6]));
  }

  public function test___call()
  {
    $x = new Variants(['Just', 5], [':Just' => 'int', ':Nothing' => null]);
    $this->assertTrue($x->isJust());
    $this->assertSame(5, $x->justOr(0));
    $x = new Variants(['Nothing', null], [':Just' => 'int', ':Nothing' => null]);
    $this->assertTrue($x->isNothing());
    $this->assertSame(0, $x->justOr(0));
  }

  public function test___call_Exception_VARIANT_MISSED_PARAM()
  {
    $this->expectExceptionCode(VARIANT_MISSED_PARAM);
    $x = new Variants(['Just', 5], [':Just' => 'int', ':Nothing' => null]);
    $y = $x->justOr();
  }

  public function test___call_Exception_VARIANT_UNKNOWN_METHOD()
  {
    $this->expectExceptionCode(VARIANT_UNKNOWN_METHOD);
    $x = new Variants(['Just', 5], [':Just' => 'int', ':Nothing' => null]);
    $y = $x->nothingOr(42);
  }

  public function test__callStatic()
  {
    $x = MaybeInt::just(5);
    $this->assertTrue($x->isJust());
    $this->assertSame(5, $x->justOr(0));
  }
}