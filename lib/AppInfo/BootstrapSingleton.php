<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\AppInfo;

use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Events\SaveDraftEvent;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCA\Mail\Listener\AddressCollectionListener;
use OCA\Mail\Listener\DeleteDraftListener;
use OCA\Mail\Listener\DraftMailboxCreatorListener;
use OCA\Mail\Listener\FlagRepliedMessageListener;
use OCA\Mail\Listener\SaveSentMessageListener;
use OCA\Mail\Listener\TrashMailboxCreatorListener;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\AvatarService;
use OCA\Mail\Service\Group\IGroupService;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\MailSearch;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\UserPreferenceSevice;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IContainer;
use OCP\Util;

class BootstrapSingleton {

	/** @var BootstrapSingleton */
	private static $instance = null;

	/** @var bool */
	private $booted = false;

	/** @var IAppContainer */
	private $container;

	private function __construct(IAppContainer $container) {
		$this->container = $container;
	}

	public static function getInstance(IAppContainer $container): BootstrapSingleton {
		if (self::$instance === null) {
			self::$instance = new static($container);
		}

		return self::$instance;
	}

	public function boot(): void {
		if ($this->booted) {
			return;
		}

		$this->initializeAppContainer($this->container);
		$this->registerEvents($this->container);

		$this->booted = true;
	}

	private function initializeAppContainer(IAppContainer $container) {
		$transport = $container->getServer()->getConfig()->getSystemValue('app.mail.transport', 'smtp');
		$testSmtp = $transport === 'smtp';

		$container->registerAlias(IAvatarService::class, AvatarService::class);
		$container->registerAlias(IAttachmentService::class, AttachmentService::class);
		$container->registerAlias(IMailManager::class, MailManager::class);
		$container->registerAlias(IMailSearch::class, MailSearch::class);
		$container->registerAlias(IMailTransmission::class, MailTransmission::class);
		$container->registerAlias(IUserPreferences::class, UserPreferenceSevice::class);

		$container->registerService("userFolder", function () use ($container) {
			$user = $container->query("UserId");
			return $container->getServer()->getUserFolder($user);
		});

		$container->registerParameter('testSmtp', $testSmtp);
		$container->registerParameter('hostname', Util::getServerHostName());

		$container->registerAlias('ErrorMiddleware', ErrorMiddleware::class);
		$container->registerMiddleWare('ErrorMiddleware');

		$container->registerAlias(IGroupService::class, NextcloudGroupService::class);
	}

	private function registerEvents(IAppContainer $container): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(BeforeMessageDeletedEvent::class, TrashMailboxCreatorListener::class);
		$dispatcher->addServiceListener(DraftSavedEvent::class, DeleteDraftListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, AddressCollectionListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, DeleteDraftListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, FlagRepliedMessageListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, SaveSentMessageListener::class);
		$dispatcher->addServiceListener(SaveDraftEvent::class, DraftMailboxCreatorListener::class);
	}

}
