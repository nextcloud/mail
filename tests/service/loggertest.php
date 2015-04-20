<?php

namespace OCA\Mail\Tests\Service;

class LoggerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providesLoggerMethods
	 * @param $method
	 */
	public function testLoggerMethod($method) {

		$baseLogger = $this->getMock('\OCP\ILogger');
		$baseLogger->expects($this->once())
			->method($method)
			->with(
				$this->equalTo('1'),
				$this->equalTo(['app' => 'mail'])
			);

		$logger = new \OCA\Mail\Service\Logger('mail', $baseLogger);
		$logger->$method("1");
    }

	public function providesLoggerMethods() {
		return [
			['alert'],
			['warning'],
			['emergency'],
			['critical'],
			['error'],
			['notice'],
			['info'],
			['debug'],
		];
	}
}
