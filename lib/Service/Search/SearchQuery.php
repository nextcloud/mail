<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Search;

class SearchQuery {
	private ?int $cursor = null;

	private bool $threaded = true;

	/** @var Flag[] */
	private array $flags = [];

	/** @var FlagExpression[] */
	private array $flagExpressions = [];

	/** @var string[] */
	private array $to = [];

	/** @var string[] */
	private array $from = [];

	/** @var string[] */
	private array $cc = [];

	/** @var string[] */
	private array $bcc = [];

	/** @var string[] */
	private array $subjects = [];

	/** @var string[] */
	private array $bodies = [];

	/** @var array[] */
	private array $tags = [];

	private ?string $start = null;

	private ?string $end = null;

	private bool $hasAttachments = false;

	private bool $mentionsMe = false;

	private string $match = 'allof';

	/**
	 * @psalm-mutation-free
	 */
	public function getCursor(): ?int {
		return $this->cursor;
	}

	public function setCursor(int $cursor): void {
		$this->cursor = $cursor;
	}

	public function getThreaded(): bool {
		return $this->threaded;
	}

	public function setThreaded(bool $threaded): void {
		$this->threaded = $threaded;
	}

	public function getMatch(): string {
		return $this->match;
	}

	public function setMatch(string $match): void {
		$this->match = $match;
	}

	/**
	 * @return Flag[]
	 * @psalm-mutation-free
	 */
	public function getFlags(): array {
		return $this->flags;
	}

	public function addFlag(Flag $flag): void {
		$this->flags[] = $flag;
	}

	/**
	 * @return FlagExpression[]
	 */
	public function getFlagExpressions(): array {
		return $this->flagExpressions;
	}

	public function addFlagExpression(FlagExpression $expression): void {
		$this->flagExpressions[] = $expression;
	}

	/**
	 * @return string[]
	 */
	public function getTo(): array {
		return $this->to;
	}

	public function addTo(string $to): void {
		$this->to[] = $to;
	}

	/**
	 * @return string[]
	 */
	public function getFrom(): array {
		return $this->from;
	}

	public function addFrom(string $from): void {
		$this->from[] = $from;
	}

	/**
	 * @return string[]
	 */
	public function getCc(): array {
		return $this->cc;
	}

	public function addCc(string $cc): void {
		$this->cc[] = $cc;
	}

	/**
	 * @return string[]
	 */
	public function getBcc(): array {
		return $this->bcc;
	}

	public function addBcc(string $bcc): void {
		$this->bcc[] = $bcc;
	}

	public function getSubjects(): array {
		return $this->subjects;
	}

	public function addSubject(string $subject): void {
		$this->subjects[] = $subject;
	}
	public function getBodies(): array {
		return $this->bodies;
	}

	public function addBody(string $body): void {
		$this->bodies[] = $body;
	}

	/**
	 * Get tags to search query
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * Set tags to search query
	 */
	public function setTags(array $tags): void {
		$this->tags = $tags;
	}

	/**
	 * Get start date to search query
	 */
	public function getStart(): ?string {
		return $this->start;
	}

	/**
	 * Set start date to search query
	 */
	public function setStart(string $start): void {
		$this->start = $start;
	}

	/**
	 * Get start date to search query
	 */
	public function getEnd(): ?string {
		return $this->end;
	}

	/**
	 * Set end date to search query
	 */
	public function setEnd(string $end): void {
		$this->end = $end;
	}

	/**
	 * @psalm-mutation-free
	 */
	public function getHasAttachments(): ?bool {
		return $this->hasAttachments;
	}

	public function setHasAttachments(bool $hasAttachments): void {
		$this->hasAttachments = $hasAttachments;
	}

	public function getMentionsMe(): bool {
		return $this->mentionsMe;
	}

	/**
	 * @param bool $hasAttachments
	 */
	public function setMentionsMe(bool $mentionsMe): void {
		$this->mentionsMe = $mentionsMe;
	}
}
