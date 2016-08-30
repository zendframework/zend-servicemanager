<?php

class UserMapper
{
    public function __construct(Adapter $db, Cache $cache) {}
}

class Adapter
{
    public function __construct(array $config) {}
}

class Cache
{
    public function __construct(CacheAdapter $cacheAdapter) {}
}

class CacheAdapter
{
}
