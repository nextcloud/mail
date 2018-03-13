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

use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\AvatarService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\UserPreferenceSevice;
use OCA\Mail\Service\Group\IGroupService;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCP\AppFramework\App;
use OCP\Util;

class Application extends App {

	public function __construct(array $urlParams = []) {
		parent::__construct('mail', $urlParams);

		$this->initializeAppContainer();
	}

	private function initializeAppContainer() {
		$container = $this->getContainer();

		$transport = $container->getServer()->getConfig()->getSystemValue('app.mail.transport', 'smtp');
		$testSmtp = $transport === 'smtp';

		$container->registerAlias(IAvatarService::class, AvatarService::class);
		$container->registerAlias(IAttachmentService::class, AttachmentService::class);
		$container->registerAlias(IMailManager::class, MailManager::class);
		$container->registerAlias(IMailTransmission::class, MailTransmission::class);
		$container->registerAlias(IUserPreferences::class, UserPreferenceSevice::class);
		$container->registerService('OCP\ISession', function ($c) {
			return $c->getServer()->getSession();
		});

		$container->registerParameter("appName", "mail");
		$container->registerService("userFolder", function () use ($container) {
			$user = $container->query("UserId");
			return $container->getServer()->getUserFolder($user);
		});

		$container->registerParameter("testSmtp", $testSmtp);
		$container->registerParameter("referrer", isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
		$container->registerParameter("hostname", Util::getServerHostName());

		$container->registerAlias('ErrorMiddleware', ErrorMiddleware::class);
		$container->registerMiddleWare('ErrorMiddleware');

		$container->registerAlias(IGroupService::class, NextcloudGroupService::class);
	}

}
