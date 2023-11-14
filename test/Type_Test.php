<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;
use stdClass;

class Type_Test extends TestCase
{
  public function test_register()
  {
    Type::register('email', function ($value) {
      return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    });
    $this->assertTrue(Type::isScalar('email'));
    $this->assertTrue(Type::check('email', 'artem.v.mailbox@gmail.com'));
    $this->assertFalse(Type::check('email', 'Hello world'));
  }

  public function test_isScalar()
  {
    $this->assertTrue(Type::isScalar('bool'));
    $this->assertFalse(Type::isScalar(['int']));
  }

  public function test_getComplexTypeClass()
  {
    $this->assertSame(Map::class, Type::getComplexTypeClass(['map', 'string', 'int']));
    $this->assertSame(ListOf::class, Type::getComplexTypeClass(['int']));
    $this->assertSame(Tuple::class, Type::getComplexTypeClass(['int', 'float', 'bool']));
    $this->assertSame(Record::class, Type::getComplexTypeClass(['id' => 'int', 'name' => 'string']));
    $this->assertSame(Variants::class, Type::getComplexTypeClass([':Just' => 'int', ':Nothing' => null]));
  }

  public function test_check()
  {
    $this->assertTrue(Type::check('any', ['Foo', 42]));
    $this->assertTrue(Type::check('bool', false));
    $this->assertFalse(Type::check('bool', 'true'));
    $this->assertTrue(Type::check('int', 42));
    $this->assertFalse(Type::check('int', 42.5));
    $this->assertTrue(Type::check('float', 42));
    $this->assertTrue(Type::check('float', 42.5));
    $this->assertTrue(Type::check('string', 'hello'));
    $this->assertFalse(Type::check('string', true));
    $this->assertTrue(Type::check(stdClass::class, (object) []));
    $this->assertFalse(Type::check(stdClass::class, []));
  }

  public function test_of()
  {
    $this->assertSame(
      ['bool', 'int', 'float', 'string', stdClass::class],
      [Type::of(true), Type::of(42), Type::of(3.1415926), Type::of('okay'), Type::of(new stdClass())]
    );
    $this->assertSame(['any'], Type::of([]));
    $this->assertSame(['int'], Type::of([1, 2, 3, 4]));
    $this->assertSame(['bool', 'int'], Type::of([true, 0]));
    $this->assertSame(['id' => 'int', 'name' => 'string'], Type::of(['id' => 1, 'name' => 'John']));
  }
}
