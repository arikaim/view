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
use Arikaim\Core\View\Html\ComponentDescriptor;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;

/**
 *  Base html component
 */
class Component   
{
    /**
     * Cache save time
     *
     * @var integer
     */
    public static $cacheSaveTime = 4;
 
    /**
     * Twig view
     *
     * @var ViewInterface
     */
    public $view;

    /**
     * Cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Component params
     *
     * @var array
     */
    protected $params;

    /**
     * Language
     *
     * @var string
     */
    protected $language;
    
    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * Component name
     *
     * @var string
     */
    protected $name;

    /**
     * Component data
     *
     * @var ComponentDescriptorInterface
     */
    protected $componentDescriptor;

    /**
     * Options file name
     *
     * @var string
     */
    protected $optionsFile;

    /**
     * Current template name
     *
     * @var string
     */
    protected $currentTenplate;

    /**
     * Constructor
     *
     * @param ViewInterface $view
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @param string $basePath
     * @param string|null $optionsFile
     * @param boolean $withOptions
     */
    public function __construct(
        ViewInterface $view,
        $name,
        $params = [],
        $language = null,
        $basePath = 'components',
        $optionsFile = null,
        $withOptions = true)
    {
        $this->view = $view;
        $this->basePath = $basePath;
        $this->withOptions = $withOptions;
        $this->optionsFile = $optionsFile ?? 'component.json';
        $this->name = $name;
        $this->language = $language ?? 'en';
        $this->params = $params;
    
        Self::$cacheSaveTime = \defined('CACHE_SAVE_TIME') ? \constant('CACHE_SAVE_TIME') : Self::$cacheSaveTime;

        if (empty($name) == false) {
            $this->componentDescriptor = $this->createComponentDescriptor($name,$language,$withOptions); 
        }
    }

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
     * Get view ref
     *
     * @return ViewInterface
     */
    public function getVeiw()
    {
        return $this->view;
    }

