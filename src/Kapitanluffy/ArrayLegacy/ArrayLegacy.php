<?php

namespace Kapitanluffy\ArrayLegacy;

use Kapitanluffy\ArrayLegacy\ArrayAccessTrait;

class ArrayLegacy implements \ArrayAccess
{
    use ArrayAccessTrait;

    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes ?: $this->attributes;
    }

    /**
     * Get value of provided name
     *
     *  Return default if name does not exist
     *
     * @param  string $name
     * @param  mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $result = $this->getAttribute($name);

        if ($this->offsetExists($name) == false && $result === null) {
            return $default;
        }

        return $result;
    }

    /**
     * Set value of provided name
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        return $this->setAttribute($name, $value);
    }

    /**
     * Convert given array into ArrayLegacy
     *
     * @param  array  $array
     *
     * @return \Kapitanluffy\ArrayLegacy\ArrayLegacy
     */
    public static function make(array $array)
    {
        return new self($array);
    }

    /**
     * Get attribute
     *
     * @param  string $name
     *
     * @return mixed
     */
    protected function getAttribute($name)
    {
        $method = $this->toCamelCase($name);
        $method = "get{$method}";
        $result = null;

        if ($this->offsetExists($name)) {
            $result = $this->attributes[$name];
        }

        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this, $method], [$value]);
        }

        return $result;
    }

    /**
     * Set attribute
     *
     * @param string $name
     * @param mixed $value
     */
    protected function setAttribute($name, $value)
    {
        $method = $this->toCamelCase($name);
        $method = "set{$method}";

        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], [$value]);
        }

        return $this->attributes[$name] = $value;
    }

    /**
     * Clear an attribute
     *
     * @param  string $name
     *
     * @return void
     */
    protected function unsetAttribute($name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * Redirect setter/getter methods to set/get attribute methods
     *
     * @param  string $method
     * @param  array $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (preg_match('#((?:s|g)et)(\p{Lu}.+)#u', $method, $match)) {
            $attribute = $this->toSnakeCase($match[2]);
            array_unshift($args, $attribute);
            return call_user_func_array([$this, "{$match[1]}Attribute"], $args);
        }

        throw new \BadMethodCallException("Undefined $method method");
    }

    /**
     * Convert the current object into a real array
     *
     * @return array
     */
    public function toArray()
    {
        foreach ($this->attributes as $key => $value) {
            if (is_array($value)) {
                $this->attributes[$key] = $this->nestedToArray($value);
            }

            if ($value instanceof self) {
                $this->attributes[$key] = $value->toArray();
            }
        }

        return $this->attributes;
    }

    /**
     * Convert nested ArrayLegacy into array
     *
     * @param  array  $nested
     *
     * @return array
     */
    protected function nestedToArray(array $nested)
    {
        foreach ($nested as $k => $v) {
            if (is_array($v)) {
                $nested[$k] = $this->nestedToArray($v);
            }

            if ($v instanceof self) {
                $nested[$k] = $v->toArray();
            }
        }

        return $nested;
    }

    /**
     * Convert string to camelCase
     *
     * @param  string $string
     *
     * @return string
     */
    protected function toCamelCase($string)
    {
        // mb_ucwords
        $string = array_map(function ($v) {
            return mb_strtoupper(mb_substr($v, 0, 1)) . mb_substr($v, 1);
        }, explode('_', $string));

        return implode('', $string);
    }

    /**
     * Convert string to snake_case
     *
     * @param  string $string
     *
     * @return string
     */
    protected function toSnakeCase($string)
    {
        return trim(mb_strtolower(preg_replace('#(\p{Lu})#u', '_$1', $string)), '_');
    }
}
