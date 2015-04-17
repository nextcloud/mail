<?php

use OCA\Mail\HordeTranslationHandler;

if ((@include_once __DIR__ . '/../vendor/autoload.php')===false) {
	throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
}

// bypass Horde Translation system
Horde_Translation::setHandler('Horde_Imap_Client', new HordeTranslationHandler());
Horde_Translation::setHandler('Horde_Mime', new HordeTranslationHandler());
Horde_Translation::setHandler('Horde_Smtp', new HordeTranslationHandler());

\OC::$server->getNavigationManager()->add(
	function () {
		$l = \OC::$server->getL10N('mail');
		$g =  \OC::$server->getURLGenerator();
		return [
			'id' => 'mail',
			'order' => 1,
			'href' => $g->linkToRoute( 'mail.page.index' ),
			'icon' => $g->imagePath( 'mail', 'mail.svg' ),
			'name' => $l->t('Mail'),
		];
	}
);