    /**
     * Create component data obj
     *
     * @param string $name
     * @param string|null $language
     * @param boolean $withOptions
     * @return ComponentDescriptorInterface
     */
    protected function createComponentDescriptor($name, $language = null, $withOptions = true)
    {
        $language = $language ?? $this->language;
        $primaryTemplate = $this->view->getPrimaryTemplate();
 
        $descriptor = new ComponentDescriptor(
            $name,
            $this->basePath,
            $language,
            $this->optionsFile,
            $this->view->getViewPath(),
            $this->view->getExtensionsPath(),
            $primaryTemplate
        );
        if ($descriptor->isValid() == false) {           
            $descriptor->setError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $name]);             
        }
        $descriptor = ($withOptions == true) ? $this->processOptions($descriptor) : $descriptor;  
        
        return $descriptor;
    }

    /**
     * Fetch component
     *   
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return ComponentDescriptorInterface
     */
    public function fetch(ComponentDescriptorInterface $component, $params = [])
    {      
        if ($component->hasContent() == false) {
            return $component;
        }
        $templateFle = $component->getTemplateFile();

        $this->view->getEnvironment()->loadTemplate($templateFle);
        $code = $this->view->getEnvironment()->render($templateFle,$params);
        $component->setHtmlCode($code);    

        return $component;
    }

    /**
     * Check auth and permissions access
     *
     * @param ComponentDescriptorInterface $component       
     * @return boolean|null
     */
    public function checkAuthOption(ComponentDescriptorInterface $component)
    {
        $auth = $component->getOption('access/auth',null);   
        if (\strtolower($auth) == 'none') {
            return true;
        }

        return (empty($auth) == false) ? $this->view->getCurrentExtension()->getAccess()->isLogged() : null;       
    }

    /**
     * Check auth and permissions access
     *
     * @param ComponentDescriptorInterface $component   
     * @return boolean|null
     */
    public function checkPermissionOption(ComponentDescriptorInterface $component)
    {
        $permission = $component->getOption('access/permission',null);
        if (\strtolower($permission) == 'none') {
            return true;
        }
        
        return (empty($permission) == false) ? $this->view->getCurrentExtension()->hasAccess($permission) : null;      
    }

    /**
     * Procss component options
     *
     * @param ComponentDescriptorInterface $component
     * @return ComponentDescriptorInterface
     */
    public function processOptions(ComponentDescriptorInterface $component)
    {         
        // check auth access 
        $authAccess = $this->checkAuthOption($component);

        if ($authAccess === null) {
            // check root component
            $rootComponent = $component->createComponent($component->getRootName());           
            $authAccess = (\is_object($rootComponent) == true) ? $this->checkAuthOption($rootComponent) : true;               
        }
        if ($authAccess === false) {
            $component->setError('ACCESS_DENIED',['name' => $component->getFullName()]);             
            return $component;
        }

        // check permissions
        $permissionsAccess = $this->checkPermissionOption($component);
        if ($permissionsAccess == null) {
            // check root component
            $rootComponent = $component->createComponent($component->getRootName());
            $permissionsAccess = (\is_object($rootComponent) == true) ? $this->checkPermissionOption($rootComponent) : true;
        }
        if ($permissionsAccess === false) {
            $component->setError('ACCESS_DENIED',['name' => $component->getFullName()]);  
            return $component;
        }
        
        return $this->applyIncludeOption($component,'include/js','js');      
    }

    /**
     * Apply component include option
     *
     * @param ComponentDescriptorInterface $component
     * @param string $key
     * @param string $fileType
     * @return ComponentDescriptorInterface
     */
    protected function applyIncludeOption(ComponentDescriptorInterface $component, $key, $fileType)
    { 
        $option = $component->getOption($key);   
       
        if (empty($option) == false) {
            $option = (\is_array($option) == false) ? [$option] : $option;            
            // include component files
            foreach ($option as $item) {                                       
                $files = $this->resolveIncludeFile($item,$fileType);
                $component->addFiles($files,$fileType);
            }           
        }
        
        return $component;
    }

    /**
     * Resolve include file
     *
     * @param string $includeFile
     * @param string $fileType
     * @return array
     */
    protected function resolveIncludeFile($includeFile, $fileType)
    {
        if (Utils::isValidUrl($includeFile) == true) {             
            $tokens = \explode('|',$includeFile);
            $url = $tokens[0];
            $tokens[0] = 'external';
            $params = (isset($tokens[1]) == true) ? $tokens : [];                           
            $files = [['url' => $url,'params' => $params]];       
        } else {          
            $files = $this->getComponentFiles($includeFile,$fileType);         
        }

        return $files;
    }

    /**
     * Return component files
     *
     * @param string $name
     * @param string $fileType
     * @return array
     */
    public function getComponentFiles($name, $fileType = null)
    {        
        $files = $this->view->getCache()->fetch('component.files.' . $name);
        if (\is_array($files) == true) {
            return $files;
        }
        $language = (empty($language) == true) ? $this->language : null;
        $primaryTemplate = $this->view->getPrimaryTemplate();

        $descriptor = new ComponentDescriptor(
            $name,
            'components',
            $language,
            'component.json',
            $this->view->getViewPath(),
            $this->view->getExtensionsPath(),      
            $primaryTemplate
        );

        $files = (\is_object($descriptor) == true) ? $descriptor->getFiles($fileType) : ['js' => [],'css' => []];
        $files['js'] = $files['js'] ?? [];
        $files['css'] = $files['css'] ?? [];

        $this->view->getCache()->save('component.files.' . $name,$files,Self::$cacheSaveTime);

        return $files;
    }

    /**
     * Return true if component is vlaid
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->componentDescriptor->isValid();
    }

    /**
     * Return true if component have content
     *
     * @return boolean
     */
    public function hasContent()
    {
        return $this->componentDescriptor->hasContent();
    }
    
    /**
     * Get component options
     *
     * @param string $name   
     * @return array|null
     */
    public function getOptions()
    {       
        return $this->componentDescriptor->getOptions();
    }

    /**
     * Inlcude componnent files
     *
     * @param array $files
     * @param string $key
     * @return void
     */
    public function includeComponentFiles(array $files)
    {
        foreach ($files as $item) {          
            if (empty($item['url']) == false) {                   
                $this->view->addIncludeFile($item,'js');
            }                              
        }   
    }

    /**
     * Return true if component name is full name
     *
     * @param string $name
     * @return boolean
     */
    public static function isFullName($name)
    {
        return (\stripos($name,':') !== false || \stripos($name,'>') !== false);       
    }
}
