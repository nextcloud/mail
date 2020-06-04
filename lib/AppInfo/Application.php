<?php

declare(strict_types=1);

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
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\SaveDraftEvent;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCA\Mail\Http\Middleware\ProvisioningMiddleware;
use OCA\Mail\Listener\AddressCollectionListener;
use OCA\Mail\Listener\DeleteDraftListener;
use OCA\Mail\Listener\DraftMailboxCreatorListener;
use OCA\Mail\Listener\FlagRepliedMessageListener;
use OCA\Mail\Listener\InteractionListener;
use OCA\Mail\Listener\MessageCacheUpdaterListener;
use OCA\Mail\Listener\NewMessageClassificationListener;
use OCA\Mail\Listener\SaveSentMessageListener;
use OCA\Mail\Listener\TrashMailboxCreatorListener;
use OCA\Mail\Listener\UserDeletedListener;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\AvatarService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\UserPreferenceSevice;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;

class Application extends App {
	public const APP_ID = 'mail';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$this->initializeAppContainer($this->getContainer());
		$this->registerEvents($this->getContainer());
	}

	private function initializeAppContainer(IAppContainer $container): void {
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
		$container->registerAlias('ProvisioningMiddleware', ProvisioningMiddleware::class);
		$container->registerMiddleWare('ProvisioningMiddleware');
	}

	private function registerEvents(IAppContainer $container): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $container->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(BeforeMessageDeletedEvent::class, TrashMailboxCreatorListener::class);
		$dispatcher->addServiceListener(DraftSavedEvent::class, DeleteDraftListener::class);
		$dispatcher->addServiceListener(MessageFlaggedEvent::class, MessageCacheUpdaterListener::class);
		$dispatcher->addServiceListener(MessageDeletedEvent::class, MessageCacheUpdaterListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, AddressCollectionListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, DeleteDraftListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, FlagRepliedMessageListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, InteractionListener::class);
		$dispatcher->addServiceListener(MessageSentEvent::class, SaveSentMessageListener::class);
		$dispatcher->addServiceListener(NewMessagesSynchronized::class, NewMessageClassificationListener::class);
		$dispatcher->addServiceListener(SaveDraftEvent::class, DraftMailboxCreatorListener::class);
		$dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
	}
}
