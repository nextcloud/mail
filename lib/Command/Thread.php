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

namespace OCA\Mail\Command;

use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\IMAP\Threading\ThreadBuilder;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function memory_get_peak_usage;

class Thread extends Command {
	public const ARGUMENT_INPUT_FILE = 'thread-file';

	private ThreadBuilder $builder;
	private LoggerInterface $logger;

	public function __construct(ThreadBuilder $builder,
								LoggerInterface $logger) {
		parent::__construct();
		$this->builder = $builder;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:thread');
		$this->setDescription('Build threads from the exported data of an account');
		$this->addArgument(self::ARGUMENT_INPUT_FILE, InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		$inputFile = $input->getArgument(self::ARGUMENT_INPUT_FILE);

		if (!file_exists($inputFile)) {
			$output->writeln("<error>File $inputFile does not exist</error>");
			return 1;
		}

		$json = file_get_contents($inputFile);
		if ($json === false) {
			$output->writeln("<error>Could not read thread data</error>");
			return 2;
		}
		$consoleLogger->debug(strlen($json) . 'B read');
		$parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		$consoleLogger->debug(count($parsed) . ' data sets loaded');
		$threadData = array_map(static function ($serialized) {
			return new DatabaseMessage(
				$serialized['databaseId'],
				$serialized['subject'],
				$serialized['id'],
				$serialized['references'],
				$serialized['threadRootId'] ?? null
			);
		}, $parsed);

		$threads = $this->builder->build($threadData, $consoleLogger);
		$output->writeln(count($threads) . " threads built from " . count($threadData) . " messages");

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');

		return 0;
	}
}
