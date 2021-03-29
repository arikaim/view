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

use Arikaim\Core\View\Html\Component\BaseComponent;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\Utils;

use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\Interfaces\View\ComponentInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;
use Arikaim\Core\Interfaces\View\EmailViewInterface;
use Arikaim\Core\Interfaces\EmailCompilerInterface;

use Arikaim\Core\View\Html\Component\Traits\Options;
use Arikaim\Core\View\Html\Component\Traits\Properties;
use Arikaim\Core\View\Html\Component\Traits\IndexPage;
use Arikaim\Core\View\Html\Component\Traits\UiLibrary;

/**
 * Render email component
 */
class EmailView extends BaseComponent implements HtmlComponentInterface, EmailViewInterface
{
    use 
        Options,
        IndexPage,
        UiLibrary,
        Properties;
  
    /**
     * Emeil compilers list
     *
     * @var array
     */
    protected $emailCompilers = [];

    /**
     * Default language
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * Constructor
     *
     * @param ViewInterface $view
     * @param string $defaultLanguage    
     */
    public function __construct(ViewInterface $view, string $defaultLanguage) 
    {
        parent::__construct(
            '',
            'emails',
            'en',
            $view->getViewPath(),
            $view->getExtensionsPath(),
            $view->getPrimaryTemplate(),
            ComponentInterface::ARIKAIM_COMPONENT_TYPE
        );

        $this->defaultLanguage = $defaultLanguage;
        $this->view = $view;         
    }   

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {        
        parent::init();

        $this->loadProperties();
        $this->loadOptions(); 
        $this->addComponentFile('css');           
        $this->resolveHtmlContent(); 
    }

    /**
     * Return true if component is valid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->hasContent();
    }

    /**
     * Render component data
     *     
     * @param array $params   
     * @return bool
     */
    public function resolve(array $params = []): bool
    {        
        if ($this->isValid() == false) {    
            return false;                
        }
      
        $this->mergeContext($this->getProperties());
        $this->mergeContext($params);
        
        return true;
    }

    /**
     * Render email component
     *
     * @param string $name
     * @param array $params
     * @param string|null $language    
     * @return \Arikaim\Core\Interfaces\View\EmailViewInterface|null
    */
    public function render(string $name, array $params = [], ?string $language = null)
    {
        $language = $language ?? $this->defaultLanguage;

        $this->fullName = $name;
        $this->language = $language;

        $this->init();

        if ($this->resolve($params) == false) { 
            return null;
        }

        $library = $this->getOption('library');
      
        $code = $this->view->fetch($this->getTemplateFile(),$this->getContext());
        if (empty($library) == false) {
            $code = $this->compileCssFrameworkCode($code,$library);
        }

        if (Utils::hasHtml($code) == true) {
            // Email is html  
            $file = $this->getComponentFile('css');           
            $params['component_css_file'] = ($file !== false) ? File::read($this->getFullPath() . $file) : null;              
            $params['library_code'] = (empty($library) == false) ? $this->readLibraryCode($library) : [];
            $params['body'] = $code;

            $indexFile = $this->getIndexFile($this->primaryTemplate);
            $code = $this->view->fetch($indexFile,$params);                    
        }

        $this->setHtmlCode($code);   

        return $this;
    }   

    /**
     * Get email subject
     *
     * @return string
     */
    public function getSubject(): string
    {
       return $this->properties['subject'] ?? '';
    }

    /**
     * Compile email code
     *
     * @param string $code
     * @param string|null $library
     * @return string
     */
    public function compileCssFrameworkCode(string $code, ?string $library): string
    {
        $compilerClass = $this->emailCompilers[$library] ?? null;
        if (empty($compilerClass) == true) {
            return $code;
        }

        if (\class_exists($compilerClass) == true) {
            $compiller = new $compilerClass();
            if ($compiller instanceof EmailCompilerInterface) {
                $code = $compiller->compile($code);
            }
        }
        
        return $code;
    } 

    /**
     * Read UI library css code
     *
     * @param string $name
     * @return array
     */
    public function readLibraryCode(string $name): array
    {
        list($libraryName,$libraryVersion,$forceInclude) = $this->parseLibraryName($name);
        $properties = $this->getLibraryProperties($libraryName,$libraryVersion); 
        $content = [];

        foreach($properties->get('files') as $file) {
            $libraryFile = Path::getLibraryFilePath($libraryName,$file);
            $content[] = File::read($libraryFile);
        }

        return $content;
    }    

    /**
     * Set email compilers
     *
     * @param array $compilers
     * @return void
     */
    public function setEmailCompillers(array $compilers): void
    {
        $this->emailCompilers = $compilers;       
    }
}
