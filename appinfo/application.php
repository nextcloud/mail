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

namespace OCA\Mail\AppInfo;

use \OCP\AppFramework\App;

class Application extends App {

	public static $ispUrls = [
	    'https://autoconfig.{DOMAIN}/mail/config-v1.1.xml',
	    'https://{DOMAIN}/.well-known/autoconfig/mail/config-v1.1.xml',
	    'https://autoconfig.thunderbird.net/v1.1/{DOMAIN}',
	];

	public function __construct(array $urlParams = []) {
		parent::__construct('mail', $urlParams);

		$container = $this->getContainer();

		$transport = $container->getServer()->getConfig()->getSystemValue('app.mail.transport', 'smtp');
		$testSmtp = $transport === 'smtp';

		$container->registerService('OCP\ISession', function ($c) {
			return $c->getServer()->getSession();
		});

		$user = $container->query("UserId");
		$container->registerParameter("appName", "mail");
		$container->registerParameter("userFolder", $container->getServer()->getUserFolder($user));
		$container->registerParameter("testSmtp", $testSmtp);
		$container->registerParameter("referrer", isset($_SERVER['HTTP_REFERER']) ? : null);
		$container->registerParameter("hostname", \OCP\Util::getServerHostName());
		$container->registerParameter('ispUrls', self::$ispUrls);
	}

}
