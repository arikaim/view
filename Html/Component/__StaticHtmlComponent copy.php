<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Html\Component;

use Arikaim\Core\View\Html\AbstractComponent;
use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

use Arikaim\Core\View\Html\Traits\IncludeTrait;

/**
 * Static html component
 */
class StaticHtmlComponent extends AbstractComponent implements HtmlComponentInterface
{
    use 
        IncludeTrait;

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
    }

    /**
     * Render component data
     *
     * @param ComponentDescriptorInterface|null $component
     * @param array $params   
     * @return \Arikaim\Core\View\Interfaces\ComponentDescriptorInterface
     */
    public function renderComponentDescriptor(ComponentDescriptorInterface $component, array $params = [])
    {        
        $component = $this->processOptions($component);

        if ($component->hasError() == true) {         
            return $this->renderComponentError($component,$params);             
        }                     
        // default params           
        $params = $this->resolevDefultParams($component,$params);

        $params = \array_merge_recursive($component->getProperties(),$params);
        $component->setContext($params);
        
        return $component;
    }
}
