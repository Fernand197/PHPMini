<?php

namespace PHPMini\Models;

use PHPMini\Collections\Collection;

class ModelCollection extends Collection
{
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        if (is_array($key) && $this->isEmpty()) {
            return new static;
        }

        return $this->first(
            $this->items,
            function ($model) use ($key) {
                return $model->getKey() === $key;
            },
            $default
        );
    }

    public function contains($key, $operator = null, $value = null)
    {
        if ((!is_string($value) && is_callable($value)) || func_num_args() > 1) {
            return parent::contains(...func_get_args());
        }

        if ($key instanceof Model) {
            return parent::contains(function ($model) use ($key) {
                return $model->is($key);
            });
        }

        return parent::contains(function ($model) use ($key) {
            return $model->getKey() === $key;
        });
    }

    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$value->getKey()] = $value;
        }

        return $dictionary;
    }

    public function modelKeys(): array
    {
        return array_map(function ($model) {
            return $model->getKey();
        }, $this->items);
    }

    public function merge($items): ModelCollection
    {
        $dictionary = $this->getDictionary();

        foreach ($items as $item) {
            $dictionary[$item->getKey()] = $item;
        }

        return new static(array_values($dictionary));
    }

    public function intersect($items): ModelCollection
    {
        $intersect = new static;

        if (empty($items)) {
            return $intersect;
        }

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[$item->getKey()])) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }
}
