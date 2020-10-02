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
use Arikaim\Core\View\Interfaces\ComponentDataInterface;

/**
 * Render html component
 */
class HtmlComponent extends Component implements HtmlComponentInterface
{
    const NOT_VALID_COMPONENT_ERROR = 'Not valid component';
    const ACESS_DENIED_ERROR        = 'Access denied for component';
    const NOT_FOUND_ERROR           = 'Component not found';
    const COMPONENT_ERROR_NAME      = 'components:message.error';
    const CACHE_SAVE_TIME = 2;

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
     * Current template name
     *
     * @var string
     */
    protected $currentTenplate;

    /**
     * Set current template name
     *
     * @param string $name
     * @return void
     */
    public function setCurrentTemplate($name)
    {
        $this->currentTenplate = $name;
    }

    /**
     * Get component data
     *
     * @return ComponentDataInterface
     */
    public function getComponentData()
    {
        return $this->componentData;
    }

    /**
     * Rnder component error mesage
     *
     * @param string $message
     * @return string
     */
    public function getErrorMessage($message)
    {        
        $componentData = $this->createComponentData(Self::COMPONENT_ERROR_NAME,$this->language,true,$this->framework);

        return $this->renderComponentData($componentData,['message' => $message]);
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
        $error = (isset(Self::$errors[$code]) == true) ? Self::$errors[$code] : $code;

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
       
        if (\is_object($component) == false) {
            if (Arrays::getDefaultValue($this->params,'show_error') !== false) {              
                return $this->getErrorMessage($this->getErrorText('NOT_VALID_COMPONENT',$this->name));
            }
            return '';
        }
        if ($component->hasError() == true) {
            $error = $component->getError();
            return $this->getErrorMessage($this->getErrorText($error['code'],$this->name));
        }

        return $component->getHtmlCode();
    }

    /**
     * Render component
     *
     * @return ComponentDataInterface
     */
    public function renderComponent() 
    { 
        return $this->render($this->name,$this->params,$this->language);
    }

    /**
     * Render component data
     *
     * @param ComponentDataInterface $component
     * @param array $params   
     * @return ComponentDataInterface
     */
    public function renderComponentData(ComponentDataInterface $component, $params = [])
    {       
        if ($component->hasError() == true) {         
            $error = $component->getError();
            $redirect = $component->getOption('access/redirect');
            $params['message'] = $this->getErrorText($error['code'],$component->getFullName());
            $component = $this->createComponentData(Self::COMPONENT_ERROR_NAME,$this->language,true,$this->framework);  
            $component->setError($error['code'],$error['params'],$params['message']); 
            $component->setOption('access/redirect',$redirect);            
        }   
        // default params      
        $params['component_url'] = $component->getUrl();
        $params['template_url'] = $component->getTemplateUrl(); 
        $params['component_framework'] = $component->getFramework(); 
     
        $params = Arrays::merge($component->getProperties(),$params);
        $component->setHtmlCode('');  
        if ($component->getOption('render') !== false) {      
            $component = $this->fetch($component,$params);
            // include files
            $this->includeComponentFiles($component->getFiles('js'),'js');
            $this->includeComponentFiles($component->getFiles('css'),'css');
        }        
        $this->view->getEnvironment()->addGlobal('current_component_name',$component->getName());              
        $this->view->getCache()->save('html.component.' . $this->currentTenplate . '.' . $component->getName() . '.' . $this->language,$component,Self::CACHE_SAVE_TIME);
       
        return $component;
    }

    /**
     * Render component html code from template
     *
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @param boolean $withOptions    
     * @return ComponentDataInterface
     */
    public function render($name, $params = [], $language = null, $withOptions = true) 
    {                 
        $component = $this->view->getCache()->fetch('html.component.' . $this->currentTenplate . '.' . $name . '.' . $language);
        $component = (empty($component) == true) ? $this->createComponentData($name,$language,$withOptions,$this->framework) : $component;
      
        return $this->renderComponentData($component,$params);
    }

    /**
     * Get properties
     *    
     * @return array|null
     */
    public function getProperties()
    {             
        return $this->componentData->getProperties();
    }

    /**
     * get component options
     *
     * @return array
     */
    public function getOptions()
    {             
        return $this->componentData->getOptions();
    }
}
