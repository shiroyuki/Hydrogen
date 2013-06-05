<?php
/**
 * Development Sandbox
 */

namespace Shiroyuki\Hydrogen\Sandbox;

abstract class Cryptographer
{
    protected $maxDepth = 0; // unlimited

    final public function setMaxDepth($maxDepth)
    {
        $this->maxDepth = $maxDepth;
    }

    protected function makeGuid($object)
    {
        return sprintf('%s/%s', get_class($object), spl_object_hash($object));
    }

    protected function makeReference($object)
    {
        return sprintf('ref://%s', $this->makeGuid($object));
    }

    abstract public function encode($object);
    abstract protected function encodeObject($object, array &$objectMap = array(), $depth = 0);
    abstract protected function encodeProperty(\ReflectionProperty $reflector, $object, array &$objectMap, $depth);
}

class NestedCryptographer extends Cryptographer
{
    public function encode($object)
    {
        return $this->encodeObject($object);
    }

    protected function encodeObject($object, array &$objectMap = array(), $depth = 0)
    {
        $objectGuid = $this->makeGuid($object);

        if (in_array($objectGuid, array_keys($objectMap))) {
            return $this->makeReference($object);
        }

        $className = get_class($object);
        $reflector = new \ReflectionClass($className);

        $propertyToValueMap = array();

        $objectMap[$objectGuid] = $propertyToValueMap;

        foreach ($reflector->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertyToValueMap[$property->getName()] = $this->encodeProperty($property, $object, $objectMap, $depth + 1);
        }

        return $propertyToValueMap;
    }

    protected function encodeProperty(\ReflectionProperty $reflector, $object, array &$objectMap, $depth)
    {
        $reflector->setAccessible(true);

        $rawValue      = $reflector->getValue($object);
        $isTraversable = is_array($rawValue) || $rawValue instanceof \Traversable;

        if (is_object($rawValue)) {
            return $this->encodeObject($rawValue, $objectMap, $depth);
        }

        if ( ! $isTraversable) {
            return $rawValue;
        }

        $value = array();

        foreach ($rawValue as $k => $v) {
            $value[$k] = is_object($v) ? $this->encode($v) : $v;
        }

        return $value;
    }
}

class MappedCryptographer extends Cryptographer
{
    public function encode($object)
    {
        $objectMap = array();

        $this->encodeObject($object, $objectMap);

        return array_values($objectMap);
    }

    protected function encodeObject($object, array &$objectMap = array(), $depth = 0)
    {
        $objectGuid = $this->makeGuid($object);

        if (in_array($objectGuid, array_keys($objectMap))) {
            return $this->makeReference($object);
        }

        $className = get_class($object);
        $reflector = new \ReflectionClass($className);

        $propertyToValueMap = array(
            'guid'  => $this->makeReference($object),
            'class' => $className,
        );

        $objectMap[$objectGuid] = $propertyToValueMap;

        foreach ($reflector->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertyToValueMap[$property->getName()] = $this->encodeProperty($property, $object, $objectMap, $depth + 1);
        }

        $objectMap[$objectGuid] = $propertyToValueMap;
    }

    protected function encodeProperty(\ReflectionProperty $reflector, $object, array &$objectMap, $depth)
    {
        $reflector->setAccessible(true);

        $rawValue      = $reflector->getValue($object);
        $isTraversable = is_array($rawValue) || $rawValue instanceof \Traversable;

        if (is_object($rawValue)) {
            $this->encodeObject($rawValue, $objectMap, $depth);

            return $this->makeReference($rawValue);
        }

        if ( ! $isTraversable) {
            return $rawValue;
        }

        $value = array();

        foreach ($rawValue as $k => $v) {
            if ( ! is_object($v)) {
                $value[$k] = $v;

                continue;
            }

            $this->encodeObject($v, $objectMap, $depth);

            $value[$k] = $this->makeReference($v);
        }

        return $value;
    }
}

class Person // testing class
{
    static public $handler = 'Ghost';

    public $name;
    public $buddy;
    protected $links;

    public function __construct($name, $buddy = null, $links = array())
    {
        $this->name  = $name;
        $this->buddy = $buddy;
        $this->links = $links;
    }
}

$nc = new NestedCryptographer();
$mc = new MappedCryptographer();

$mary = new Person('Mary', null);
$dave = new Person('Dave', $mary);
$hahn = new Person('Hahn', null, array($mary, $dave));

$mary->buddy = $dave;

$dataMap = $nc->encode($hahn);

print "\nNested Version\n";

var_dump($dataMap);

$dataMap = $mc->encode($hahn);

print "\nMapped Version\n";

var_dump($dataMap);