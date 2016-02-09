<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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
namespace OCA\Mail\Tests\Service;

use OCA\Mail\Service\Logger;

class LoggerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providesLoggerMethods
	 * @param $method
	 */
	public function testLoggerMethod($method, $param = '1') {

		$baseLogger = $this->getMock('\OCP\ILogger');
		$baseLogger->expects($this->once())
			->method($method)
			->with(
				$this->equalTo($param),
				$this->equalTo([
					'app' => 'mail',
					'key' => 'value',
				]));

		$logger = new \OCA\Mail\Service\Logger('mail', $baseLogger);
		$logger->$method($param, ['key' => 'value']);
    }

	public function providesLoggerMethods() {

		$methods = [
			['alert'],
			['warning'],
			['emergency'],
			['critical'],
			['error'],
			['notice'],
			['info'],
			['debug'],
		];
		if (version_compare(implode('.', \OCP\Util::getVersion()), '8.2', '>=')) {
			$methods[]= ['logException', new \Exception()];
		}

		return $methods;
	}

}
