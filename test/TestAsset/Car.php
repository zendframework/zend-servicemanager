<?php

namespace ZendTest\ServiceManager\TestAsset;

class Car
{
    public $classifier = 'I am not a clone, honestly.';

    public function __clone()
    {
        $this->classifier = 'I am a cloned car, believe me.';
    }
}
