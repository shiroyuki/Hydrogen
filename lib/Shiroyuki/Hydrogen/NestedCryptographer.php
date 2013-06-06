<?php
/**
 * @copyright 2013 Juti Noppornpitak
 */

namespace Shiroyuki\Hydrogen;

/**
 * Nested Cryptographer
 *
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
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

        $propertyToValueMap = array(
            '__hydrogen_guid'  => $this->makeReference($object),
            '__hydrogen_class' => $className,
        );

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
        $isTraversable = $this->isList($rawValue);
        $isCollection  = is_object($rawValue) && ! $isTraversable;

        if ($isCollection) {
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