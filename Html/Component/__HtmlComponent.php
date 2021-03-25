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
use Arikaim\Core\Interfaces\View\ComponentDataInterface;

use Arikaim\Core\View\Html\Traits\Access;
use Arikaim\Core\View\Html\Traits\IncludeTrait;

/**
 * Html component
 */
class HtmlComponent extends AbstractComponent implements HtmlComponentInterface
{
    use 
        Access,
        IncludeTrait;

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
        //$this->setAccessService($this->service);
    }

    /**
     * Process component options
     *
     * @param ComponentDescriptorInterface $component
     * @return ComponentDescriptorInterface
     */
    protected function processOptions(ComponentDescriptorInterface $component)
    {        
        $component = parent::processOptions($component);
        $component = $this->processAccessOption($component);

        return $this->processIncludeOption($component);      
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

        // check data file
        $dataFile = $component->getDataFile();
        if (empty($dataFile) == false) {
            // include data file
            $componentData = require $dataFile;                       
            if ($componentData instanceof ComponentDataInterface) {                   
                $data = $componentData->getData($params);
                $params = \array_merge($params,$data);
            }          
        }

        $params = \array_merge_recursive($component->getProperties(),$params);              
        $component->setContext($params);
                          
        return $component;
    }
}
