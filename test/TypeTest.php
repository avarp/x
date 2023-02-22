<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Precise\Type;

class TypeTest extends TestCase
{
  public function testTypeOf(): void
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

  public function testIsScalar(): void
  {
    $this->assertTrue(Type::isScalar('bool'));
    $this->assertFalse(Type::isScalar(['int']));
  }

  public function testTypeCheck(): void
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

  public function testCustomScalarType(): void
  {
    Type::register('email', function ($value) {
      return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    });
    $this->assertTrue(Type::isScalar('email'));
    $this->assertTrue(Type::check('email', 'artem.v.mailbox@gmail.com'));
    $this->assertFalse(Type::check('email', 'Hello world'));
  }
}
