<?php namespace Heroest\LaravelModel\Traits;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\ConnectionNotFoundException;
use Heroest\LaravelModel\ConnectionPool;

trait Connection
{
    private static $pool = null;

    protected function loadConnection($name, $mixed)
    {
        if(empty(self::$pool)) self::$pool = ConnectionPool::getInstance();
        self::$pool->addConnection($name, $mixed);
        return $this;
    }

    protected function hasConnection($name)
    {
        if(empty(self::$pool)) self::$pool = ConnectionPool::getInstance();
        return self::$pool->hasConnection($name);
    }

    protected function getConnection($name)
    {
        if(empty(self::$pool)) self::$pool = ConnectionPool::getInstance();
        return self::$pool->getConnection($name);
    }
    
}