<?php namespace Heroest\LaravelModel\Component\Query;

use Heroest\LaravelModel\Component\Query\Interfaces\QueryComponent;
use Heroest\LaravelModel\Exception\InvalidParameterException;

class Set implements QueryComponent
{
    private $keys = [];

    private $values = [];

    public function __construct($params)
    {
        foreach($params as $k => $v) {
            $this->keys[] = "{$k} = ?";
            $this->values[] = $v;
        }
    }

    public function getValue()
    {
        return $this->values;
    }

    public function __toString()
    {
        return implode(', ', $this->keys);
    }
}