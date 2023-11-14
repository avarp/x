<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class MethodsForListOf_Test extends TestCase
{
  public function test_chunk()
  {
    $list = new ListOf([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], ['int']);
    $chunks = $list->chunk(3);
    $this->assertTrue($chunks instanceof ListOf);
    $this->assertSame([['int']], $chunks->getType());
    $this->assertSame([[0, 1, 2], [3, 4, 5], [6, 7, 8], [9]], $chunks->toArray(true));
  }

  public function test_fill()
  {
    $list = ListOf::fill(3, 0.5);
    $this->assertSame(['float'], $list->getType());
    $this->assertSame([0.5, 0.5, 0.5], $list->toArray());

    $list = ListOf::fill(3, 1, ['float']);
    $this->assertSame(['float'], $list->getType());
    $this->assertSame([1.0, 1.0, 1.0], $list->toArray());
  }
}
