<?php
/**
 * Object that adds convenience/utility methods to interacting with PHP
 * streams.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Stream
 */
class Horde_Stream
{
    /**
     * Stream resource.
     *
     * @var resource
     */
    public $stream;

    /**
     * Constructor.
     *
     * @param array $opts  Configuration options.
     */
    public function __construct(array $opts = array())
    {
        // Sane default: read-only, 0-length stream.
        if (!$this->stream) {
            $this->stream = @fopen('php://temp', 'r');
        }
    }

    /**
     * Adds data to the stream.
     *
     * @param mixed $data     Data to add to the stream. Can be a resource,
     *                        Horde_Stream object, or a string(-ish) value.
     * @param boolean $reset  Reset stream pointer to initial position after
     *                        adding?
     */
    public function add($data, $reset = false)
    {
        if ($reset) {
            $pos = ftell($this->stream);
        }

        if (is_resource($data)) {
            $dpos = ftell($data);
            stream_copy_to_stream($data, $this->stream);
            fseek($data, $dpos);
        } elseif ($data instanceof Horde_Stream) {
            $dpos = ftell($data->stream);
            stream_copy_to_stream($data->stream, $this->stream);
            fseek($data->stream, $dpos);
        } else {
            fwrite($this->stream, $data);
        }

        if ($reset) {
            fseek($this->stream, $pos);
        }
    }

    /**
     * Returns the length of the data. Does not change the stream position.
     *
     * @return integer  Stream size.
     *
     * @throws Horde_Stream_Exception
     */
    public function length()
    {
        $pos = ftell($this->stream);

        if (fseek($this->stream, 0, SEEK_END) == -1) {
            throw new Horde_Stream_Exception('ERROR');
        }

        $len = ftell($this->stream);

        if (fseek($this->stream, $pos) == -1) {
            throw new Horde_Stream_Exception('ERROR');
        }

        return $len;
    }

    /**
     * Stream utility method: get a string from a stream up to a certain
     * character (or EOF).
     *
     * @param resource $data  Data stream.
     * @oaram string $end     The character to stop reading at.
     *
     * @return string  The string up to $end (stream is positioned after the
     *                 end character(s), all of which are stripped from the
                       return data).
     */
    public function getToChar($end)
    {
        $found_end = false;
        $out = '';

        while (($c = fgetc($this->stream)) !== false) {
            if ($c == $end) {
                $found_end = true;
            } elseif ($found_end) {
                fseek($this->stream, -1, SEEK_CUR);
                break;
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    /**
     * Return the current character without moving the pointer.
     *
     * @return string  The current character.
     */
    public function peek()
    {
        if (($c = fgetc($this->stream)) !== false) {
            fseek($this->stream, -1, SEEK_CUR);
        }

        return $c;
    }

    /**
     * Search for a character and return its position.
     *
     * @param string $char      The character to search for.
     * @param boolean $reverse  Do a reverse search?
     * @param boolean $reset    Reset the pointer to the original position?
     *
     * @return mixed  The position (integer), or null if character not found.
     */
    public function search($char, $reverse = false, $reset = true)
    {
        $pos = ftell($this->stream);
        $found_pos = null;

        if ($reverse) {
            for ($i = $pos - 1; $i >= 0; --$i) {
                fseek($this->stream, $i);
                if (fgetc($this->stream) == $char) {
                    $found_pos = $i;
                    break;
                }
            }
        } else {
            while (($c = fgetc($this->stream)) !== false) {
                if ($c == $char) {
                    $found_pos = ftell($this->stream) - 1;
                    break;
                }
            }
        }

        fseek(
            $this->stream,
            $reset || is_null($found_pos) ? $pos : $found_pos
        );

        return $found_pos;
    }

    /**
     * Returns the stream (or a portion of it) as a string.
     *
     * @param integer $start  The starting position. If null, starts from the
     *                        current position. If negative, starts this far
     *                        back from the current position.
     * @param integer $end    The ending position. If null, reads the entire
     *                        stream. If negative, sets ending this far from
     *                        the end of the stream.
     *
     * @return string  A string.
     */
    public function getString($start = null, $end = null)
    {
        if (!is_null($start) && ($start < 0)) {
            fseek($this->stream, $start, SEEK_CUR);
            $start = ftell($this->stream);
        }

        if (!is_null($end)) {
            $curr = is_null($start)
                ? ftell($this->stream)
                : $start;
            $end = ($end >= 0)
                ? $end - $curr + 1
                : $this->length() - $curr + $end;
            if ($end < 0) {
                $end = 0;
            }
        }

        return stream_get_contents(
            $this->stream,
            is_null($end) ? -1 : $end,
            is_null($start) ? -1 : $start
        );
    }

}
