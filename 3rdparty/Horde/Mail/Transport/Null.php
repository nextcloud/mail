<?php
/**
 * Null implementation of the mail transport interface.
 *
 * LICENSE:
 *
 * Copyright (c) 2010 Phil Kernick
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
 * @category    Horde
 * @package     Mail
 * @author      Phil Kernick <philk@rotfl.com.au>
 * @copyright   2010 Phil Kernick
 * @license     http://www.horde.org/licenses/bsd New BSD License
 */

/**
 * Null implementation of the mail transport interface.
 *
 * @category Horde
 * @package  Mail
 */
class Horde_Mail_Transport_Null extends Horde_Mail_Transport
{
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
    }
}
