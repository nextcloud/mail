<?php
/**
 * ownCloud - mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright Thomas Müller 2014
 */

namespace OCA\Mail\AppInfo;

use \OCP\AppFramework\App;

class Application extends App {

	public function __construct (array $urlParams=array()) {
		parent::__construct('mail', $urlParams);

		$container = $this->getContainer();
		$transport = $container->getServer()->getConfig()->getSystemValue('app.mail.transport', 'smtp');
		$testSmtp = $transport === 'smtp';

		$user = $container->query("UserId");
		$container->registerParameter("appName", "mail");
		$container->registerParameter("userFolder", $container->getServer()->getUserFolder($user));
		$container->registerParameter("testSmtp", $testSmtp);
		$container->registerParameter("referrer", isset($_SERVER['HTTP_REFERER']) ? : null);
		$container->registerParameter("hostname", \OCP\Util::getServerHostName());
	}

}
