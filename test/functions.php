<?php
function getPrivateProp(object $object, string $propName) {
  $prop = (new ReflectionObject($object))->getProperty($propName);
  $prop->setAccessible(true);
  return $prop->getValue($object);
}