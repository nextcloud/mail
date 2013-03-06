<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Stream_Wrapper
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Stream_Wrapper
 */
interface Horde_Stream_Wrapper_StringStream
{
    /**
     * Return a reference to the wrapped string.
     *
     * @return string
     */
    public function &getString();
}
