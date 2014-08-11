<?php

use OCA\Mail\HordeTranslationHandler;

$autoload = __DIR__ .  '/../vendor/autoload.php';
if (!is_readable($autoload)) {
	print 'Cannot read "autoload.php". Did you run composer ?'.
	      "<pre>cd path/to/owncloud/apps/mail\n".
	      "curl -sS https://getcomposer.org/installer | php\n".
	      'php composer.phar install</pre>';
	exit;
}
require_once $autoload;
$l = OC_L10N::get('mail');

// bypass Horde Translation system
Horde_Translation::setHandler('Horde_Imap_Client', new HordeTranslationHandler());
Horde_Translation::setHandler('Horde_Mime', new HordeTranslationHandler());

OCP\App::addNavigationEntry(array(
  'id' => 'mail',
  'order' => 1,
  'href' => OCP\Util::linkToRoute( 'mail.page.index' ),
  'icon' => OCP\Util::imagePath( 'mail', 'mail.svg' ),
  'name' => $l->t('Mail'),
));

//OCP\App::registerPersonal('mail','settings');
