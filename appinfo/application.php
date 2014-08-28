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


use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Controller\FoldersController;
use OCA\Mail\Controller\MessagesController;
use OCA\Mail\Controller\ProxyController;
use OCA\Mail\Controller\SettingsController;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AutoConfig;
use OCA\Mail\Service\ContactsIntegration;
use \OCP\AppFramework\App;

use \OCA\Mail\Controller\PageController;
use OCP\AppFramework\IAppContainer;


class Application extends App {


	public function __construct (array $urlParams=array()) {
		parent::__construct('mail', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('PageController', function($c) {
			/** @var IAppContainer $c */
			return new PageController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId')
			);
		});

		$container->registerService('SettingsController', function($c) {
			/** @var IAppContainer $c */
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId')
			);
		});

		$container->registerService('AccountsController', function($c) {
			/** @var IAppContainer $c */
			return new AccountsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId'),
				$c->getServer()->getUserFolder(),
				$c->query('ContactsIntegration'),
				$c->query('AutoConfig')
			);
		});

		$container->registerService('FoldersController', function($c) {
			/** @var IAppContainer $c */
			return new FoldersController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId')
			);
		});

		$container->registerService('MessagesController', function($c) {
			/** @var IAppContainer $c */
			return new MessagesController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId'),
				$c->getServer()->getUserFolder(),
				$c->query('ContactsIntegration')
			);
		});

		$container->registerService('ProxyController', function($c) {
			/** @var IAppContainer $c */
			return new ProxyController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ServerContainer')->getURLGenerator(),
				$c->query('ServerContainer')->getSession()
			);
		});

		/**
		 * Mappers
		 */
		$container->registerService('MailAccountMapper', function ($c) {
			/** @var IAppContainer $c */
			return new MailAccountMapper($c->getServer()->getDb());
		});

		/**
		 * Services
		 */
		$container->registerService('ContactsIntegration', function ($c) {
			/** @var IAppContainer $c */
			return new ContactsIntegration($c->getServer()->getContactsManager());
		});

		$container->registerService('AutoConfig', function ($c) {
			/** @var IAppContainer $c */
			return new AutoConfig(
				$c->query('UserId')
			);
		});
		/**
		 * Core
		 */
		$container->registerService('UserId', function($c) {
			return \OCP\User::getUser();
		});
	}
}
