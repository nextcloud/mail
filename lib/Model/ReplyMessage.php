<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Model;

class ReplyMessage extends Message
{
    public function setSubject(string $subject)
    {
        parent::setSubject("Re: " . ReplyMessage::replacePrefix($subject));
    }

    /**
     * Prevents stacking of subject prefixes by replacing the international abbreviations read from an array
     * (Source: https://en.wikipedia.org/wiki/List_of_email_subject_abbreviations#Abbreviations_in_other_languages)
     * with a simple 'Re: '.
     * The replacement is called recursively to fix subjects that are already ugly
     *
     * @param string $subject
     *
     * @return bool|string
     */
    private function replacePrefix(string $subject)
    {
        $prefixes = array("Re", "Aw", "Wg", "Fw", "Fwd", "Sv", "Vs", "Antw", "Doorst", "Vl", "Ref", "Tr", "R", "Rif",
            "I", "Fs", "Bls", "Trs", "Vb", "Rv", "Res", "Enc", "Odp", "Pd", "Ynt", "İlt", "Vá", 'Továbbítás', 'ΠΡΘ',
            'ΑΠ', 'ΣΧΕΤ', 'إعادة توجيه', 'رد', "回复", "转发", "回覆", "轉寄", "תגובה", "הועבר");

        foreach ($prefixes as $prefix) {
            if (strncasecmp($subject, $prefix . ": ", strlen($prefix) + 2) === 0) {
                $subject = substr($subject, strlen($prefix) + 2);
                return $this->replacePrefix($subject);
            }
        }

        return $subject;
    }

}