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
use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Utils\Utils;
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
     * Current template name
     *
     * @var string
     */
    protected $currentTenplate;

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
            ComponentInterface::ARIKAIM_COMPONENT_TYPE
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
        $component = $this->view->renderComponent($name,$params,$language,$type);

        $this->addIncludedComponent($name,$type);
        
        $this->includedComponents = \array_merge($this->includedComponents,$component->getIncludedComponents());
        $this->componentsFiles['js'] = \array_merge($this->componentsFiles['js'],$component->getFiles('js'));
      
        return $component;   
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
        // add global variables 
        $language = $language ?? Self::$defaultLanguage;

        $this->view->addGlobal('current_language',$language);
        $this->view->addGlobal('page_name',$name); 
        $this->view->addGlobal('current_url_path',$params['current_path'] ?? '');

        $this->fullName = $name;
        $this->language = $language;

        $this->init();
        $this->resolve($params);  
     
        $properties = $this->getProperties();

        // set current page template name      
        $this->setCurrentTemplate($this->getTemplateName());
       
        $params['component_url'] = $this->url();
        $params['template_url'] = $this->getTemplateUrl();
        $params['primary_template'] = $this->view->getPrimaryTemplate();

        // page head
        if (\is_array($properties['head']) == true) {
            $this->resolvePageHead($properties['head'],$params['template_url']);
        }
        
        $params = \array_merge_recursive($params,$properties); 
        $body = $this->view->fetch($this->getTemplateFile(),$params);  

        $includes = $this->getPageIncludes($name,$language);   

        $params = \array_merge($params,[              
            'body'             => $body,           
            'library_files'    => $includes['library_files'],
            'template_files'   => $includes['template_files'],
            'page_files'       => $includes['page_files'], 
            'component_files'  => $this->getComponentsFiles(),
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
      
        // page include files
        $pageFiles = $this->getPageIncludeFiles();

        // template include files        
        $templatefiles = $this->getTemplateIncludeFiles($this->getTemplateName());     
      
        // set page component includ files
        $includes['page_files'] = $this->getFiles();
        // merge template and page include files

        $includes['template_files'] = \array_merge_recursive($templatefiles,$pageFiles);

        $includes['library_files'] = $includes['template_files']['library_files']; 
        unset($includes['template_files']['library_files']);

        // get index file
        $includes['index'] = $this->getIndexFile($this->getCurrentTemplate());   

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
        return (empty($this->currentTenplate) == true) ? $this->view->getPrimaryTemplate() : $this->currentTenplate;
    }

    /**
     * Return url link with current language code
     *
     * @param string $path
     * @param boolean $full
     * @param string|null $language
     * @return string
     */
    public static function getUrl(string $path = '', bool $full = false, ?string $language = null): string
    {       
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
            $include = $this->resolveIncludeFiles($include,$this->url,$this->getTemplateName());
            $this->view->getCache()->save('page.include.files.' . $this->getName(),$include);   
        }

        return $include ?? [];
    }

    /**
     * Get template include files
     *
     * @param string $templateName
     * @return array
     */
    public function getTemplateIncludeFiles(string $templateName): array
    {               
        $include = $this->view->getCache()->fetch('template.include.files.' . $templateName);
        if ($include !== false) {        
            return $include;
        }
       
        $templateOptions = PackageManager::loadPackageProperties($templateName,Path::TEMPLATES_PATH);
        $include = $templateOptions->get('include',[]);
     
        $include = $this->resolveIncludeFiles($include,Url::getTemplateUrl($templateName),$templateName);
    
        $this->view->getCache()->save('template.include.files.' . $templateName,$include);
      
        return $include;
    }

    /**
     * Resolve include files
     *
     * @param array $include
     * @param string $url
     * @return array
     */
    protected function resolveIncludeFiles(array $include, string $url, string $templateName): array
    {
        $include['js'] = $include['js'] ?? [];
        $include['css'] = $include['css'] ?? [];
        $include['components'] = $include['components'] ?? [];
        $include['library'] = $include['library'] ?? [];

        $include['js'] = \array_map(function($value) use($url) {
            return $url . '/js/' . $value; 
        },$include['js']);
      
        $include['css'] = \array_map(function($value) use($url) {
            return $url . '/css/' . $value;         
        },$include['css']);
       
        // include components
        foreach ($include['components'] as $componentName) {               
            $component = $this->view->createComponent($componentName,'en','empty');
            $file = $component->getIncludeFile('js');
            if (empty($file) == false) {
                $include['js'][] = $file;
            }                
        }    
       
        if (\count($include['library']) > 0) {
            // UI Libraries                    
            $include['library_files'] = $this->getLibraryIncludeFiles($include['library'],$templateName);  
        }
              
        return $include;
    }

    /**
     * Resolve library params
     *
     * @param Collection $properties
     * @return array
     */
    public function resolveLibraryParams($properties)
    {      
        $params = $properties->get('params',[]);
        $vars = [
            'domian'   => DOMAIN,
            'base_url' => BASE_PATH
        ];

        $libraryParams = $this->libraryOptions[$properties['name']] ?? [];
        $vars = \array_merge($vars,$libraryParams);
            
        return Text::renderMultiple($params,$vars);       
    }

    /**
     * Get include library files
     *
     * @param array $libraryList
     * @param string $templateName
     * @return array
     */
    public function getLibraryIncludeFiles(array $libraryList, string $templateName): array
    {          
        $files = $this->view->getCache()->fetch('ui.library.files.' . $templateName);        
        if ($files !== false) {            
            return $files;
        }
        $files = [];

        foreach($libraryList as $libraryItem) {           
            list($libraryName,$libraryVersion,$forceInclude) = $this->parseLibraryName($libraryItem);

            $properties = $this->getLibraryProperties($libraryName,$libraryVersion);            
            $params = $this->resolveLibraryParams($properties);           
            if ($properties->get('disabled',false) == true) {
                // Library is disabled
                continue;
            }
            $urlParams = ($properties->get('params-type') == 'url') ? '?' . \http_build_query($params) : '';

            foreach($properties->get('files') as $file) {
                $libraryFile = Path::getLibraryFilePath($libraryName,$file);
                $type = \pathinfo($libraryFile,PATHINFO_EXTENSION);
                $item = [
                    'file'        => (Utils::isValidUrl($file) == true) ? $file . $urlParams : Url::getLibraryFileUrl($libraryName,$file) . $urlParams,
                    'type'        => (empty($type) == true) ? 'js' : $type,
                    'params'      => $params,
                    'library'     => $libraryName,
                    'async'       => $properties->get('async',false),
                    'crossorigin' => $properties->get('crossorigin',null)
                ];
                \array_push($files,$item);
            }           
        }
        // Save to cache
        $this->view->getCache()->save('ui.library.files.' . $templateName,$files); 
                               
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
        $language = $language ?? Self::$defaultLanguage;

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
        $language = $language ?? Self::$defaultLanguage;

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
        $language = $language ?? Self::$defaultLanguage;

        return $this->render($templateName . Self::SYSTEM_ERROR_PAGE,$error,$language);      
    }
}
