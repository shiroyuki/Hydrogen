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

    public function encode($object)
    {
        return $this->encodeObject($object, $objectMap, $depth);
    }

    abstract protected function encodeObject($object, array $objectMap = array(), $depth);
    abstract protected function encodeProperty(\ReflectionProperty $reflector, $object, array $objectMap, $depth);
}

class NestedCryptographer extends Cryptographer
{
    protected function encodeObject($object, array $objectMap = array(), $depth)
    {
        $objectGuid = $this->makeGuid($object);
        
        if (in_array($objectGuid, array_keys($objectMap))) {
            return sprintf('ref://%s', $objectGuid);
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

    protected function encodeProperty(\ReflectionProperty $reflector, $object, array $objectMap, $depth)
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

$serializer = new NestedCryptographer();

$mary = new Person('Mary', null);
$dave = new Person('Dave', $mary);
$hahn = new Person('Hahn', null, array($mary, $dave));

$mary->buddy = $dave;

$dataMap = $serializer->encode($hahn);

var_dump($dataMap);