<?php
/**
 * Load classes from a specific path matching a specific prefix.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Autoloader
 */
class Horde_Autoloader_ClassPathMapper_Prefix implements Horde_Autoloader_ClassPathMapper
{
    private $_pattern;
    private $_includePath;

    public function __construct($pattern, $includePath)
    {
        $this->_pattern = $pattern;
        $this->_includePath = $includePath;
    }

    public function mapToPath($className)
    {
        if (preg_match($this->_pattern, $className, $matches, PREG_OFFSET_CAPTURE)) {
            if (strcasecmp($matches[0][0], $className) === 0) {
                return "$this->_includePath/$className.php";
            } else {
                return str_replace(array('\\', '_'), '/', substr($className, 0, $matches[0][1])) .
                    $this->_includePath . '/' .
                    str_replace(array('\\', '_'), '/', substr($className, $matches[0][1] + strlen($matches[0][0]))) .
                    '.php';
            }
        }

        return false;
    }
}
