<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Template;

use Arikaim\Core\Utils\Utils;

/**
 * Tmplate tests functions
 */
class Tests  
{
   /**
     * Return true if var is object
     *
     * @param mixed $var
     * @return boolean
     */
    public static function isObject($var)
    {
        return \is_object($var);
    }

    /**
     * Return true if var is string
     *
     * @param mixed $var
     * @return boolean
     */
    public static function isString($var)
    {
        return \is_string($var);
    }

    /**
     * Compare version (if requiredVersion is > currentVersion retrun true)
     *
     * @param string|null $requiredVersion
     * @param string|null $currentVersion   
     * @return boolean
     */
    public static function versionCompare($requiredVersion, $currentVersion)
    {
        return Utils::checkVersion($currentVersion,$requiredVersion,'>');  
    }
}
