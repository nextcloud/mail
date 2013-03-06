<?php
/**
 * Horde_Autoloader_default
 *
 * Default autoloader definition that simply uses the include path with default
 * class path mappers.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Autoloader
 */
require_once 'Horde/Autoloader.php';
require_once 'Horde/Autoloader/ClassPathMapper.php';
require_once 'Horde/Autoloader/ClassPathMapper/Default.php';

class Horde_Autoloader_Default extends Horde_Autoloader
{
    public function __construct()
    {
        foreach (array_reverse(explode(PATH_SEPARATOR, get_include_path())) as $path) {
            if ($path == '.') { continue; }
            $path = realpath($path);
            if ($path) {
                $this->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Default($path));
            }
        }
    }
}

$__autoloader = new Horde_Autoloader_Default();
$__autoloader->registerAutoloader();
