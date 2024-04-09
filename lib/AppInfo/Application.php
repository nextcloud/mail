<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

use Horde_Translation;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Contracts\IDkimService;
use OCA\Mail\Contracts\IDkimValidator;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Dashboard\ImportantMailWidget;
use OCA\Mail\Dashboard\ImportantMailWidgetV2;
use OCA\Mail\Dashboard\UnreadMailWidget;
use OCA\Mail\Dashboard\UnreadMailWidgetV2;
use OCA\Mail\Events\BeforeImapClientCreated;
use OCA\Mail\Events\DraftMessageCreatedEvent;
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\OutboxMessageCreatedEvent;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\HordeTranslationHandler;
use OCA\Mail\Http\Middleware\ErrorMiddleware;
use OCA\Mail\Http\Middleware\ProvisioningMiddleware;
use OCA\Mail\Listener\AccountSynchronizedThreadUpdaterListener;
use OCA\Mail\Listener\AddressCollectionListener;
use OCA\Mail\Listener\DeleteDraftListener;
use OCA\Mail\Listener\HamReportListener;
use OCA\Mail\Listener\InteractionListener;
use OCA\Mail\Listener\MailboxesSynchronizedSpecialMailboxesUpdater;
use OCA\Mail\Listener\MessageCacheUpdaterListener;
use OCA\Mail\Listener\MessageKnownSinceListener;
use OCA\Mail\Listener\MoveJunkListener;
use OCA\Mail\Listener\NewMessageClassificationListener;
use OCA\Mail\Listener\OauthTokenRefreshListener;
use OCA\Mail\Listener\OptionalIndicesListener;
use OCA\Mail\Listener\OutOfOfficeListener;
use OCA\Mail\Listener\SpamReportListener;
use OCA\Mail\Listener\UserDeletedListener;
use OCA\Mail\Notification\Notifier;
use OCA\Mail\Search\FilteringProvider;
use OCA\Mail\Search\Provider;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\AvatarService;
use OCA\Mail\Service\DkimService;
use OCA\Mail\Service\DkimValidator;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\TrustedSenderService;
use OCA\Mail\Service\UserPreferenceService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\IServerContainer;
use OCP\Search\IFilteringProvider;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\Util;
use Psr\Container\ContainerInterface;
use function interface_exists;

include_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap {
	public const APP_ID = 'mail';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerParameter('hostname', Util::getServerHostName());

		$context->registerService('userFolder', static function (ContainerInterface $c) {
			$userContainer = $c->get(IServerContainer::class);
			$uid = $c->get('UserId');

			return $userContainer->getUserFolder($uid);
		});

		$context->registerServiceAlias(IAvatarService::class, AvatarService::class);
		$context->registerServiceAlias(IAttachmentService::class, AttachmentService::class);
		$context->registerServiceAlias(IMailManager::class, MailManager::class);
		$context->registerServiceAlias(IMailSearch::class, MailSearch::class);
		$context->registerServiceAlias(IMailTransmission::class, MailTransmission::class);
		$context->registerServiceAlias(ITrustedSenderService::class, TrustedSenderService::class);
		$context->registerServiceAlias(IUserPreferences::class, UserPreferenceService::class);
		$context->registerServiceAlias(IDkimService::class, DkimService::class);
		$context->registerServiceAlias(IDkimValidator::class, DkimValidator::class);

		$context->registerEventListener(AddMissingIndicesEvent::class, OptionalIndicesListener::class);
		$context->registerEventListener(BeforeImapClientCreated::class, OauthTokenRefreshListener::class);
		$context->registerEventListener(DraftSavedEvent::class, DeleteDraftListener::class);
		$context->registerEventListener(DraftMessageCreatedEvent::class, DeleteDraftListener::class);
		$context->registerEventListener(OutboxMessageCreatedEvent::class, DeleteDraftListener::class);
		$context->registerEventListener(MailboxesSynchronizedEvent::class, MailboxesSynchronizedSpecialMailboxesUpdater::class);
		$context->registerEventListener(MessageFlaggedEvent::class, MessageCacheUpdaterListener::class);
		$context->registerEventListener(MessageFlaggedEvent::class, SpamReportListener::class);
		$context->registerEventListener(MessageFlaggedEvent::class, HamReportListener::class);
		$context->registerEventListener(MessageFlaggedEvent::class, MoveJunkListener::class);
		$context->registerEventListener(MessageDeletedEvent::class, MessageCacheUpdaterListener::class);
		$context->registerEventListener(MessageSentEvent::class, AddressCollectionListener::class);
		$context->registerEventListener(MessageSentEvent::class, InteractionListener::class);
		$context->registerEventListener(NewMessagesSynchronized::class, NewMessageClassificationListener::class);
		$context->registerEventListener(NewMessagesSynchronized::class, MessageKnownSinceListener::class);
		$context->registerEventListener(SynchronizationEvent::class, AccountSynchronizedThreadUpdaterListener::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);

		// TODO: drop condition if nextcloud < 28 is not supported anymore
		if (class_exists(OutOfOfficeStartedEvent::class)
			&& class_exists(OutOfOfficeEndedEvent::class)
			&& class_exists(OutOfOfficeChangedEvent::class)
			&& class_exists(OutOfOfficeClearedEvent::class)
			&& class_exists(OutOfOfficeScheduledEvent::class)
		) {
			$context->registerEventListener(OutOfOfficeStartedEvent::class, OutOfOfficeListener::class);
			$context->registerEventListener(OutOfOfficeEndedEvent::class, OutOfOfficeListener::class);
			$context->registerEventListener(OutOfOfficeChangedEvent::class, OutOfOfficeListener::class);
			$context->registerEventListener(OutOfOfficeClearedEvent::class, OutOfOfficeListener::class);
			$context->registerEventListener(OutOfOfficeScheduledEvent::class, OutOfOfficeListener::class);
		}

		$context->registerMiddleWare(ErrorMiddleware::class);
		$context->registerMiddleWare(ProvisioningMiddleware::class);

		if (interface_exists(IAPIWidgetV2::class)) {
			$context->registerDashboardWidget(ImportantMailWidgetV2::class);
			$context->registerDashboardWidget(UnreadMailWidgetV2::class);
		} else {
			$context->registerDashboardWidget(ImportantMailWidget::class);
			$context->registerDashboardWidget(UnreadMailWidget::class);
		}

		if (interface_exists(IFilteringProvider::class)) {
			$context->registerSearchProvider(FilteringProvider::class);
		} else {
			$context->registerSearchProvider(Provider::class);
		}

		$context->registerNotifierService(Notifier::class);

		// bypass Horde Translation system
		Horde_Translation::setHandler('Horde_Imap_Client', new HordeTranslationHandler());
		Horde_Translation::setHandler('Horde_Mime', new HordeTranslationHandler());
		Horde_Translation::setHandler('Horde_Smtp', new HordeTranslationHandler());
	}

	public function boot(IBootContext $context): void {
	}
}
