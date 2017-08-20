<?php namespace Heroest\LaravelModel\Component\Query;

use Heroest\LaravelModel\Component\Query\Interfaces\QueryComponent;
use Heroest\LaravelModel\Exception\InvalidParameterException;

class Values implements QueryComponent
{
    private $fields = [];

    private $values = [];

    public function __construct(array $params)
    {
        foreach($params as $k => $v) {
            $this->fields[] = $k;
            $this->values[] = $v;
        }
    }

    public function getValue()
    {
        return $this->values;
    }

    public function __toString()
    {
        $str_fields = '(' . implode(', ', $this->fields) . ')';
        $str_values = '(' . implode(', ', array_fill(0, count($this->values), '?')) . ')';

        return "{$str_fields} VALUES {$str_values}";
    }
}