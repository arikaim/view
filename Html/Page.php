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
use Arikaim\Core\Collection\Collection;
use Arikaim\Core\View\Html\PageHead;
use Arikaim\Core\Utils\Text;

use Arikaim\Core\Utils\Path;
use Arikaim\Core\Http\Url;

use Arikaim\Core\View\Html\Component\Traits\IncludeOption;
use Arikaim\Core\View\Html\Component\Traits\Options;
use Arikaim\Core\View\Html\Component\Traits\Properties;
use Arikaim\Core\View\Html\Component\Traits\IndexPage;
use Arikaim\Core\View\Html\Component\Traits\UiLibrary;

use Arikaim\Core\Interfaces\View\ComponentInterface;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;

/**
 * Html page
 */
class Page extends BaseComponent implements HtmlPageInterface
{    
    use 
        Options,
        Properties,   
        IndexPage,
        UiLibrary,
        IncludeOption;

    /**
     *  Control panel template name
     */
    const SYSTEM_TEMPLATE_NAME = 'system';
  
    /**
     *  Error page names
     */
    const PAGE_NOT_FOUND         = 'page-not-found';
    const SYSTEM_ERROR_PAGE      = 'system-error';
    const APPLICATION_ERROR_PAGE = 'application-error';

    /**
     * Default language
     *
     * @var string
     */
    private static $defaultLanguage;

    /**
     * Page head properties
     *
     * @var PageHead
     */
    protected $head;
    
    /**
     * Ui Library options
     *
     * @var array
     */
    protected $libraryOptions;

    /**
     * Component include files (js)
     *
     * @var array
    */
    protected $componentsFiles = [];

    /**
     * View 
     *
     * @var ViewInterface
     */
    protected $view;

    /**
     * Constructor
     * 
     * @param ViewInterface $view
     * @param string $defaultLanguage,
     * @param array $libraryOptions
     */
    public function __construct(ViewInterface $view, string $defaultLanguage, array $libraryOptions = []) 
    {  
        parent::__construct(
            '',
            'pages',
            'en',
            $view->getViewPath(),
            $view->getExtensionsPath(),
            $view->getPrimaryTemplate(),
            ComponentInterface::PAGE_COMPONENT_TYPE
        );

        $this->view = $view; 
        $this->setOptionFile('page.json');

        $this->componentsFiles = [
            'js'  => [],
            'css' => []
        ];

        $this->libraryOptions = $libraryOptions;       
        $this->head = new PageHead();
        Self::$defaultLanguage = $defaultLanguage; 
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
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
        parent::init();
        
        $this->loadProperties();
        $this->loadOptions(); 
        $this->addComponentFile('js');    
        $this->addComponentFile('css');           
        $this->resolveHtmlContent(); 
        // options
        $this->processIncludeOption();      
    }

    /**
     * Render html component
     *
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @param string|null $type
     * @return \Arikaim\Core\Interfaces\View\HtmlComponentInterface
     */
    public function renderHtmlComponent(string $name, array $params = [], ?string $language = null, ?string $type = null)
    {
        $type = $type ?? ComponentInterface::ARIKAIM_COMPONENT_TYPE;
        $language = $language ?? $this->language;
        $params['template_path'] = Path::TEMPLATES_PATH . $this->getCurrentTemplate() . DIRECTORY_SEPARATOR;
        $params['template_url'] = Url::getTemplateUrl($this->getCurrentTemplate(),'/');
    
        $component = $this->view->renderComponent($name,$params,$language,$type);

        if (\count($component->getFiles('js')) > 0) {
            // include    
            if (\in_array($name,\array_column($this->includedComponents,'name')) == false) {
                $this->componentsFiles['js'] = \array_merge($this->componentsFiles['js'],$component->getFiles('js'));              
            }
            $this->addIncludedComponent($name,$type,$component->id);
            $this->includedComponents = \array_merge($this->includedComponents,$component->getIncludedComponents());
        } 
              
        return $component;   
    }

