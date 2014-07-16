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

use \OCA\Mail\Controller\PageController;


class Application extends App {


	public function __construct (array $urlParams=array()) {
		parent::__construct('mail', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('PageController', function($c) {
			return new PageController(
			$c->query('AppName'),
				$c->query('Request'),
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
