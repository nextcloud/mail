<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

use OC;
use OCA\Mail\Command\CreateAccount;

$accountService = OC::$server->query('OCA\Mail\Service\AccountService');
/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateAccount($accountService));
