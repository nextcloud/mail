<?php
/**
 * Class that can substitute for any object and safely do nothing.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_Stub
{
    /**
     * Cooerce to an empty string
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * Return self for any requested property.
     *
     * @param string $key The requested object property
     *
     * @return null
     */
    public function __get($key)
    {
    }

    /**
     * Gracefully accept any method call and do nothing.
     *
     * @param string $method The method that was called
     * @param array $args The method's arguments
     *
     * @return null
     */
    public function __call($method, $args)
    {
    }

    /**
     * Gracefully accept any static method call and do nothing.
     *
     * @param string $method The method that was called
     * @param array $args The method's arguments
     *
     * @return null
     */
    public static function __callStatic($method, $args)
    {
    }

}
