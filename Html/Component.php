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
    const NOT_VALID_COMPONENT_ERROR = 'Not valid component';
    const ACESS_DENIED_ERROR        = 'Access denied for component';
    const NOT_FOUND_ERROR           = 'Component not found';
    const COMPONENT_ERROR_NAME      = 'components:message.error';

    /**
     * Errors messages
     *
     * @var array
     */
    protected static $errors = [
        'NOT_VALID_COMPONENT'          => Self::NOT_VALID_COMPONENT_ERROR,
        'TEMPLATE_COMPONENT_NOT_FOUND' => Self::NOT_FOUND_ERROR,
        'ACCESS_DENIED'                => Self::ACESS_DENIED_ERROR
    ];

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
     * Component name
     *
     * @var string|null
     */
    protected $name;

    /**
     * Component type
     *
     * @var string
     */
    protected $componentType;

    /**
     * Constructor
     *
     * @param ViewInterface $view
     * @param string|null $name
     * @param array $params
     * @param string|null $language
     * @param string $basePath
     * @param string|null $optionsFile
     * @param boolean $withOptions
     * @param string $type
     */
    public function __construct(
        ViewInterface $view,
        ?string $name,
        ?array $params = [],
        ?string $language = null,
        string $basePath = 'components',
        ?string $optionsFile = null,
        bool $withOptions = true,
        string $type = ComponentDescriptorInterface::ARIKAIM_COMPONENT_TYPE
    )
    {
        $this->view = $view;
        $this->basePath = $basePath;
        $this->withOptions = $withOptions;
        $this->optionsFile = $optionsFile ?? 'component.json';
        $this->language = $language ?? 'en';
        $this->params = $params ?? [];
        $this->name = $name;
        $this->componentType = $type;

        Self::$cacheSaveTime = \defined('CACHE_SAVE_TIME') ? \constant('CACHE_SAVE_TIME') : Self::$cacheSaveTime;

        if (empty($name) == false) {
            $this->componentDescriptor = $this->createComponentDescriptor($name,$language,$withOptions,$type); 
        }
    }

    /**
     * Set current template name
     *
     * @param string $name
     * @return void
     */
    public function setCurrentTemplate(string $name): void
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
     * @param string $type
     * @return ComponentDescriptorInterface
     */
    protected function createComponentDescriptor(
        string $name, 
        ?string $language = null, 
        bool $withOptions = true, 
        string $type = ComponentDescriptorInterface::ARIKAIM_COMPONENT_TYPE
    )
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
            $primaryTemplate,
            $type
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
    public function fetch(ComponentDescriptorInterface $component, array $params = [])
    {      
        if ($component->hasContent() == false) {
            return $component;
        }
        $templateFle = $component->getTemplateFile();

        $code = $this->view->getEnvironment()->render($templateFle,$params);
        $component->setHtmlCode($code);    

        return $component;
    }

    /**
     * Check auth and permissions access
     *
     * @param ComponentDescriptorInterface $component       
     * @return boolean
     */
    public function checkAuthOption(ComponentDescriptorInterface $component): bool
    {
        $auth = $component->getOption('access/auth',null);   
        if ((\strtolower($auth) == 'none') || (empty($auth) == true)) {
            return true;
        }

        // add auth provider
        $provider = $this->view->getCurrentExtension()->getAccess()->requireProvider($auth);
        if (\is_object($provider) == false) {
            return false;
        }

        return $this->view->getCurrentExtension()->getAccess()->isLogged();       
    }

    /**
     * Check auth and permissions access
     *
     * @param ComponentDescriptorInterface $component   
     * @return boolean
     */
    public function checkPermissionOption(ComponentDescriptorInterface $component): bool
    {
        $permission = $component->getOption('access/permission',null);
        if ((\strtolower($permission) == 'none') || (empty($permission) == true)) {
            return true;
        }
        
        return $this->view->getCurrentExtension()->getAccess()->hasAccess($permission);      
    }

    /**
     * Procss component options
     *
     * @param ComponentDescriptorInterface $component
     * @return ComponentDescriptorInterface
     */
    public function processOptions(ComponentDescriptorInterface $component)
    {         
        // check access 
        if ($this->checkAuthOption($component) === false || $this->checkPermissionOption($component) === false) {
            $component->setError('ACCESS_DENIED',['name' => $component->getFullName()]);             
            return $component;
        }
        // component type option
        $componentType = $component->getOption('component-type',null);
        if (empty($componentType) == false) {
            $component->setComponentType($componentType);
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
    protected function applyIncludeOption(ComponentDescriptorInterface $component, string $key, string $fileType)
    { 
        $option = $component->getOption($key);   
        if (empty($option) == true) {
            return $component;
        }

        $option = (\is_array($option) == false) ? [$option] : $option;            
        // include component files
        foreach ($option as $item) {                                       
            $files = $this->resolveIncludeFile($item,$fileType);
            $component->addFiles($files,$fileType,$item);
        }           

        return $component;
    }

    /**
     * Resolve include file
     *
     * @param string $includeFile  Component or Url 
     * @param string|null $fileType
     * @return array
     */
    protected function resolveIncludeFile(string $includeFile, ?string $fileType): array
    {
        if (Utils::isValidUrl($includeFile) == true) {             
            $tokens = \explode('|',$includeFile);
            $url = $tokens[0];
            $tokens[0] = 'external';
            $params = (isset($tokens[1]) == true) ? $tokens : [];                           
            $files = [
                [
                    'url'               => $url,
                    'params'            => $params,
                    'source_component'  => 'url'
                ]
            ];       
        } else {          
            // get from component
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
    public function getComponentFiles(string $name, ?string $fileType = null): array
    {        
        $files = $this->view->getCache()->fetch('component.files.' . $name);
        if (empty($files) == false) {
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
    public function isValid(): bool
    {
        return $this->componentDescriptor->isValid();
    }

    /**
     * Return true if component have content
     *
     * @return boolean
     */
    public function hasContent(): bool
    {
        return $this->componentDescriptor->hasContent();
    }
    
    /**
     * Get component options
     *
     * @param string $name   
     * @return array
     */
    public function getOptions(): array
    {       
        return $this->componentDescriptor->getOptions();
    }

    /**
     * Inlcude componnent files
     *
     * @param array $files
     * @param string $componentName
     * @param string $type
     * @return void
     */
    public function includeComponentFiles(array $files, string $componentName, string $type): void
    {
        foreach ($files as $item) {          
            if (empty($item['url']) == false) {                   
                $this->view->addIncludeFile($item,'js',$componentName,$type);
            }                              
        }   
    }

    /**
     * Return true if component name is full name
     *
     * @param string $name
     * @return boolean
     */
    public static function isFullName(string $name): bool
    {
        return (\stripos($name,':') !== false || \stripos($name,'>') !== false);       
    }
}
