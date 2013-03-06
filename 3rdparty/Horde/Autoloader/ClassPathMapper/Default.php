<?php
/**
 * Maps classes to paths following the PHP Framework Interop Group PSR-0
 * reference implementation. Under this guideline, the following rules apply:
 *
 *   Each namespace separator is converted to a DIRECTORY_SEPARATOR when loading from the file system.
 *   Each "_" character in the CLASS NAME is converted to a DIRECTORY_SEPARATOR. The "_" character has no special meaning in the namespace.
 *   The fully-qualified namespace and class is suffixed with ".php" when loading from the file system.
 *
 * Examples:
 *
 *   \Doctrine\Common\IsolatedClassLoader => /path/to/project/lib/vendor/Doctrine/Common/IsolatedClassLoader.php
 *   \namespace\package\Class_Name => /path/to/project/lib/vendor/namespace/package/Class/Name.php
 *   \namespace\package_name\Class_Name => /path/to/project/lib/vendor/namespace/package_name/Class/Name.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Default implements Horde_Autoloader_ClassPathMapper
{
    private $_includePath;

    public function __construct($includePath)
    {
        $this->_includePath = $includePath;
    }

    public function mapToPath($className)
    {
        // @FIXME: Follow reference implementation
        $relativePath = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $className) . '.php';
        return $this->_includePath . DIRECTORY_SEPARATOR . $relativePath;
    }
}
