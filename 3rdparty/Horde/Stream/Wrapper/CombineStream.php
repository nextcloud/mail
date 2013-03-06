<?php
/**
 * Provides access to the Combine stream wrapper.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Stream_Wrapper
 */

/**
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Stream_Wrapper
 */
interface Horde_Stream_Wrapper_CombineStream
{
    /**
     * Return a reference to the data.
     *
     * @return array
     */
    public function getData();
}
