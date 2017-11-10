<?php namespace Heroest\LaravelModel;

use ArrayAccess;
use Countable;
use Iterator;

class Collection implements ArrayAccess, Countable, Iterator, Serializable
{
    private $storage = [];
    private $index = 0;

    public function __construct(array $arr)
    {
        $this->storage = $arr;
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
                $result[] = (is_object($item) and method_exists($item, 'toArray')) ? $item->toArray() : object2Array($item);
            } else {    
                $result[] = $item;
            }
        }
        return $result;
    }

    /**  ArrayAccess, Countable, Iterator, Serializable **/
    public function offsetExists($index)
    {
        return isset($this->storage[$index]);
    }

    public function offsetGet($index)
    {
        return isset($this->storage[$index]) ? $this->storage[$index] : [];
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
        $this->storage[$this->index];
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