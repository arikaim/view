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
use Arikaim\Core\Interfaces\View\ViewInterface;

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
     * Twig view
     *
     * @var ViewInterface
     */
    public $view;

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
     * Options file name
     *
     * @var string
     */
    protected $optionsFile;

    /**
     * Constructor
     *
     * @param ViewInterface $view
     * @param string|null $language
     * @param string $basePath
     * @param string|null $optionsFile
     */
    public function __construct(ViewInterface $view, string $basePath = 'components', ?string $optionsFile = null)
    {
        $this->view = $view;
        $this->basePath = $basePath;
        $this->optionsFile = $optionsFile ?? 'component.json';  
        
        $this->init();
    }

    /**
     * Init component
     *
     * @return void
     */
    public abstract function init(): void; 
   
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
     * @param string $type
     * @return ComponentDescriptorInterface
     */
    protected function createComponentDescriptor(string $name, string $language, ?string $type = null)
    {  
        return new ComponentDescriptor(
            $name,
            $this->basePath,
            $language,
            $this->optionsFile,
            $this->view->getViewPath(),
            $this->view->getExtensionsPath(),
            $this->view->getPrimaryTemplate(),          
            $type ?? ComponentDescriptorInterface::ARIKAIM_COMPONENT_TYPE
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
}
