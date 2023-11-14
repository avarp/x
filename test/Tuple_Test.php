<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class Tuple_Test extends TestCase
{
  public function test___construct()
  {
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $this->assertSame(1, $t[0]);
    $this->assertSame(3.14, $t[1]);
    $this->assertSame(true, $t[2]);
  }

  public function test_checkType()
  {
    $type = ['int', 'float', 'bool'];

    // Not an array
    $this->assertFalse(Tuple::checkType($type, null));
    $this->assertSame(
      '$value is expected to be an array but it is NULL.',
      Tuple::getLastTypeError()
    );

    // Not a list
    $this->assertFalse(Tuple::checkType($type, ['foo' => 'bar']));
    $this->assertSame(
      '$value is expected to be a list but it is array.',
      Tuple::getLastTypeError()
    );

    // Different length
    $this->assertFalse(Tuple::checkType($type, [1, 1.5]));
    $this->assertSame(
      '$value is expected to be a list of 3 values but it is array.',
      Tuple::getLastTypeError()
    );

    // Inner type mismatch
    $this->assertFalse(Tuple::checkType($type, [1, 1.5, 'false']));
    $this->assertSame(
      '$value[2] is expected to be a boolean value but it is "false".',
      Tuple::getLastTypeError()
    );
  }

  public function test_equal()
  {
    $t1 = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $t2 = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $this->assertTrue($t1->equal($t2));
    $this->assertTrue($t2->equal($t1));
    $this->assertTrue($t1->equal([1, 3.14, true]));
    $this->assertFalse($t1->equal([1, 3.15, true]));
    $this->assertFalse($t1->equal(['1st' => 1, '2nd' => 3.14, '3rd' => true]));
  }

  public function test_offsetExists()
  {
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $this->assertTrue(isset($t[1]));
    $this->assertFalse(isset($t[3]));
    $this->assertFalse(isset($t[-1]));
    $this->assertFalse(isset($t["bla"]));
  }

  public function test_offsetGet()
  {
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $this->assertSame(1, $t[0]);
    $this->assertSame(3.14, $t[1]);
    $this->assertSame(true, $t[2]);
  }

  public function test_offsetGet_Exception_OFFSET_DOESNT_EXIST()
  {
    $this->expectExceptionCode(OFFSET_DOESNT_EXIST);
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $x = $t[5];
  }

  public function test_offsetSet()
  {
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $t[2] = false;
    $this->assertSame(1, $t[0]);
    $this->assertSame(3.14, $t[1]);
    $this->assertSame(false, $t[2]);
  }

  public function test_offsetSet_Exception_OFFSET_OUT_OF_BOUNDS()
  {
    $this->expectExceptionCode(OFFSET_OUT_OF_BOUNDS);
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $t[3] = false;
  }

  public function test_offsetSet_Exception_TYPE_MISMATCH()
  {
    $this->expectExceptionCode(TYPE_MISMATCH);
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    $t[2] = 1;
  }

  public function test__unset_Exception_TUPLE_UNSET_UNSUPPORTED()
  {
    $this->expectExceptionCode(TUPLE_UNSET_UNSUPPORTED);
    $t = new Tuple([1, 3.14, true], ['int', 'float', 'bool']);
    unset($t[0]);
  }
}