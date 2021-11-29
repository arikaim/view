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
use Arikaim\Core\Http\Url;

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
     * Email css inliner
     *
     * @var EmailCompilerInterface|null
     */
    protected $cssInliner = null;

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
    public function __construct(ViewInterface $view, string $defaultLanguage, ?string $emailComplierClass = null) 
    {
        parent::__construct(
            '',
            'emails',
            'en',
            $view->getViewPath(),
            $view->getExtensionsPath(),
            $view->getPrimaryTemplate(),
            ComponentInterface::EMAIL_COMPONENT_TYPE
        );

        $this->defaultLanguage = $defaultLanguage;
        $this->view = $view;   
        
        if (empty($emailComplierClass) == false) {
            $this->cssInliner = (\class_exists($emailComplierClass) == true) ? new $emailComplierClass() : null; 
            if (($this->cssInliner instanceof EmailCompilerInterface) == false) {
                $this->cssInliner = null;
            }     
        }
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
     * Get css email library name
     *
     * @return string
     */
    public function getLibraryName(): string
    {
        return $this->getOption('library','');
    }

    /**
     * Get inline css option
     *
     * @return boolean
     */
    public function inlineCssOption(): bool
    {
        return (bool)$this->getOption('inlineCss',false);
    }

    /**
     * Get css inliner class
     *
     * @return string|null
     */
    public function getCssInlinerClass(): ?string
    {
        return (\is_object($this->cssInliner) == true) ? \get_class($this->cssInliner) : null;
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
        // set current email component url
        $this->context['component_url'] = DOMAIN . $this->url;

        $library = $this->getLibraryName();      
        $code = $this->view->fetch($this->getTemplateFile(),$this->getContext());

        if (Utils::hasHtml($code) == true) {
            // Email is html  
            $file = $this->getComponentFile('css');
            $componentCss = ($file !== false) ? File::read($this->getFullPath() . $file) : '';
            $libraryCss = $this->readLibraryCode($library);

            if ($this->inlineCssOption() != true) {
                $params['component_css'] = $componentCss;             
                $params['library_css'] = $libraryCss;
            }  
          
            $params['body'] = $code;
         
            $indexFile = $this->getIndexFile($this->primaryTemplate);
            $code = $this->view->fetch($indexFile,$params); 

            if ($this->inlineCssOption() == true) {
                $code = $this->inlineCss($code,$libraryCss . $componentCss);
                $params['body'] = $code;
            }                   
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
     * @param string $cssCode
     * @return string
     */
    public function inlineCss(string $code, string $cssCode): string
    {
        if (\is_object($this->cssInliner) == false || empty($cssCode) == true) {
            return $code;
        }

        return $this->cssInliner->compile($code,$cssCode);       
    } 

    /**
     * Read UI library css code
     *
     * @param string $name
     * @return string
     */
    public function readLibraryCode(string $name): string
    {
        if (empty($name) == true) {
            return '';
        }
        list($libraryName,$libraryVersion) = $this->parseLibraryName($name);
        $properties = $this->getLibraryProperties($libraryName,$libraryVersion); 
        $content = '';

        foreach($properties->get('files') as $file) {
            $libraryFile = Path::getLibraryFilePath($libraryName,$file);
            $content .= File::read($libraryFile);
        }

        return \trim($content);
    }    

    /**
     * Set email css inliner
     *
     * @param EmailCompilerInterface|null $inliner
     * @return void
     */
    public function setCssInliner(?EmailCompilerInterface $inliner): void
    {
        $this->cssInliner = $inliner;       
    }
}
