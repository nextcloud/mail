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
use OCA\Mail\Db\MailAccountMapper;
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

		$container->registerService('AccountsController', function($c) {
			/** @var IAppContainer $c */
			return new AccountsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('MailAccountMapper'),
				$c->query('UserId')
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
				$c->query('UserId')
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
		 * Core
		 */
		$container->registerService('UserId', function($c) {
			return \OCP\User::getUser();
		});
	}
}
