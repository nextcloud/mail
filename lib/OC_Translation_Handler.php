<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thomas
 * Date: 13.11.12
 * Time: 23:51
 * To change this template use File | Settings | File Templates.
 */
class OC_Translation_Handler  implements Horde_Translation_Handler
{
	/**
	 * Returns the translation of a message.
	 *
	 * @var string $message  The string to translate.
	 *
	 * @return string  The string translation, or the original string if no
	 *                 translation exists.
	 */
	public function t($message) {
		return $message;
	}

	/**
	 * Returns the plural translation of a message.
	 *
	 * @param string $singular  The singular version to translate.
	 * @param string $plural    The plural version to translate.
	 * @param integer $number   The number that determines singular vs. plural.
	 *
	 * @return string  The string translation, or the original string if no
	 *                 translation exists.
	 */
	public function ngettext($singular, $plural, $number) {
		return ($number > 1 ? $plural : $singular);
	}
}
