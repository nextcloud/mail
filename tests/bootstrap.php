<?php

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';
require_once __DIR__.'/../vendor/autoload.php';

if (version_compare(implode('.', \OCP\Util::getVersion()), '8.2', '>=')) {
	\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
	\OC_App::loadApp('mail');
}

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
