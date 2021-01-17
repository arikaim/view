<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Html;

use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Http\Url;

/**
 *  Resource locator
 */
class ResourceLocator   
{
    const UNKNOWN             = 0;
    const TEMPLATE_COMPONENT  = 1; 
    const EXTENSION_COMPONENT = 2;
    const GLOBAL_COMPONENT    = 3; 
    const PRIMARY_TEMLATE     = 4;  
    const URL_RESOURCE        = 5;
    const FILE_RESOURCE       = 6;

    const EXTENSION_SELECTOR       = '::';
    const TEMPLATE_SELECTOR        = ':';
    const PRIMARY_TEMLATE_SELECTOR = '>';

    /**
     * Get resource selector type
     *
     * @param string $name
     * @return string|null
     */
    public static function getSelectorType(string $name): ?string
    {
        if (\stripos($name,'::') !== false) {
            return Self::EXTENSION_SELECTOR;
        }
        if (\stripos($name,':') !== false) {
            return Self::TEMPLATE_SELECTOR;
        }
        if (\stripos($name,'>') !== false) {
            return Self::PRIMARY_TEMLATE_SELECTOR;
        }

        return null;
    }

    /**
     * Get resource type
     *
     * @param string $name
     * @param string|null $selectorType
     * @return integer
     */
    public static function getType(string $name, ?string $selectorType = null): int
    {
        if (Utils::isValidUrl($name) == true) {
            return Self::URL_RESOURCE;
        }

        $selectorType = (empty($selectorType) == true) ? Self::getSelectorType($name) : $selectorType;
        if (empty($selectorType) == true) {
            return Self::UNKNOWN;
        }
        $tokens = \explode($selectorType,$name);  

        switch ($selectorType) {
            case Self::EXTENSION_SELECTOR:
                $type = Self::EXTENSION_COMPONENT;
                break;
            case Self::TEMPLATE_SELECTOR:             
                $type = ($tokens[0] == 'components') ? Self::GLOBAL_COMPONENT : Self::TEMPLATE_COMPONENT;   
                break;
            case Self::PRIMARY_TEMLATE_SELECTOR:
                $type = Self::PRIMARY_TEMLATE;
                break;
            default:
                $type = Self::UNKNOWN;           
        }

        return $type;
    }

    /**
     * Get resource url
     *
     * @param string $name
     * @param string $default
     * @return string 
     */
    public static function getResourceUrl(string $name, string $default = ''): string
    {
        $data = Self::parse($name);
    
        switch ($data['type']) {
            case Self::URL_RESOURCE:
                return $name;              
            case Self::TEMPLATE_COMPONENT:
                $templateUrl = Url::getTemplateUrl($data['component_name']);                 
                return  $templateUrl . $data['path'];                          
        }

        return $default;
    }

    /**
     * Parse resource name 
     * 
     * @param string $name
     * @return array
     */
    public static function parse(string $name): array
    {
        $selectorType = Self::getSelectorType($name);
      
        if (empty($selectorType) == true) {
            $tokens[0] = $name;
            $type = Self::UNKNOWN;
        } else {
            $tokens = \explode($selectorType,$name); 
            $type = Self::getType($name); 
        } 
        $componentName = (isset($tokens[0]) == true) ? $tokens[0] : null;
        $path = (isset($tokens[1]) == true) ? $tokens[1] : null;
        
        return [
            'path'           => $path,
            'component_name' => $componentName,
            'type'           => $type
        ];
    }   

    /**
     * Get component template name (or extenson)
     *
     * @param string $name
     * @param string|null $primaryTemplate
     * @return string|null
     */
    public static function getTemplateName(string $name, ?string $primaryTemplate): ?string
    {
        $result = Self::parse($name);

        switch($result['type']) {
            case Self::UNKNOWN:
                return null;
            
            case Self::EXTENSION_COMPONENT:
                return (Self::isAdminPath($result['path']) == true) ? 'system' : $primaryTemplate;
        
            case Self::GLOBAL_COMPONENT: 
                return $primaryTemplate;

            case Self::TEMPLATE_COMPONENT:
                return $result['component_name'];

            case Self::PRIMARY_TEMLATE:
                return $primaryTemplate;
        }

        return null;
    }

    /**
     * Return true if component is for control panel 
     *
     * @param string $path
     * @return boolean
     */
    public static function isAdminPath(string $path): bool
    {
        return (\substr($path,0,5) == 'admin');
    }
}
