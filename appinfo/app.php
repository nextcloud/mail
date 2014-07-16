<?php

OC::$CLASSPATH['OCA\Mail\App'] = 'apps/mail/lib/mail.php';
OC::$CLASSPATH['OCA\Mail\Account'] = 'apps/mail/lib/account.php';
OC::$CLASSPATH['OCA\Mail\Mailbox'] = 'apps/mail/lib/mailbox.php';
OC::$CLASSPATH['OCA\Mail\Message'] = 'apps/mail/lib/message.php';
OC::$CLASSPATH['OC_Translation_Handler'] = 'apps/mail/lib/OC_Translation_Handler.php';

require_once __DIR__ .  '/../vendor/autoload.php';

// bypass Horde Translation system
Horde_Translation::setHandler('Horde_Imap_Client', new OC_Translation_Handler());

OCP\App::addNavigationEntry( array(
  'id' => 'mail_index',
  'order' => 1,
  'href' => OCP\Util::linkToRoute( 'mail.page.index' ),
  'icon' => OCP\Util::imagePath( 'mail', 'mail.svg' ),
  'name' => 'Mail'));

//OCP\App::registerPersonal('mail','settings');
