<?php
/**
 * Utility class to help in loading DOM data from HTML strings.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Util
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Domhtml implements Iterator
{
    /**
     * DOM object.
     *
     * @var DOMDocument
     */
    public $dom;

    /**
     * Iterator status.
     *
     * @var array
     */
    protected $_iterator = null;

    /**
     * Original charset of data.
     *
     * @var string
     */
    protected $_origCharset;

    /**
     * Encoding tag added to beginning of output.
     *
     * @var string
     */
    protected $_xmlencoding = '';

    /**
     * Constructor.
     *
     * @param string $text     The text of the HTML document.
     * @param string $charset  The charset of the HTML document.
     *
     * @throws Exception
     */
    public function __construct($text, $charset = null)
    {
        if (!extension_loaded('dom')) {
            throw new Exception('DOM extension is not available.');
        }

        // Bug #9616: Make sure we have valid HTML input.
        if (!strlen($text)) {
            $text = '<html></html>';
        }

        $old_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument();

        if (is_null($charset)) {
            /* If no charset given, charset is whatever libxml tells us the
             * encoding should be defaulting to 'iso-8859-1'. */
            $doc->loadHTML($text);
            $this->_origCharset = $doc->encoding
                ? $doc->encoding
                : 'iso-8859-1';
        } else {
            /* Convert/try with UTF-8 first. */
            $this->_origCharset = Horde_String::lower($charset);
            $this->_xmlencoding = '<?xml encoding="UTF-8"?>';
            $doc->loadHTML($this->_xmlencoding . Horde_String::convertCharset($text, $charset, 'UTF-8'));

            if ($doc->encoding &&
                (Horde_String::lower($doc->encoding) != 'utf-8')) {
                /* Convert charset to what the HTML document says it SHOULD
                 * be. */
                $doc->loadHTML(Horde_String::convertCharset($text, $charset, $doc->encoding));
                $this->_xmlencoding = '';
            }
        }

        if ($old_error) {
            libxml_use_internal_errors(false);
        }

        $this->dom = $doc;
    }

    /**
     * Returns the HEAD element, or creates one if it doesn't exist.
     *
     * @return DOMElement  HEAD element.
     */
    public function getHead()
    {
        $head = $this->dom->getElementsByTagName('head');
        if ($head->length) {
            return $head->item(0);
        }

        $headelt = $this->dom->createElement('head');
        $this->dom->appendChild($headelt);

        return $headelt;
    }

    /**
     * Returns the full HTML text in the original charset.
     *
     * @return string  HTML text.
     */
    public function returnHtml()
    {
        $text = Horde_String::convertCharset($this->dom->saveHTML(), $this->dom->encoding || $this->_origCharset, $this->_origCharset);

        if (!$this->_xmlencoding ||
            (($pos = strpos($text, $this->_xmlencoding)) === false)) {
            return $text;
        }

        return substr_replace($text, '', $pos, strlen($this->_xmlencoding));
    }

    /**
     * Returns the body text in the original charset.
     *
     * @return string  HTML text.
     */
    public function returnBody()
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        $text = '';

        if ($body && $body->hasChildNodes()) {
            foreach ($body->childNodes as $child) {
                $text .= $this->dom->saveXML($child);
            }
        }

        return Horde_String::convertCharset($text, 'UTF-8', $this->_origCharset);
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        if ($this->_iterator instanceof DOMDocument) {
            return $this->_iterator;
        }

        $curr = end($this->_iterator);
        return $curr['list']->item($curr['i']);
    }

    /**
     */
    public function key()
    {
        return 0;
    }

    /**
     */
    public function next()
    {
        /* Iterate in the reverse direction through the node list. This allows
         * alteration of the original list without breaking things (foreach()
         * w/removeChild() may exit iteration after removal is complete. */

        if ($this->_iterator instanceof DOMDocument) {
            $this->_iterator = array();
            $curr = array();
            $node = $this->dom;
        } elseif (empty($this->_iterator)) {
            $this->_iterator = null;
            return;
        } else {
            $curr = &$this->_iterator[count($this->_iterator) - 1];
            $node = $curr['list']->item($curr['i']);
        }

        if (empty($curr['child']) &&
            ($node instanceof DOMNode) &&
            $node->hasChildNodes()) {
            $curr['child'] = true;
            $this->_iterator[] = array(
                'child' => false,
                'i' => $node->childNodes->length - 1,
                'list' => $node->childNodes
            );
        } elseif (--$curr['i'] < 0) {
            array_pop($this->_iterator);
            $this->next();
        } else {
            $curr['child'] = false;
        }
    }

    /**
     */
    public function rewind()
    {
        $this->_iterator = $this->dom;
    }

    /**
     */
    public function valid()
    {
        return !is_null($this->_iterator);
    }

}
