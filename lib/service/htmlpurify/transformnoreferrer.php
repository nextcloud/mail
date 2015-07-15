<?php

namespace OCA\Mail\Service\HtmlPurify;
use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URIParser;

/**
 * Adds rel="noreferrer" to all outbound links.
 */
class TransformNoReferrer extends HTMLPurifier_AttrTransform
{
    /**
     * @type HTMLPurifier_URIParser
     */
    private $parser;

    public function __construct() {
        $this->parser = new HTMLPurifier_URIParser();
    }

    /**
     * @param array $attr
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['href'])) {
            return $attr;
        }

        // XXX Kind of inefficient
        $url = $this->parser->parse($attr['href']);
        $scheme = $url->getSchemeObj($config, $context);

        if ($scheme->browsable && !$url->isLocal($config, $context)) {
            if (isset($attr['rel'])) {
                $rels = explode(' ', $attr['rel']);
                if (!in_array('noreferrer', $rels)) {
                    $rels[] = 'noreferrer';
                }
                $attr['rel'] = implode(' ', $rels);
            } else {
                $attr['rel'] = 'noreferrer';
            }
        }
        return $attr;
    }
}
