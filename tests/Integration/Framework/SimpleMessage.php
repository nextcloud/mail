<?php

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

namespace OCA\Mail\Tests\Integration\Framework;

class SimpleMessage {

	/** @var string */
	private $from;

	/** @var string */
	private $to;

	/** @var string */
	private $cc;

	/** @var string */
	private $bcc;

	/** @var string */
	private $date;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $cc
	 * @param string $bcc
	 * @param string $date
	 * @param string $subject
	 * @param string $body
	 */
	public function __construct($from, $to, $cc, $bcc, $date, $subject, $body) {
		$this->from = $from;
		$this->to = $to;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->date = $date;
		$this->subject = $subject;
		$this->body = $body;
	}

	/**
	 * @return string
	 */
	function getFrom() {
		return $this->from;
	}

	/**
	 * @return string
	 */
	function getTo() {
		return $this->to;
	}

	/**
	 * @return string
	 */
	function getCc() {
		return $this->cc;
	}

	/**
	 * @return string
	 */
	function getBcc() {
		return $this->bcc;
	}

	/**
	 * @return string
	 */
	function getDate() {
		return $this->date;
	}

	/**
	 * @return string
	 */
	function getSubject() {
		return $this->subject;
	}

	/**
	 * @return string
	 */
	function getBody() {
		return $this->body;
	}

}
