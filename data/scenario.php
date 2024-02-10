<?php


class scenario
{
    private $key;
    private $name;
    private $isActive;

    public function __construct($key, $name)
    {
        $this->key = $key;
        $this->name = $name;
        $this->isActive = false;
    }
    public function getKey()
    {
        return $this->key;
    }
    public function getName()
    {
        return $this->name;
    }
    public function isActive()
    {
        return $this->isActive;
    }
}


?>