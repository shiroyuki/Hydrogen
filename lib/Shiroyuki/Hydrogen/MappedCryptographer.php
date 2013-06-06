<?php
/**
 * @copyright 2013 Juti Noppornpitak
 */

namespace Shiroyuki\Hydrogen;

/**
 * Mapped Cryptographer
 *
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
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

        $objectMap[$objectGuid] = $propertyToValueMap;
    }

    protected function encodeProperty(\ReflectionProperty $reflector, $object, array &$objectMap, $depth)
    {
        $reflector->setAccessible(true);

        $rawValue      = $reflector->getValue($object);
        $isTraversable = $this->isList($rawValue);

        if (is_object($rawValue) && ! $isTraversable) {
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