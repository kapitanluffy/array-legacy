<?php

namespace Kapitanluffy\ArrayLegacy;

use Kapitanluffy\ArrayLegacy\ArrayAccessTrait;

class ArrayLegacy implements \ArrayAccess, \IteratorAggregate, \Countable, \Serializable
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

        // basic array functions
        $basic = [
            'array_change_key_case', 'array_chunk', 'array_count_values', 'array_diff_assoc',
            'array_diff_key', 'array_diff_uassoc', 'array_diff_ukey', 'array_diff',
            'array_filter', 'array_flip', 'array_intersect_assoc', 'array_intersect_key',
            'array_intersect_uassoc', 'array_intersect_ukey', 'array_intersect', 'array_key_first',
            'array_key_last', 'array_keys', 'array_merge_recursive', 'array_merge',
            'array_pad', 'array_product',
            'array_rand', 'array_reduce', 'array_replace_recursive', 'array_replace', 'array_reverse',
            'array_slice', 'array_sum',
            'array_udiff_assoc', 'array_udiff_uassoc', 'array_udiff', 'array_uintersect_assoc', 'array_uintersect_uassoc',
            'array_uintersect', 'array_unique', 'array_values', 'array_walk',
            'count', 'current', 'end', 'key', 'pos', 'sizeof'
        ];

        // functions that don't have arrays as first parameter
        $notFirst = [
            'array_key_exists', // bool array_key_exists ( mixed $key , array $array )
            'key_exists', // array_key_exists alias
            'array_map', // array array_map ( callable $callback , array $array1 [, array $... ] )
            'array_search', // mixed array_search ( mixed $needle , array $haystack [, bool $strict = FALSE ] )
            'in_array', // bool in_array ( mixed $needle , array $haystack [, bool $strict = FALSE ] )
            'in', // in_array alias
        ];

        // functions that modify the first array parameter
        $byReference = [
            'array_multisort', // bool
            'array_pop', // mixed
            'array_push', // int
            'array_shift', // mixed
            'array_splice', // array
            'array_unshift', // int
            'array_walk_recursive', // bool
            'array_walk', // bool
            'arsort', // bool
            'asort', // bool
            'each', // key-value pair
            'krsort', // bool
            'ksort', // bool
            'natcasesort', // bool
            'natsort', // bool
            'next', // bool
            'prev', // bool
            'reset', // mixed
            'rsort', // bool
            'shuffle', // bool
            'sort', // bool
            'uasort', // bool
            'uksort', // bool
            'usort', // bool
        ];

        $unsupported = ['array_combine', 'array_fill_keys', 'array_fill', 'range', 'list', 'extract'];

        $functions = array_merge($basic, $notFirst, $byReference);
        $prefixed = in_array("array_$method", $functions);
        $others = in_array($method, $functions);

        if ($prefixed || $others) {
            $fn = $method;

            if ($prefixed) {
                $fn = "array_$method";
            }

            if ($fn === 'in') {
                $fn = 'in_array';
            }

            // rearrange parameters
            $params = [];
            if (in_array($fn, $notFirst)) { $params[] = array_shift($args); }
            $params[] =& $this->attributes;
            foreach ($args as $v) { $params[] = $v; }

            // customize error handling to point to proper file and line
            set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
                $trace = debug_backtrace();
                $origin = $trace[(count($trace) -1)];
                throw new \ErrorException($errstr, $errno, $errno, $origin['file'], $origin['line']);
            });

            $result = call_user_func_array($fn, $params);
            restore_error_handler();

            if (is_array($result) == false) {
                return $result;
            }

            return self::make($result);
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

    /**
     * Allow object to be traversable using foreach
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Count attributes of the object
     *
     * @return int
     */
    public function count()
    {
        return count($this->attributes);
    }

    /**
     * Serialize the current attributes
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->attributes);
    }

    /**
     * Unserialize the provided string
     *
     * @param  string $string
     *
     * @return \ArrayLegacy
     */
    public function unserialize($string)
    {
        $this->attributes = unserialize($string);
    }
}
