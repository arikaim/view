<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View;

use Arikaim\Core\Interfaces\View\ComponentInterface;

/**
 * Component factory class
 */
class ComponentFactory 
{
    /**
     *  Component render classes
    */
    const COMPONENT_RENDER_CLASSES = [
        ComponentInterface::EMPTY_COMPONENT_TYPE   => '\\Arikaim\\Core\\View\\Html\\Component\\EmptyComponent',
        ComponentInterface::ARIKAIM_COMPONENT_TYPE => '\\Arikaim\\Core\\View\\Html\\Component\\ArikaimComponent',
        ComponentInterface::STATIC_COMPONENT_TYPE  => '\\Arikaim\\Core\\View\\Html\\Component\\StaticHtmlComponent',
        ComponentInterface::JSON_COMPONENT_TYPE    => '\\Arikaim\\Core\\View\\Html\\Component\\JsonComponent',
        ComponentInterface::SVG_COMPONENT_TYPE     => '\\Arikaim\\Core\\View\\Html\\Component\\SvgComponent',
        ComponentInterface::HTML_COMPONENT_TYPE    => '\\Arikaim\\Core\\View\\Html\\Component\\HtmlComponent',
        ComponentInterface::JS_COMPONENT_TYPE      => '\\Arikaim\\Core\\View\\Html\\Component\\JsComponent'
    ];

    /**
     * Create view component
     *
     * @param string $name
     * @param string $language
     * @param string $type
     * @param string $viewPath
     * @param string $extensionsPath
     * @param string $primaryTemplate
     * @return Arikaim\Core\Interfaces\View\ComponentInterface
     */
    public static function create(
        string $name,
        string $language, 
        string $type,
        string $viewPath,
        string $extensionsPath,
        string $primaryTemplate
    )
    {
        $type = $type ?? ComponentInterface::ARIKAIM_COMPONENT_TYPE;
        if (isset(Self::COMPONENT_RENDER_CLASSES[$type]) == false) {
            $type = ComponentInterface::ARIKAIM_COMPONENT_TYPE;
        }

        $class = Self::COMPONENT_RENDER_CLASSES[$type];
        $component =  new $class($name,$language,$viewPath,$extensionsPath,$primaryTemplate);
        $component->init();

        return $component;
    }
}
