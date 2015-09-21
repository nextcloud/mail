<?php

namespace OCA\Mail\Model;

use OCP\Files\File;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
interface IMessage {

	/**
	 * Get the ID if available
	 *
	 * @return int|null
	 */
	public function getMessageId();

	/**
	 * Get all flags set on this message
	 * 
	 * @return array
	 */
	public function getFlags();

	/**
	 * @param array $flags
	 */
	public function setFlags(array $flags);

	/**
	 * @return string
	 */
	public function getFrom();

	/**
	 * @param string $from
	 */
	public function setFrom($from);

	/**
	 * @return string
	 */
	public function getTo();

	/**
	 * @param string[] $to
	 */
	public function setTo(array $to);

	/**
	 * @return string[]
	 */
	public function getToList();

	/**
	 * @return string[]
	 */
	public function getCCList();

	/**
	 * @param array $cc
	 */
	public function setCC(array $cc);

	/**
	 * @return string[]
	 */
	public function getBCCList();

	/**
	 * @param array $bcc
	 */
	public function setBcc(array $bcc);

	/**
	 * @return IMessage
	 */
	public function getRepliedMessage();

	/**
	 * @param IMessage $message
	 */
	public function setRepliedMessage(IMessage $message);

	/**
	 * @return string
	 */
	public function getSubject();

	/**
	 * @param string $subject
	 */
	public function setSubject($subject);

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @param string $content
	 */
	public function setContent($content);

	/**
	 * @return Horde_Mime_Part[]
	 */
	public function getAttachments();

	/**
	 * @param File $fileName
	 */
	public function addAttachmentFromFiles(File $fileName);
}
