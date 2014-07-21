<?php

use OCA\Mail\HordeTranslationHandler;

require_once __DIR__ .  '/../vendor/autoload.php';

// bypass Horde Translation system
Horde_Translation::setHandler('Horde_Imap_Client', new HordeTranslationHandler());

OCP\App::addNavigationEntry( array(
  'id' => 'mail_index',
  'order' => 1,
  'href' => OCP\Util::linkToRoute( 'mail.page.index' ),
  'icon' => OCP\Util::imagePath( 'mail', 'mail.svg' ),
  'name' => 'Mail'));

//OCP\App::registerPersonal('mail','settings');
