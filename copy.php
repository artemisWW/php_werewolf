<?php

class Person
{
    public $name = '';
    public function __construct(string $name) {
        $this->name = $name;
    }
    public function print() {
        print 'name = ' . $this->name . "\n";
    }
}
class Group {
    public $person_list = [];
    public function __construct(array $person_list) {
        $this->person_list = $person_list;
    }
    public function __clone() {
        foreach ($this->person_list as $key => $person) {
            $this->person_list[$key] = clone $person;
        }
    }
    public function print() {
        foreach ($this->person_list as $person) {
            $person->print();
        }
        print "\n";
    }
}

$person0 = new Person('person0');
$person1 = new Person('person1');

$group0 = new Group([$person0, $person1]);
$group0->print();

$group1 = clone $group0;
$group1->person_list[0]->name = 'person2';

$group0->print();
$group1->print();
