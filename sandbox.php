<?php
/**
 * Development Sandbox
 */

include 'autoload.php';
include 'vendor/autoload.php';

use Shiroyuki\Hydrogen\Accessor;
use Shiroyuki\Hydrogen\NestedCryptographer;

class Person // testing class
{
    static protected $nextId = 1;
    static public $handler = 'Ghost';

    private $id;
    private $name;
    private $buddy;
    private $links;
    public $collection;

    public function __construct($name, $buddy = null, $links = array())
    {
        $this->id    = Person::$nextId++;
        $this->name  = $name;
        $this->buddy = $buddy;
        $this->links = $links;
        $this->collection = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setBuddy(Person $buddy)
    {
        $this->buddy = $buddy;
    }

    public function getId()
    {
        return $this->id;
    }
}

$nc = new NestedCryptographer();

$mary = new Person('Mary', null);
$dave = new Person('Dave', $mary);
$hahn = new Person('Hahn', null, array($mary, $dave));

$mary->setBuddy($dave);
$mary->collection->set('profession', 'journalist');
$mary->collection->set('native language', 'japanese');

$hahnDataMap = $nc->encode($hahn);
$maryDataMap = $nc->encode($mary);

print "\n[Nested Version]\n\n";

var_dump($hahnDataMap, $maryDataMap);

print "\n[Accessor]\n\n";

$ac = new Accessor();

printf("buddy/name                 => [%s]\n", $ac->get($maryDataMap, 'buddy/name'));
printf("collection/native language => [%s]\n", $ac->get($maryDataMap, 'collection/native language'));
printf("collection.native language => [%s]\n", $ac->get($maryDataMap, 'collection.native language', '.'));
printf("links/0/name               => [%s]\n", $ac->get($hahnDataMap, 'links/0/name'));