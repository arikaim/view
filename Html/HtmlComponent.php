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

use Arikaim\Core\View\Html\AbstractComponent;
use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\View\ComponentDataInterface;

use Arikaim\Core\View\Html\Traits\Access;
use Arikaim\Core\View\Html\Traits\IncludeTrait;

/**
 * Render html component
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
        $this->setAccessService($this->view->getCurrentExtension()->getAccess());
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
            $error = $component->getError();
            $access = $component->getOption('access');
            $language = $component->getLanguage();
            $redirect = (empty($access) == false) ? $access['redirect'] ?? '' : '';
            $params['message'] = $this->getErrorText($error['code'],$component->getFullName());
            $component = $this->createComponentDescriptor(Self::COMPONENT_ERROR_NAME,$language,true);    
            $component->resolve();           
            $component->setOption('access/redirect',$redirect);    
            $component->setError($error['code']);                
        }   
       
        // default params      
        $defaultParams = [
            'component_url'    => $component->getUrl(),
            'template_url'     => $component->getTemplateUrl(),
            'current_language' => $component->getLanguage(),
            'current_url_path' => $params['current_path'] ?? ''
        ];       
        $params = \array_merge($params,$defaultParams);

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

        $params = \array_merge($component->getProperties(),$params);
          
        // resolve component type
        if (isset($params['component-type']) == true) {
            $component->setComponentType($params['component-type']);
        }
                
        if ($component->hasContent() == true) {          
            $code = $this->view->fetch($component->getTemplateFile(),$params);
            $component->setHtmlCode($code);                     
        }
         
        return $component;
    }

    /**
     * Render component html code from template
     *
     * @param string $name
     * @param array $params
     * @param string $language
     * @param string|null $type     
     * @return \Arikaim\Core\View\Interfaces\ComponentDescriptorInterface
     */
    public function render(string $name, array $params = [], string $language, ?string $type = null) 
    {          
        $component = $this->createComponentDescriptor($name,$language,$type);
        $component->resolve();  

        return $this->renderComponentDescriptor($component,$params);
    }
}
