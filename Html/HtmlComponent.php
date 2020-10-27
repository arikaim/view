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

use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\View\Html\Component;
use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\View\ComponentDataInterface;

/**
 * Render html component
 */
class HtmlComponent extends Component implements HtmlComponentInterface
{
    const NOT_VALID_COMPONENT_ERROR = 'Not valid component';
    const ACESS_DENIED_ERROR        = 'Access denied for component';
    const NOT_FOUND_ERROR           = 'Component not found';
    const COMPONENT_ERROR_NAME      = 'components:message.error';
 
    /**
     * Errors messages
     *
     * @var array
     */
    private static $errors = [
        'NOT_VALID_COMPONENT'          => Self::NOT_VALID_COMPONENT_ERROR,
        'TEMPLATE_COMPONENT_NOT_FOUND' => Self::NOT_FOUND_ERROR,
        'ACCESS_DENIED'                => Self::ACESS_DENIED_ERROR
    ];

    /**
     * Get component data
     *
     * @return ComponentDescriptorInterface
     */
    public function getComponentData()
    {
        return $this->componentDescriptor;
    }

    /**
     * Rnder component error mesage
     *
     * @param string $message
     * @return string
     */
    public function getErrorMessage($message)
    {        
        $componentDescriptor = $this->createComponentDescriptor(Self::COMPONENT_ERROR_NAME,$this->language,true,$this->framework);

        return $this->renderComponentDescriptor($componentDescriptor,['message' => $message]);
    }

    /**
     * Get error message
     *
     * @param string $code
     * @param string $name
     * @return string
     */
    public function getErrorText($code, $name = '')
    {
        $error = Self::$errors[$code] ?? $code;

        return $error . ' ' . $name;
    } 

    /**
     * Load component from template
     *    
     * @return string
     */
    public function load()
    {      
        $component = $this->render($this->name,$this->params,$this->language,true);
       
        if ($component->hasError() == true) {
            $error = $component->getError();
            return $this->getErrorMessage($this->getErrorText($error['code'],$this->name));
        }

        return $component->getHtmlCode();
    }

    /**
     * Render component
     *
     * @param bool $withOptions
     * @return ComponentDescriptorInterface
     */
    public function renderComponent($withOptions = true) 
    { 
        return $this->render($this->name,$this->params,$this->language,$withOptions);
    }

    /**
     * Render component data
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params   
     * @return ComponentDescriptorInterface
     */
    public function renderComponentDescriptor(ComponentDescriptorInterface $component, $params = [])
    {       
        if ($component->hasError() == true) {         
            $error = $component->getError();
            $redirect = $component->getOption('access/redirect');
            $params['message'] = $this->getErrorText($error['code'],$component->getFullName());
            $component = $this->createComponentDescriptor(Self::COMPONENT_ERROR_NAME,$this->language,true,$this->framework);  
            $component->setError($error['code'],$error['params'],$params['message']); 
            $component->setOption('access/redirect',$redirect);            
        }   
        // default params      
        $defaultParams = [
            'component_url'       => $component->getUrl(),
            'template_url'        => $component->getTemplateUrl(),
            'component_framework' => $component->getFramework(),
            'current_language'    => $component->getLanguage()
        ]; 
        $params = $params ?? [];
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

        $params = Arrays::merge($component->getProperties(),$params);
        
        $component->setHtmlCode('');  
        if ($component->getOption('render') !== false) {      
            $component = $this->fetch($component,$params);
            // include files
            $this->includeComponentFiles($component->getFiles('js'));          
        }  
        // add global vars      
        $this->view->getEnvironment()->addGlobal('current_component_name',$component->getName());        
        $this->view->getEnvironment()->addGlobal('current_language',$component->getLanguage());
        // curent route path        
        $this->view->getEnvironment()->addGlobal('current_url_path',$params['current_path'] ?? '');

        $this->view->getCache()->save('html.component.' . $this->currentTenplate . '.' . $component->getName() . '.' . $this->language,$component,Self::$cacheSaveTime);
       
        return $component;
    }

    /**
     * Render component html code from template
     *
     * @param string $name
     * @param array $params
     * @param string $language
     * @param boolean $withOptions    
     * @return ComponentDescriptorInterface
     */
    public function render($name, $params = [], $language, $withOptions = true) 
    {                 
        $component = $this->view->getCache()->fetch('html.component.' . $this->currentTenplate . '.' . $name . '.' . $language);
        $component = (empty($component) == true) ? $this->createComponentDescriptor($name,$language,$withOptions,$this->framework) : $component;
      
        return $this->renderComponentDescriptor($component,$params);
    }

    /**
     * Get properties
     *    
     * @return array
     */
    public function getProperties()
    {             
        return $this->componentDescriptor->getProperties();
    }

    /**
     * get component options
     *
     * @return array
     */
    public function getOptions()
    {             
        return $this->componentDescriptor->getOptions();
    }
}
