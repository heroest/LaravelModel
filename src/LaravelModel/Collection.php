<?php namespace Heroest\LaravelModel;

use ArrayAccess;
use Countable;
use Iterator;
use Serializable;

class Collection implements ArrayAccess, Countable, Serializable, Iterator
{
    private $storage = [];
    
    private $index = 0;

    private $function_prefix = '';

    public function __construct(array $arr = [])
    {
        $this->storage = $arr;
    }

    public function setFunctionPrefix($prefix)
    {
        $this->function_prefix = $prefix;
    }

    public function push($value)
    {
        $this->storage[] = $value;
    }

    public function pop()
    {
        return array_pop($this->storage);
    }

    public function destory()
    {
        $this->storage = [];
        $this->index = 0;
    }

    public function toArray()
    {
        $result = [];
        foreach($this->storage as $item) {
            if(is_object($item)) {
                $func = "{$this->function_prefix}toArray";
                $result[] = method_exists($item, $func) ? $item->$func() : object2Array($item);
            } else {    
                $result[] = $item;
            }
        }
        return $result;
    }

    public function isEmpty()
    {
        return empty($this->storage);
    }

    /**  ArrayAccess, Countable, Iterator, Serializable **/
    public function offsetExists($index)
    {
        return isset($this->storage[$index]);
    }

    public function offsetGet($index)
    {
        return isset($this->storage[$index]) ? $this->storage[$index] : null;
    }

    public function offsetSet($index, $value)
    {
        if($index == null) {
            $this->storage[] = $value;
        } else {
            $this->storage[$index] = $value;
        }
    }

    public function offsetUnset($index)
    {
        unset($this->storage[$index]);
    }

    public function count()
    {
        return count($this->storage);
    }

    public function current()
    {
        return $this->storage[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return isset($this->storage[$this->index]);
    }

    public function serialize()
    {
        return json_encode($this->storage);
    }

    public function unserialize($serialized)
    {
        $this->storage = json_decode($serialized);
    }
}
