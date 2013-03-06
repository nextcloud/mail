<?php
/**
 * Internal PHP-mail() interface.
 *
 * LICENSE:
 *
 * Copyright (c) 2010 Chuck Hagenbuch
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * o Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 * o The names of the authors may not be used to endorse or promote
 *   products derived from this software without specific prior written
 *   permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Horde
 * @package   Mail
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @copyright 2010 Chuck Hagenbuch
 * @copyright 2010 Michael Slusarz
 * @license   http://www.horde.org/licenses/bsd New BSD License
 */

/**
 * Internal PHP-mail() interface.
 *
 * @category Horde
 * @package  Mail
 */
class Horde_Mail_Transport_Mail extends Horde_Mail_Transport
{
    /**
     * Constructor.
     *
     * @param array $params  Additional parameters:
     *   - args: (string) Extra arguments for the mail() function.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Send a message.
     *
     * @param mixed $recipients  Either a comma-seperated list of recipients
     *                           (RFC822 compliant), or an array of
     *                           recipients, each RFC822 valid. This may
     *                           contain recipients not specified in the
     *                           headers, for Bcc:, resending messages, etc.
     * @param array $headers     The headers to send with the mail, in an
     *                           associative array, where the array key is the
     *                           header name (ie, 'Subject'), and the array
     *                           value is the header value (ie, 'test'). The
     *                           header produced from those values would be
     *                           'Subject: test'.
     *                           If the '_raw' key exists, the value of this
     *                           key will be used as the exact text for
     *                           sending the message.
     * @param mixed $body        The full text of the message body, including
     *                           any Mime parts, etc. Either a string or a
     *                           stream resource.
     *
     * @throws Horde_Mail_Exception
     */
    public function send($recipients, array $headers, $body)
    {
        $headers = $this->_sanitizeHeaders($headers);

        // If we're passed an array of recipients, implode it.
        if (is_array($recipients)) {
            $recipients = array_map('trim', implode(',', $recipients));
        }

        $subject = '';

        foreach (array_keys($headers) as $hdr) {
            if (strcasecmp($hdr, 'Subject') === 0) {
                // Get the Subject out of the headers array so that we can
                // pass it as a separate argument to mail().
                $subject = $headers[$hdr];
                unset($headers[$hdr]);
            } elseif (strcasecmp($hdr, 'To') === 0) {
                // Remove the To: header.  The mail() function will add its
                // own To: header based on the contents of $recipients.
                unset($headers[$hdr]);
            }
        }

        // Flatten the headers out.
        list(, $text_headers) = $this->prepareHeaders($headers);

        // mail() requires a string for $body. If resource, need to convert
        // to a string.
        if (is_resource($body)) {
            $body_str = '';

            stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
            stream_filter_append($body, 'horde_eol', STREAM_FILTER_READ, array('eol' => $this->sep));

            rewind($body);
            while (!feof($body)) {
                $body_str .= fread($body, 8192);
            }
            $body = $body_str;
        } else {
            // Convert EOL characters in body.
            $body = $this->_normalizeEOL($body);
        }

        // We only use mail()'s optional fifth parameter if the additional
        // parameters have been provided and we're not running in safe mode.
        if (empty($this->_params) || ini_get('safe_mode')) {
            $result = mail($recipients, $subject, $body, $text_headers);
        } else {
            $result = mail($recipients, $subject, $body, $text_headers, isset($this->_params['args']) ? $this->_params['args'] : '');
        }

        // If the mail() function returned failure, we need to create an
        // Exception and return it instead of the boolean result.
        if ($result === false) {
            throw new Horde_Mail_Exception('mail() returned failure.');
        }
    }
}
