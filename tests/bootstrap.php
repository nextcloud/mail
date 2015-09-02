<?php

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';
require_once __DIR__.'/../vendor/autoload.php';
\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
