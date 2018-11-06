<?php

namespace Kapitanluffy\ArrayLegacy;

trait ArrayAccessTrait
{
    /**
     * Check if offset exists
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (!is_array($this->attributes) || !isset($this->attributes)) {
            $class = get_class();
            throw new \LogicException("Property attributes must be defined in the {$class} class");
        }

        return array_key_exists($offset, $this->attributes);
    }

    /**
     * Get an offset
     *
     * @param  string $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset) == false) {
            return null;
        }

        return $this->getAttribute($offset);
    }

    /**
     * Set an offset
     *
     * @param  string $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset an offset
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset) == false) {
            return;
        }

        $this->unsetAttribute($offset);
    }
}
