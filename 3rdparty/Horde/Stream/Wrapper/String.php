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
class Horde_Stream_Wrapper_String
{
    /**
     * @var resource
     */
    public $context;

    /**
     * @var string
     */
    protected $_string;

    /**
     * @var integer
     */
    protected $_length;

    /**
     * @var integer
     */
    protected $_position;

    /**
     * @param string $path
     * @param string $mode
     * @param integer $options
     * @param string &$opened_path
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $options = stream_context_get_options($this->context);
        if (empty($options['horde-string']['string']) || ! $options['horde-string']['string'] instanceof Horde_Stream_Wrapper_StringStream) {
            throw new Exception('String streams must be created using the Horde_Stream_Wrapper_StringStream interface');
        }

        $this->_string =& $options['horde-string']['string']->getString();
        if (is_null($this->_string)) {
            return false;
        }

        $this->_length = strlen($this->_string);
        $this->_position = 0;
        return true;
    }

    /**
     * @param integer $count
     *
     * @return string
     */
    public function stream_read($count)
    {
        $current = $this->_position;
        $this->_position += $count;
        return substr($this->_string, $current, $count);
    }

    /**
     * @param string $data
     *
     * @return integer
     */
    public function stream_write($data)
    {
        return strlen($data);
    }

    /**
     * @return integer
     */
    public function stream_tell()
    {
        return $this->_position;
    }

    /**
     * @return boolean
     */
    public function stream_eof()
    {
        return ($this->_position > $this->_length);
    }

    /**
     * @see streamWrapper::stream_stat()
     *
     * @return array
     */
    public function stream_stat()
    {
        return array(
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => $this->_length,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0
        );
    }

    /**
     * @param integer $offset
     * @param integer $whence SEEK_SET, SEEK_CUR, or SEEK_END
     */
    public function stream_seek($offset, $whence)
    {
        if ($offset > $this->_length) {
            return false;
        }

        switch ($whence) {
        case SEEK_SET:
            $this->_position = $offset;
            break;

        case SEEK_CUR:
            $target = $this->_position + $offset;
            if ($target < $this->_length) {
                $this->_position = $target;
            } else {
                return false;
            }
            break;

        case SEEK_END:
            $this->_position = $this->_length - $offset;
            break;
        }

        return true;
    }

}
