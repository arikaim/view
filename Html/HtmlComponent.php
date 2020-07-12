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
use Arikaim\Core\View\Html\ComponentData;
use Arikaim\Core\Interfaces\View\HtmlComponentInterface;

/**
 * Render html component
 */
class HtmlComponent extends Component implements HtmlComponentInterface
{
    /**
     * Rnder component error mesage
     *
     * @param string $message
     * @return string
     */
    public function getErrorMessage($message)
    {        
        $componentData = $this->createComponentData('components:message.error');

        return $this->renderComponentData($componentData,['message' => $message]);
    }

    /**
     * Load component from template
     *
     * @param boolean $useCache
     * @return string
     */
    public function load($useCache = true)
    {      
        $component = $this->render($this->name,$this->params,$this->language,true,$useCache);
        if ($component == null) {
            if (Arrays::getDefaultValue($this->params,'show_error') !== false) {              
                return $this->getErrorMessage('Not valid component name ' .  $this->name);
            }
            return '';
        }
        if ($component->hasError() == true) {
            return $this->getErrorMessage($component->getError());
        }

        return $component->getHtmlCode();
    }

    /**
     * Render component
     *
     * @return ComponentData
     */
    public function renderComponent() 
    { 
        return $this->render($this->name,$this->params,$this->language);
    }

    /**
     * Render component data
     *
     * @param ComponentData $component
     * @param array $params
     * @return ComponentData
     */
    public function renderComponentData($component,$params = [])
    {
        if (is_object($component) == false) {
            return null;               
        }
        if ($component->hasError() == true) {
            return $component;
        }
        
        // default params      
        $params['component_url'] = $component->getUrl();
        $params['template_url'] = $component->getTemplateUrl(); 
        
        $params = Arrays::merge($component->getProperties(),$params);
        $component->setHtmlCode("");  
        if ($component->getOption('render') !== false) {      
            $component = $this->fetch($component,$params);
            // include files
            $this->includeComponentFiles($component->getFiles('js'),'js');
            $this->includeComponentFiles($component->getFiles('css'),'css');
        }        

        $this->view->getEnvironment()->addGlobal('current_component_name',$component->getName());
        // save to cache         
        $this->view->getCache()->save("html.component." . $component->getFullName() . "." . $component->getLanguage(),$component,1);
      
        return $component;
    }

    /**
     * Render component html code from template
     *
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @param boolean $withOptions
     * @param boolean $useCache
     * @return ComponentData
     */
    public function render($name, $params = [], $language = null, $withOptions = true, $useCache = true) 
    {    
        if ($useCache == true) {        
            $component = $this->view->getCache()->fetch("html.component." . $name . ".$language");
        }
       
        $component = (empty($component) == true) ? $this->createComponentData($name,$language,$withOptions) : $component;

        return $this->renderComponentData($component,$params);
    }

    /**
     * Get properties
     *    
     * @return Collection|null
     */
    public function getProperties()
    {             
        return $this->componentData->loadProperties();
    }
}
