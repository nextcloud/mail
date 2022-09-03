<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\IMAP\Threading;

use OCA\Mail\Support\PerformanceLogger;
use Psr\Log\LoggerInterface;
use function array_key_exists;
use function count;

class ThreadBuilder {
	/** @var PerformanceLogger */
	private $performanceLogger;

	public function __construct(PerformanceLogger $performanceLogger) {
		$this->performanceLogger = $performanceLogger;
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Container[]
	 */
	public function build(array $messages, LoggerInterface $logger): array {
		$log = $this->performanceLogger->startWithLogger(
			'Threading ' . count($messages) . ' messages',
			$logger
		);

		// Step 1
		$idTable = $this->buildIdTable($messages);
		$log->step('build ID table');

		// Step 2
		$rootContainer = $this->buildRootContainer($idTable);
		$log->step('build root container');

		// Step 3
		unset($idTable);
		$log->step('free ID table');

		// Step 4
		$this->pruneContainers($rootContainer);
		$log->step('prune containers');

		// Step 5
		$this->groupBySubject($rootContainer);
		$log->step('group by subject');

		$log->end();
		// Return the children with reset numeric keys
		return array_values($rootContainer->getChildren());
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Container[]
	 */
	private function buildIdTable(array $messages): array {
		/** @var Container[] $idTable */
		$idTable = [];

		foreach ($messages as $message) {
			/** @var Message $message */
			// Step 1.A
			$container = $idTable[$message->getId()] ?? null;
			if ($container !== null && !$container->hasMessage()) {
				$container->fill($message);
			} else {
				$container = $idTable[$message->getId()] = Container::with($message);
			}

			// Step 1.B
			$parent = null;
			foreach ($message->getReferences() as $reference) {
				$refContainer = $idTable[$reference] ?? null;
				if ($refContainer === null) {
					$refContainer = $idTable[$reference] = Container::empty();
				}
				if (!$refContainer->hasParent()
					&& !($parent !== null && !$parent->hasAncestor($refContainer))
					&& !($parent !== null && !$refContainer->hasAncestor($parent))) {
					// TODO: Do not add a link if adding that link would introduce a loop: that is, before asserting A->B, search down the children of B to see if A is reachable, and also search down the children of A to see if B is reachable. If either is already reachable as a child of the other, don't add the link.
					$refContainer->setParent($parent);
				}

				$parent = $refContainer;
			}

			// Step 1.C
			//$parentId = $message->getReferences()[count($message->getReferences()) - 1] ?? null;
			//$container->setParent($idTable[$parentId] ?? null);
			if (($parent === null || !$parent->hasAncestor($container)) && $container !== $parent) {
				$container->setParent($parent);
			}
		}
		return $idTable;
	}

	/**
	 * @param Container[] $idTable
	 *
	 * @return Container
	 */
	private function buildRootContainer(array $idTable): Container {
		$rootContainer = Container::empty();
		foreach ($idTable as $id => $container) {
			if (!$container->hasParent()) {
				$container->setParent($rootContainer);
			}
		}
		return $rootContainer;
	}

	/**
	 * @param Container $container
	 */
	private function pruneContainers(Container $root): void {
		/** @var Container $container */
		foreach ($root->getChildren() as $id => $container) {
			// Step 4.A
			if (!$container->hasMessage() && !$container->hasChildren()) {
				$container->unlink();
				continue;
			}

			// Step 4.B
			if (!$container->hasMessage() && $container->hasChildren()) {
				if (!$container->getParent()->isRoot() && count($container->getChildren()) > 1) {
					// Do not promote
					continue;
				}

				foreach ($container->getChildren() as $child) {
					$parent = $container->getParent();
					$child->setParent($parent);
					$container->unlink();
				}
			}

			// Descend recursively (we don't care about the returned array here
			// but only for the root set)
			$this->pruneContainers($container);
		}
	}

	/**
	 * @param Container $root
	 */
	private function groupBySubject(Container $root): void {
		// Step 5.A
		/** @var Container[] $subjectTable */
		$subjectTable = [];

		// Step 5.B
		foreach ($root->getChildren() as $container) {
			$subject = $this->getSubTreeSubject($container);
			if ($subject === '') {
				continue;
			}

			$existingContainer = $subjectTable[$subject] ?? null;
			$existingMessage = $existingContainer !== null ? $existingContainer->getMessage() : null;
			$thisMessage = $container->getMessage();
			if (!array_key_exists($subject, $subjectTable)
				|| (!$container->hasMessage() && $existingContainer !== null && $existingContainer->hasMessage())
				|| ($existingMessage !== null && $existingMessage->hasReSubject() && $thisMessage !== null && !$thisMessage->hasReSubject())) {
				$subjectTable[$subject] = $container;
			}
		}

		// Step 5.C
		foreach ($root->getChildren() as $container) {
			$subject = $this->getSubTreeSubject($container);
			$subjectContainer = $subjectTable[$subject] ?? null;
			if ($subjectContainer === null || $subjectContainer === $container) {
				continue;
			}

			if (!$container->hasMessage() && !$subjectContainer->hasMessage()) {
				// Merge the current subject container into this one and replace it
				foreach ($subjectContainer->getChildren() as $child) {
					$child->setParent($container);
				}
				$subjectTable[$subject] = $container;
			} elseif (!$container->hasMessage() && $subjectContainer->hasMessage()) {
				$subjectContainer->setParent($container);
			} elseif ($container->hasMessage() && !$subjectContainer->hasMessage()) {
				$container->setParent($subjectContainer);
			} elseif ($subjectContainer->hasMessage() && !$subjectContainer->getMessage()->hasReSubject()
				&& $container->hasMessage() && $container->getMessage()->hasReSubject()) {
				$container->setParent($subjectContainer);
				$subjectTable[$subject];
			}
			/*
			 * According to RFC5256 we would have to combine two messages with the same subject
			 * to a thread. But this will also group unrelated messages, so we deliberately omit
			 * this, just like most other clients do.
			 */
		}
	}

	private function getSubTreeSubject(Container $container): string {
		if (($message = $container->getMessage()) !== null) {
			return $message->getBaseSubject();
		}

		$firstChild = $container->getChildren()[0] ?? null;
		if ($firstChild === null || ($message = $firstChild->getMessage()) === null) {
			// should not happen
			return '';
		}
		return $message->getBaseSubject();
	}
}
