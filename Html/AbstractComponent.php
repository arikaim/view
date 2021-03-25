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

use Arikaim\Core\View\Html\ComponentDescriptor;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;

/**
 *  Base html component
 */
abstract class AbstractComponent   
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
     * Service ref
     *
     * @var mixed
     */
    protected $service;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;
    
    /**
     * Options file name
     *
     * @var string
     */
    protected $optionsFile;

    /**
     * View path
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Extensions path
     *
     * @var string
     */
    protected $extensionsPath;

    /**
     * Primary template
     *
     * @var string
     */
    protected $primaryTemplate;

    /**
     * Constructor
     *
     * @param mixed $service Service eobj used in comp init
     * @param string|null $language
     * @param string $basePath
     * @param string|null $optionsFile
     */
    public function __construct(
        $service, 
        string $viewPath,
        string $extensionsPath,
        string $primaryTemplate,
        string $type, 
        string $basePath = 'components', 
        ?string $optionsFile = null
    )
    {
        $this->service = $service;
        $this->viewPath = $viewPath;
        $this->extensionsPath = $extensionsPath;
        $this->primaryTemplate = $primaryTemplate;
        $this->basePath = $basePath;
        $this->optionsFile = $optionsFile ?? 'component.json';  
        $this->type = $type;

        $this->init();
    }

    /**
     * Init component
     *
     * @return void
     */
    public abstract function init(): void; 

    /**
     * Render component data
     *
     * @param ComponentDescriptorInterface|null $component
     * @param array $params   
     * @return \Arikaim\Core\View\Interfaces\ComponentDescriptorInterface
     */
    public function renderComponentDescriptor(ComponentDescriptorInterface $component, array $params = [])
    { 
        return $component;
    }
   
    /**
     * Get error message
     *
     * @param string $code
     * @param string $name
     * @return string
     */
    public function getErrorText(string $code, string $name = ''): string
    {
        $error = Self::$errors[$code] ?? $code;

        return $error . ' ' . $name;
    } 

    /**
     * Create component data obj
     *
     * @param string $name
     * @param string $language   
     * @return ComponentDescriptorInterface
     */
    public function createComponentDescriptor(string $name, string $language)
    {  
        return new ComponentDescriptor(
            $name,
            $this->basePath,
            $language,
            $this->optionsFile,
            $this->viewPath,
            $this->extensionsPath,
            $this->primaryTemplate,          
            $this->type
        );  
    }

    /**
     * Process component options
     *
     * @param ComponentDescriptorInterface $component
     * @return ComponentDescriptorInterface
     */
    protected function processOptions(ComponentDescriptorInterface $component)
    {        
        // component type option
        $componentType = $component->getOption('component-type');
        if (empty($componentType) == false) {
            $component->setComponentType($componentType);
        }
    
        return $component;
    }

    /**
     * Render component error
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return void
     */
    protected function renderComponentError(ComponentDescriptorInterface $component, array $params = [])
    {
        $error = $component->getError();
        $access = $component->getOption('access');
        $language = $component->getLanguage();
        $redirect = (empty($access) == false) ? $access['redirect'] ?? '' : '';
        $params['message'] = $this->getErrorText($error['code'],$component->getFullName());
        $component = $this->createComponentDescriptor(Self::COMPONENT_ERROR_NAME,$language,'arikaim');    
        $component->resolve();           
        $component->setOption('access/redirect',$redirect);    
        $component->setError($error['code']);  
        
        // default params           
        $component->setContext($this->resolevDefultParams($component,$params));

        return $component;
    } 

    /**
     * Resolve default prams
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return array
     */
    protected function resolevDefultParams(ComponentDescriptorInterface $component, array $params): array
    {
        // default params           
        return \array_merge($params,[
            'component_url'    => $component->getUrl(),
            'template_url'     => $component->getTemplateUrl(),
            'current_language' => $component->getLanguage()
        ]);
    }

    /**
     * Render component html code from template
     *
     * @param string $name
     * @param array $params
     * @param string $language    
     * @return \Arikaim\Core\View\Interfaces\ComponentDescriptorInterface
     */
    public function render(string $name, array $params = [], string $language) 
    {          
        $component = $this->createComponentDescriptor($name,$language);
        $component->resolve();  

        return $this->renderComponentDescriptor($component,$params);
    }
}
