<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

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
		$container->registerParameter("hostname", Util::getServerHostName());
	}

}
