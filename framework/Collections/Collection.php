<?php

namespace PHPMini\Collections;

use Exception;
use stdClass;

class Collection
{
    protected $items;


    public function __construct($items = [])
    {
        $this->items  = $items;
    }

    public function all()
    {
        return $this->items;
    }

    public function contains($key, $operator = null, $value = null)
    {
        return in_array($key, $this->items);
    }

    public function diff($items)
    {
        return new static(array_diff($this->items, $items));
    }

    public function diffUsing($items, callable $callback)
    {
        return new static(array_udiff($this->items, $items, $callback));
    }

    public function diffAssoc($items)
    {
        return new static(array_diff_assoc($this->items, $items));
    }

    public function diffAssocUsing($items, callable $callback)
    {
        return new static(array_diff_uassoc($this->items, $items, $callback));
    }


    public function diffKeys($items)
    {
        return new static(array_diff_key($this->items, $items));
    }

    public function diffKeysUsing($items, callable $callback)
    {
        return new static(array_diff_key($this->items, $items, $callback));
    }

    public function except($keys)
    {
        $keys = (array)$keys;
        $original = &$this->items;
        foreach ($keys as $key) {
            if (array_key_exists($key, $original)) {
                unset($original[$key]);
                continue;
            }
        }
        return new static($original);
    }

    public function filter($callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->items));
    }

    public function first(array $items = null, $callback = null, $default = null)
    {
        $items = is_null($items) ? $this->items : $items;
        if (is_null($callback)) {
            if (empty($items)) {
                return value($default);
            }
            $k = array_key_first($items);
            return $items[$k];
        }

        foreach ($items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    public function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    public function keys()
    {
        return new static(array_keys($this->items));
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return value($default);
    }

    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (!array_key_exists($value, $this->items)) {
                return false;
            }
        }

        return true;
    }

    public function hasAny($key)
    {
        if ($this->isEmpty()) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->has($value)) {
                return true;
            }
        }

        return false;
    }

    public function intersect($items)
    {
        return new static(array_intersect($this->items, $items));
    }

    public function intersectByKeys($items)
    {
        return new static(array_intersect_key(
            $this->items,
            $items
        ));
    }

    public function containsOneItem()
    {
        return $this->count() === 1;
    }

    public function last(callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($this->items) ? value($default) : end($this->items);
        }

        return $this->first(array_reverse($this->items, true), $callback, $default);
    }
    public function count()
    {
        return count($this->items);
    }

    public function push(...$values)
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    public function concat($source)
    {
        $result = new static($this);

        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }

    public function replace($items)
    {
        return new static(array_replace($this->items, $items));
    }

    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    public function search($value, $strict = false)
    {
        if (!is_callable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    public function firstOrFail($key = null)
    {

        $placeholder = new stdClass();

        $item = $this->first($key, $placeholder);

        if ($item === $placeholder) {
            throw new Exception("Not found");
        }

        return $item;
    }
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    public function values()
    {
        return new static(array_values($this->items));
    }

    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }
}
