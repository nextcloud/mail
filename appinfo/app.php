<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Thomas Imbreckx <zinks@iozero.be>
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
use OCA\Mail\HordeTranslationHandler;

if ((@include_once __DIR__ . '/../vendor/autoload.php')===false) {
	throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
}

// bypass Horde Translation system
Horde_Translation::setHandler('Horde_Imap_Client', new HordeTranslationHandler());
Horde_Translation::setHandler('Horde_Mime', new HordeTranslationHandler());
Horde_Translation::setHandler('Horde_Smtp', new HordeTranslationHandler());

$l = \OC::$server->getL10N('mail');
$g =  \OC::$server->getURLGenerator();

\OC::$server->getNavigationManager()->add(
	[
		'id' => 'mail',
		'order' => 3,
		'href' => $g->linkToRoute('mail.page.index'),
		'icon' => $g->imagePath('mail', 'mail.svg'),
		'name' => $l->t('Mail'),
	]
);
