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

namespace mail\lib\IMAP\Threading;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\Threading\Container;
use OCA\Mail\IMAP\Threading\Message;
use OCA\Mail\IMAP\Threading\ThreadBuilder;
use OCA\Mail\Support\PerformanceLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ThreadBuilderTest extends TestCase {
	/** @var PerformanceLogger|MockObject */
	private $performanceLogger;

	/** @var LoggerInterface */
	private $logger;

	/** @var ThreadBuilder */
	private $builder;

	protected function setUp(): void {
		parent::setUp();

		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->logger = new NullLogger();

		$this->builder = new ThreadBuilder(
			$this->performanceLogger
		);
	}

	/**
	 * @param Container[] $set
	 *
	 * @return array
	 */
	private function abstract(array $set): array {
		return array_map(function (Container $container) {
			return [
				'id' => (($message = $container->getMessage()) !== null ? $message->getId() : null),
				'children' => $this->abstract($container->getChildren()),
			];
		}, array_values($set));
	}

	public function testBuildEmpty(): void {
		$messages = [];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals([], $result);
	}

	public function testBuildFlat(): void {
		$messages = [
			new Message('s1', 'id1', []),
			new Message('s2', 'id2', []),
			new Message('s3', 'id3', []),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [],
				],
				[
					'id' => 'id2',
					'children' => [],
				],
				[
					'id' => 'id3',
					'children' => [],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildOneDeep(): void {
		$messages = [
			new Message('s1', 'id1', []),
			new Message('Re:s1', 'id2', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildOneDeepMismatchingSubjects(): void {
		$messages = [
			new Message('s1', 'id1', []),
			new Message('s2', 'id2', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildOneDeepNoReferences(): void {
		$messages = [
			new Message('s1', 'id1', []),
			new Message('Re:s1', 'id2', []),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildTwoDeep(): void {
		// 1
		// |
		// 2
		// |
		// 3
		$messages = [
			new Message('s1', 'id1', []),
			new Message('s2', 'id2', ['id1']),
			new Message('s3', 'id3', ['id2']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [
								[
									'id' => 'id3',
									'children' => [],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildFourDeep(): void {
		// 1
		// |
		// 2
		// |
		// 3
		// |
		// 4
		$messages = [
			new Message('s1', 'id1', []),
			new Message('Re:s1', 'id2', ['id1']),
			new Message('Re:s1', 'id3', ['id2']),
			new Message('Re:s1', 'id4', ['id3']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [
								[
									'id' => 'id3',
									'children' => [
										[
											'id' => 'id4',
											'children' => [],
										],
									],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildTwoWithLoop(): void {
		// 1 (but also points to itself)
		// |
		// 2
		$messages = [
			new Message('s1', 'id1', ['id1']),
			new Message('s2', 'id2', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildTree(): void {
		//        1
		//      /   \
		//     2     3
		//    / \   / \
		//   4   5 6   7
		$messages = [
			new Message('s1', 'id1', []),
			new Message('Re:s1', 'id2', ['id1']),
			new Message('Re:s1', 'id3', ['id1']),
			new Message('Re:s1', 'id4', ['id1', 'id2']),
			new Message('Re:s1', 'id5', ['id1', 'id2']),
			new Message('Re:s1', 'id6', ['id1', 'id3']),
			new Message('Re:s1', 'id7', ['id1', 'id3']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [
								[
									'id' => 'id4',
									'children' => [],
								],
								[
									'id' => 'id5',
									'children' => [],
								],
							],
						],
						[
							'id' => 'id3',
							'children' => [
								[
									'id' => 'id6',
									'children' => [],
								],
								[
									'id' => 'id7',
									'children' => [],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildTreePartialRefs(): void {
		//        1
		//      /   \
		//     2     3
		//    / \   / \
		//   4   5 6   7
		$messages = [
			new Message('s1', 'id1', []),
			new Message('Re:s1', 'id2', ['id1']),
			new Message('Re:s1', 'id3', ['id1']),
			new Message('Re:s1', 'id4', ['id2']),
			new Message('Re:s1', 'id5', ['id2']),
			new Message('Re:s1', 'id6', ['id3']),
			new Message('Re:s1', 'id7', ['id3']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [
								[
									'id' => 'id4',
									'children' => [],
								],
								[
									'id' => 'id5',
									'children' => [],
								],
							],
						],
						[
							'id' => 'id3',
							'children' => [
								[
									'id' => 'id6',
									'children' => [],
								],
								[
									'id' => 'id7',
									'children' => [],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildCyclic(): void {
		$messages = [
			new Message('s1', 'id1', ['id2']),
			new Message('s2', 'id2', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id2',
					'children' => [
						[
							'id' => 'id1',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildSiblingsWithRoot(): void {
		$messages = [
			new Message('s1', 'id1', []),
			new Message('s2', 'id2', ['id1']),
			new Message('s3', 'id3', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => 'id1',
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
						[
							'id' => 'id3',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testBuildSiblingsWithoutRoot(): void {
		$messages = [
			new Message('Re:s1', 'id2', ['id1']),
			new Message('Re:s2', 'id3', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => null,
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
						[
							'id' => 'id3',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testWithVirtualParent(): void {
		$messages = [
			new Message('AW: s1', 'id2', ['id1']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => null,
					'children' => [
						[
							'id' => 'id2',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	/**
	 * This is a test case from real-world data, compared to how Evolution renders the thread
	 *
	 * Message IDs and subject were obfuscated because they don't matter much
	 */
	public function testRealWorldLinearThread(): void {
		$messages = [
			new Message('Sub', '<msg1@mail.host>', []),
			new Message('Re: Sub', '<msg2@mail.host>', ['<msg1@mail.host>']),
			new Message('Re: Sub', '<msg3@mail.host>', ['<msg1@mail.host>', '<msg2@mail.host>']),
			new Message('Re: Sub', '<msg4@mail.host>', ['<msg1@mail.host>', '<msg2@mail.host>', '<msg3@mail.host>']),
			new Message('Re: Sub', '<msg5@mail.host>', ['<msg1@mail.host>', '<msg2@mail.host>', '<msg3@mail.host>', '<msg4@mail.host>']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => '<msg1@mail.host>',
					'children' => [
						[
							'id' => '<msg2@mail.host>',
							'children' => [
								[
									'id' => '<msg3@mail.host>',
									'children' => [
										[
											'id' => '<msg4@mail.host>',
											'children' => [
												[
													'id' => '<msg5@mail.host>',
													'children' => [],
												],
											],
										],
									],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	/**
	 * This is a test case from real-world data, compared to how Evolution renders the thread
	 *
	 * Message IDs and subject were obfuscated because they don't matter much
	 */
	public function testRealWorldTwoSimilarThreads(): void {
		$messages = [
			new Message('Sub', '<o1@mail.host>', []),
			new Message('Re: Sub', '<o1re1@mail.host>', ['<o1@mail.host>']),
			new Message('Re: Sub', '<o1re2@mail.host>', ['<o1@mail.host>']),
			new Message('Re: Sub', '<o1re11@mail.host>', ['<o1@mail.host>', '<o1re1@mail.host>']),
			new Message('Re: Sub', '<o1re111@mail.host>', ['<o1@mail.host>', '<o1re1@mail.host>', '<o1re11@mail.host>']),
			new Message('Re: Sub', '<o1re1111@mail.host>', ['<o1@mail.host>', '<o1re11@mail.host>', '<o1re111@mail.host>']),
			new Message('Re: Sub', '<o1re3@mail.host>', ['<o1@mail.host>']),
			new Message('Re: Sub', '<o1re4@mail.host>', ['<o1@mail.host>']),
			new Message('Re: Sub', '<o1re5@mail.host>', ['<o1@mail.host>']),
			new Message('Re: Sub', '<o1re6@mail.host>', ['<o1@mail.host>']),
			new Message('Sub', '<o2@mail.host>', []),
			new Message('Re: Sub', '<o2re1@mail.host>', ['<o2@mail.host>']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => '<o1@mail.host>',
					'children' => [
						[
							'id' => '<o1re1@mail.host>',
							'children' => [
								[
									'id' => '<o1re11@mail.host>',
									'children' => [
										[
											'id' => '<o1re111@mail.host>',
											'children' => [
												[
													'id' => '<o1re1111@mail.host>',
													'children' => [],
												],
											],
										],
									],
								],
							],
						],
						[
							'id' => '<o1re2@mail.host>',
							'children' => [],
						],
						[
							'id' => '<o1re3@mail.host>',
							'children' => [],
						],
						[
							'id' => '<o1re4@mail.host>',
							'children' => [],
						],
						[
							'id' => '<o1re5@mail.host>',
							'children' => [],
						],
						[
							'id' => '<o1re6@mail.host>',
							'children' => [],
						],
					],
				],
				[
					'id' => '<o2@mail.host>',
					'children' => [
						[
							'id' => '<o2re1@mail.host>',
							'children' => [],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testRealWorldThreadWithReply(): void {
		$messages = [
			new Message('sub', '<454AF3B1-C642-4976-AA00-DB33B34225C1@bollu.be>', ['<1A0C073E-8D77-4F05-9853-4A576D33B819@acme.be>']),
			new Message('sub', '<1A0C073E-8D77-4F05-9853-4A576D33B819@acme.be>', []),
			new Message('sub', '<9009C4EE-C517-4EAE-B0E3-75FE5EA25207@acme.be>', ['<1A0C073E-8D77-4F05-9853-4A576D33B819@acme.be>','<454AF3B1-C642-4976-AA00-DB33B34225C1@bollu.be>']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => '<1A0C073E-8D77-4F05-9853-4A576D33B819@acme.be>',
					'children' => [
						[
							'id' => '<454AF3B1-C642-4976-AA00-DB33B34225C1@bollu.be>',
							'children' => [
								[
									'id' => '<9009C4EE-C517-4EAE-B0E3-75FE5EA25207@acme.be>',
									'children' => [],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}

	public function testRealWorldLinearThreadReferencesAndInReplyTo(): void {
		$messages = [
			new Message('sub', '<testRealWorldLinearThreadReferencesAndInReplyTo1@host>', []),
			new Message('sub', '<testRealWorldLinearThreadReferencesAndInReplyTo2@host>', ['<testRealWorldLinearThreadReferencesAndInReplyTo1@host>', '<testRealWorldLinearThreadReferencesAndInReplyTo1@host>']),
			new Message('sub', '<testRealWorldLinearThreadReferencesAndInReplyTo3@host>', ['<testRealWorldLinearThreadReferencesAndInReplyTo1@host>', '<testRealWorldLinearThreadReferencesAndInReplyTo2@host>', '<testRealWorldLinearThreadReferencesAndInReplyTo2@host>']),
			new Message('sub', '<testRealWorldLinearThreadReferencesAndInReplyTo4@host>', ['<testRealWorldLinearThreadReferencesAndInReplyTo1@host>','<testRealWorldLinearThreadReferencesAndInReplyTo2@host>', '<testRealWorldLinearThreadReferencesAndInReplyTo3@host>']),
		];

		$result = $this->builder->build($messages, $this->logger);

		$this->assertEquals(
			[
				[
					'id' => '<testRealWorldLinearThreadReferencesAndInReplyTo1@host>',
					'children' => [
						[
							'id' => '<testRealWorldLinearThreadReferencesAndInReplyTo2@host>',
							'children' => [
								[
									'id' => '<testRealWorldLinearThreadReferencesAndInReplyTo3@host>',
									'children' => [
										[
											'id' => '<testRealWorldLinearThreadReferencesAndInReplyTo4@host>',
											'children' => [],
										],
									],
								],
							],
						],
					],
				],
			],
			$this->abstract($result)
		);
	}
}
