<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class Record_Test extends TestCase
{
  public function test___construct()
  {
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $this->assertSame(1, $user->id);
    $this->assertSame('John', $user->name);
  }

  public function test_checkType()
  {
    $type = ['id' => 'int', 'name' => 'string'];

    // Not an array
    $this->assertFalse(Record::checkType($type, null));
    $this->assertSame(
      '$value is expected to be an associative array but it is NULL.',
      Record::getLastTypeError()
    );

    // Not an associative array
    $this->assertFalse(Record::checkType($type, [1, 2, 3]));
    $this->assertSame(
      '$value is expected to be an associative array but it is array.',
      Record::getLastTypeError()
    );

    // Missing keys
    $this->assertFalse(Record::checkType($type, ['name' => 'John']));
    $this->assertSame(
      '$value has missing required key "id".',
      Record::getLastTypeError()
    );

    // Extra keys
    $this->assertFalse(Record::checkType($type, ['id' => 1, 'name' => 'John', 'isAdmin' => false]));
    $this->assertSame(
      '$value has unknown key "isAdmin".',
      Record::getLastTypeError()
    );

    // Missing keys and extra keys
    $this->assertFalse(Record::checkType($type, ['uid' => 1, 'firstName' => 'John', 'isAdmin' => false]));
    $this->assertSame(
      '$value has missing required keys "id", "name" and unknown keys "uid", "firstName", "isAdmin".',
      Record::getLastTypeError()
    );

    // Key with wrong value
    $this->assertFalse(Record::checkType($type, ['id' => 'fff', 'name' => 'John']));
    $this->assertSame(
      '$value->id is expected to be an integer but it is "fff".',
      Record::getLastTypeError()
    );
  }

  public function test_equal()
  {
    $user1 = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $user2 = new Record(['id' => 1, 'name' => 'John'], ['name' => 'string', 'id' => 'int']);
    $this->assertTrue($user1->equal($user2));
    $this->assertTrue($user2->equal($user1));
    $this->assertTrue($user1->equal(['id' => 1, 'name' => 'John']));
    $this->assertFalse($user1->equal(['id' => 2, 'name' => 'John']));
    $this->assertFalse($user1->equal(42));
  }

  public function test__get()
  {
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $this->assertSame(1, $user->id);
    $this->assertSame('John', $user->name);
  }

  public function test__get_Exception_RECORD_UNKNOWN_PROPERTY()
  {
    $this->expectExceptionCode(RECORD_UNKNOWN_PROPERTY);
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $email = $user->email;
  }

  public function test__set()
  {
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $user->id = 2;
    $user->name = 'Bob';
    $this->assertSame(2, $user->id);
    $this->assertSame('Bob', $user->name);
  }

  public function test__set_Exception_TYPE_MISMATCH()
  {
    $this->expectExceptionCode(TYPE_MISMATCH);
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $user->id = 1.5;
  }

  public function test__set_Exception_RECORD_UNKNOWN_PROPERTY()
  {
    $this->expectExceptionCode(RECORD_UNKNOWN_PROPERTY);
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $user->email = 'test@test.com';
  }

  public function test__isset()
  {
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    $this->assertTrue(isset($user->id));
    $this->assertFalse(isset($user->email));
  }

  public function test__unset_Exception_RECORD_UNSET_UNSUPPORTED()
  {
    $this->expectExceptionCode(RECORD_UNSET_UNSUPPORTED);
    $user = new Record(['id' => 1, 'name' => 'John'], ['id' => 'int', 'name' => 'string']);
    unset($user->id);
  }
}