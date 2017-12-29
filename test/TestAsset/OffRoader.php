<?php

namespace ZendTest\ServiceManager\TestAsset;

class OffRoader
{
    public $classifier = 'I am a shared offroader.';

    public function __clone()
    {
        $this->classifier = 'I am a cloned offroader.';
    }
}