    /**
     * Get head properties
     *
     * @return PageHead
     */
    public function head()
    {
        return $this->head;
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
     * Render page
     *
     * @param string $name
     * @param array $params
     * @param string|null $language      
     * @return ComponentInterface
    */
    public function render(string $name, array $params = [], ?string $language = null)
    {         
        $language = $language ?? $this->language;
        $this->fullName = $name;
        $this->language = $language;

        $this->init();
        $this->resolve($params);  

        // add global variables       
        $this->view->addGlobal('current_url_path',$params['current_path'] ?? '');
        $this->view->addGlobal('template_url',$this->templateUrl . '/');
        $this->view->addGlobal('current_language',$language);
        $this->view->addGlobal('page_component_name',$name);

        // page head
        if (\is_array($this->properties['head'] ?? null) == true) {
            $this->resolvePageHead($this->properties['head'],$this->templateUrl);
        }
        
        $params = \array_merge_recursive($params,$this->properties); 
        $body = $this->view->fetch($this->getTemplateFile(),$params);  

        $includes = $this->getPageIncludes($name,$language);   

        $params = \array_merge($params,[              
            'body'             => $body,           
            'library_files'    => $includes['library_files'] ?? null,
            'template_files'   => $includes['template_files'] ?? null,
            'page_files'       => $includes['page_files'] ?? null, 
            'component_files'  => $this->componentsFiles,
            'head'             => $this->head->toArray()
        ]);   

        $htmlCode = $this->view->fetch($includes['index'],$params);
        $this->setHtmlCode($htmlCode);
     
        return $this;
    }

    /**
     * Get page includes
     *   
     * @param string $name  
     * @param string $language
     * @return array
     */
    protected function getPageIncludes(string $name, string $language): array
    {
        $includes = $this->view->getCache()->fetch('html.page.includes.' . $name . '.' . $language);
        if ($includes !== false) {           
            return $includes;
        }
        $includes = [];
        
        // template include files        
        $templatefiles = $this->getTemplateIncludeFiles();     
        // page include files
        $pageFiles = $this->getPageIncludeFiles();
        // set page component includ files
        $includes['page_files'] = $this->getFiles();

        // merge template and page include files
        $includes['template_files'] = \array_merge_recursive($templatefiles,$pageFiles);
        // library files
        $includes['library_files'] = \array_merge($includes['template_files']['library_files'] ?? [],$pageFiles['library_files'] ?? []); 
        unset($includes['template_files']['library_files']);

        // get index file
        $includes['index'] = $this->getIndexFile($this->templateName);   

        // save to cache
        $this->view->getCache()->save('html.page.includes.' . $name . '.' . $language,$includes);

        return $includes;      
    }

    /**
     * Resolve page head
     *
     * @param array $head
     * @param string $templateUrl
     * @return void
     */
    protected function resolvePageHead(array $head, string $templateUrl): void
    {
        $this->head->param('template_url',$templateUrl); 
        $head = Text::renderMultiple($head,$this->head->getParams());  
        $this->head->applyDefaultMetaTags($head); 
       
        if (isset($head['og']) == true) {
            if (empty($this->head->get('og')) == true) {
                $this->head->set('og',$head['og']);
                $this->head->resolveProperties('og');
            }                
        }
        if (isset($head['twitter']) == true) {
            if (empty($this->head->get('twitter')) == true) {
                $this->head->set('twitter',$head['twitter']);
                $this->head->resolveProperties('twitter');
            }               
        }

        $this->head->applyDefaultItems($head);
    }

    /**
     * Set page head properties
     *
     * @param Collection $head
     * @return void
     */
    public function setHead(Collection $head): void
    {
        $this->head = $head;
    }

    /**
     * Get components include files
     *
     * @return array
     */
    public function getComponentsFiles(): array
    {
        return $this->componentsFiles;
    }

    /**
     * Get current template name
     *
     * @return string
     */
    public function getCurrentTemplate(): string
    { 
        return (empty($this->templateName) == true) ? $this->primaryTemplate : $this->templateName;
    }

    /**
     * Return url link with current language code
     *
     * @param string|null $path
     * @param boolean $full
     * @param string|null $language
     * @return string
     */
    public static function getUrl($path = '', bool $full = false, ?string $language = null): string
    {       
        $path = $path ?? '';
        
        return Url::getUrl($path,!$full,$language,Self::$defaultLanguage);
    }

    /**
     * Get full page url
     *
     * @param string $path
     * @param string|null $language
     * @return string
     */
    public static function getFullUrl(string $path, ?string $language = null): string
    {
        return Self::getUrl($path,false,$language);
    }

    /**
     * Set current language
     *
     * @param string $language Language code
     * @return void
    */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * Get page include files
     *  
     * @return array
    */
    public function getPageIncludeFiles(): array
    {
        $include = $this->view->getCache()->fetch('page.include.files.' . $this->getName());
        if ($include !== false) {        
            return $include;
        }
        // from page options
        $include = $this->getOption('include');          
        if (empty($include) == false) {            
            $include = $this->resolveIncludeFiles($include,$this->templateUrl);
            if (\count($include['library']) > 0) {
                // UI Libraries    
                $include['library_files'] = $this->getLibraryIncludeFiles($include['library'],null);                   
            }

            $this->view->getCache()->save('page.include.files.' . $this->getName(),$include);   
        }

        return $include ?? [];
    }

    /**
     * Get template include files
     *   
     * @return array
     */
    protected function getTemplateIncludeFiles(): array
    {               
        $include = $this->view->getCache()->fetch('template.include.files.' . $this->templateName);
        if ($include !== false) {        
            return $include;
        }
       
        $json = \file_get_contents(Path::TEMPLATES_PATH . $this->templateName . DIRECTORY_SEPARATOR . 'arikaim-package.json');
        $data = \json_decode($json,true);
        $templateOptions = (\is_array($data) == true) ? $data : [];

        $include = $templateOptions['include'] ?? [];
     
        $include = $this->resolveIncludeFiles($include,$this->templateUrl);
        if (\count($include['library']) > 0) {
            // UI Libraries                    
            $include['library_files'] = $this->getLibraryIncludeFiles($include['library'],$this->templateName);  
        }

        $this->view->getCache()->save('template.include.files.' . $this->templateName,$include);
      
        return $include;
    }

    /**
     * Resolve include files
     *
     * @param array $include
     * @param string $url
     * @return array
     */
    protected function resolveIncludeFiles(array $include, string $url): array
    {                    
        $include['library'] = $include['library'] ?? [];
        $include['js'] = \array_map(function($file) use($url) {
            if (Url::isValid($file) == true) {
                return $file;
            }            
            $tokens = \explode(':',$file);           
            if (isset($tokens[1]) == true) {
                $file = $tokens[1];
                $url = Url::getTemplateUrl($tokens[0]);
            } else {
                $file = $tokens[0];
            }
           
            return $url . '/js/' . $file; 
        },$include['js'] ?? []);
      
        $include['css'] = \array_map(function($file) use($url) {
            if (Url::isValid($file) == true) {
                return $file;
            }
            $tokens = \explode(':',$file);           
            if (isset($tokens[1]) == true) {
                $file = $tokens[1];
                $url = Url::getTemplateUrl($tokens[0]);
            } else {
                $file = $tokens[0];
            }
            
            return $url . '/css/' . $file;         
        },$include['css'] ?? []);
       
        // include components
        foreach ($include['components'] ?? [] as $componentName) {               
            $component = $this->view->createComponent($componentName,'en','empty');
            $file = $component->getIncludeFile('js');
            if (empty($file) == false) {
                $include['js'][] = [
                    'url'            => $file,
                    'component_name' => $component->getFullName(),
                    'component_id'   => $component->id,
                    'component_type' => 'js',      
                ];              
            }                
        }    
              
        return $include;
    }

    /**
     * Get include library files
     *
     * @param array $libraryList
     * @param string|null $templateName
     * @return array
     */
    public function getLibraryIncludeFiles(array $libraryList, ?string $templateName): array
    {          
        if (empty($templateName) == false) {
            $files = $this->view->getCache()->fetch('template.library.files.' . $templateName);        
            if ($files !== false) {            
                return $files;
            }
        }
       
        $files = [];
        foreach ($libraryList as $library) {      
            list($libraryName,$libraryVersion,$libraryOption) = $this->parseLibraryName($library);
            $disabled = $this->libraryOptions[$libraryName]['disabled'] ?? false;
            if ($disabled == true) {
                continue;
            }
            
            $libraryFiles = (empty($templateName) == false) ? $this->view->getCache()->fetch('library.files.' . $library) : false;    
            if ($libraryFiles === false) {
                $libraryFiles = $this->getLibraryFiles($libraryName,$libraryVersion,$libraryOption);
                $this->view->getCache()->save('library.files.' . $library,$libraryFiles);  
            } 
        
            $files = \array_merge($files,$libraryFiles);       
        }

        if (empty($templateName) == false) {
            // Save to cache
            $this->view->getCache()->save('template.library.files.' . $templateName,$files); 
        }
                               
        return $files;
    }

    /**
     * Render page not found 
     *
     * @param array $data
     * @param string|null $language  
     * @param string|null $templateName        
     * @return ComponentInterface
    */
    public function renderPageNotFound(array $data = [], ?string $language = null, ?string $templateName = null)
    {
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';
        $language = $language ?? $this->language;

        return $this->render($templateName . Self::PAGE_NOT_FOUND,['error' => $data],$language);
    }

    /**
     * Render application error
     *
     * @param array $data
     * @param string|null $language    
     * @param string|null $templateName       
     * @return ComponentInterface
     */
    public function renderApplicationError(array $data = [], ?string $language = null, ?string $templateName = null)
    {
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';
        $language = $language ?? $this->language;

        return $this->render($templateName . Self::APPLICATION_ERROR_PAGE,['error' => $data],$language);
    }

    /**
     * Render system error(s)
     *
     * @param array $error
     * @param string|null $language   
     * @param string|null $templateName       
     * @return ComponentInterface
     */
    public function renderSystemError(array $error = [], ?string $language = null, ?string $templateName = null)
    {    
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';        
        $language = $language ?? $this->language;

        return $this->render($templateName . Self::SYSTEM_ERROR_PAGE,$error,$language);      
    }
}
