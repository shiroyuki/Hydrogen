<?php
/**
 * @copyright 2013 Juti Noppornpitak
 */

namespace Shiroyuki\Hydrogen;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Cryptographer
 *
 * @author Juti Noppornpitak <jnopporn@shiroyuki.com>
 */
abstract class Cryptographer
{
    /**
     * @var integer
     */
    protected $maxDepth = 0; // unlimited

    /**
     * Define the maximum depth to traverse
     *
     * @param integer $maxDepth
     */
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

    protected function isList($data)
    {
        return is_array($data) || $data instanceof ArrayCollection;
    }

    abstract public function encode($object);
    abstract protected function encodeObject($object, array &$objectMap = array(), $depth = 0);
    abstract protected function encodeProperty(\ReflectionProperty $reflector, $object, array &$objectMap, $depth);
}