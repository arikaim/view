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
use Arikaim\Core\View\Html\AbstractComponent;
use Arikaim\Core\View\Html\HtmlComponent;

use Arikaim\Core\Collection\Collection;
use Arikaim\Core\View\Html\PageHead;
use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\Text;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Http\Url;

use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;

use Arikaim\Core\View\Html\Traits\Access;
use Arikaim\Core\View\Html\Traits\IncludeTrait;

/**
 * Html page
 */
class Page extends AbstractComponent implements HtmlPageInterface
{    
    use 
        Access,
        IncludeTrait;

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
     * Current language
     *
     * @var string
     */
    private static $currentLanguage;

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
     * Gtml comp ref
     *
     * @var object
     */
    protected $htmlComponent;

    /**
     * Component include files (js)
     *
     * @var array
    */
    protected $componentsFiles = [];

    /**
     * Included compoents
     *
     * @var array
     */
    protected $includedComponents = [];

    /**
     * Constructor
     * 
     * @param ViewInterface $view
     */
    public function __construct(ViewInterface $view, string $defaultLanguage, array $libraryOptions = []) 
    {  
        parent::__construct($view,'pages','page.json');

        $this->componentsFiles = [
            'js'  => [],
            'css' => []
        ];

        $this->libraryOptions = $libraryOptions;       
        $this->head = new PageHead();
        Self::$defaultLanguage = $defaultLanguage;

        $this->htmlComponent = new HtmlComponent($this->view,'components','component.json');
    }

    /**
     * Get included components
     *
     * @return array
     */
    public function getIncludedComponents(): array
    {
        return $this->includedComponents;
    } 

    /**
     * Add include file if not exists
     *
     * @param array $file
     * @param string $key
     * @param string $componentName
     * @param string $type
     * @return void
     */
    public function addIncludeFile(array $file, string $key, string $componentName, string $type = ''): void
    {
        $found = \in_array($file['url'],\array_column($this->componentsFiles[$key],'url'));
        if ($found === false) {
            $file['component_name'] = (empty($file['source_component']) == true) ? $componentName : $file['source_component'];
            $file['component_type'] = (empty($type) == true) ? 'arikaim' : $type;           
            $this->componentsFiles[$key][] = $file;
        }      
    }

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
        $this->setAccessService($this->view->getCurrentExtension()->getAccess());
    }

    /**
     * Create html component
     *
     * @param string $name
     * @param array|null $params
     * @param string|null $language
     * @param string|null $type
     * @return HtmlComponent
     */
    public function renderHtmlComponent(string $name, ?array $params = [], ?string $language = null, ?string $type = null)
    {
        $type = $type ?? ComponentDescriptorInterface::ARIKAIM_COMPONENT_TYPE;
        $cacheItemName = 'html.component.' . $name . '.' . $language;        
        $component = $this->view->getCache()->fetch($cacheItemName);
        if ($component === false) {
            $component = $this->htmlComponent->render($name,$params,$language,$type);
            $this->view->getCache()->save($cacheItemName,$component);
        } else {
            $component = $this->htmlComponent->renderComponentDescriptor($component,$params);          
        }

        $this->includedComponents[] = [
            'name' => $name,
            'type' => $type
        ];

        // include files
        foreach ($component->getFiles('js') as $item) {                                  
            $this->addIncludeFile($item,'js',$name,$type);                                      
        }   

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
     * Process component options
     *
     * @param ComponentDescriptorInterface $component
     * @return ComponentDescriptorInterface
     */
    protected function processOptions(ComponentDescriptorInterface $component)
    {        
        $component = parent::processOptions($component);
        
        return $this->processAccessOption($component);            
    }

    /**
     * Create email component
     *
     * @param string $name
     * @param array|null $params
     * @param string|null $language
     * @return \Arikaim\Core\View\Html\EmailComponent
     */
    public function createEmailComponent(string $name, ?array $params = [], ?string $language = null)
    {       
        $language = $language ?? $this->getLanguage();
       
        $component = new \Arikaim\Core\View\Html\EmailComponent($this->view,$language,'emails','component.json',true);      
       
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
     * Render page
     *
     * @param string $name
     * @param array $params
     * @param string $language    
     * @param string|null $type
     * @return ComponentDescriptorInterface
    */
    public function render(string $name, array $params = [], string $language, ?string $type = null)
    {    
        // fetch from cache
        $currentPath = $params['current_path'] ?? $name;
        $cacheId = 'html.page.component' . $currentPath . '.' . $language;

        $component = $this->view->getCache()->fetch($cacheId);        
        if ($component !== false) {        
          //  return $component;         
        }
        
        $component = $this->createComponentDescriptor($name,$language);
        $component->resolve();  
        $component = $this->processOptions($component);

        // set current page template name      
        $this->setCurrentTemplate($component->getTemplateName());
        // add global variables 
        $this->view->addGlobal('current_language',$language);
        $this->view->addGlobal('page_name',$name);
        // curent route path        
        $this->view->addGlobal('current_url_path',$params['current_path'] ?? '');

        $includes = $this->getPageIncludes($component,$name,$language);   
        
        print_r($includes);
        exit();
      
        $body = $this->getCode($component,$params);

        $params = \array_merge($params,[
            'component_url'       => $component->getUrl(),   
            'template_url'        => $component->getTemplateUrl(),        
            'body'                => $body,
            'primary_template'    => $this->view->getPrimaryTemplate(),
            'library_files'       => $includes['library_files'],
            'template_files'      => $includes['template_files'],
            'page_files'          => $includes['page_files'], 
            'component_files'     => $this->getComponentsFiles(),
            'language'            => $language,  
            'head'                => $this->head->toArray()
        ]);   

        $htmlCode = $this->view->fetch($includes['index'],$params);
        $component->setHtmlCode($htmlCode);
        // save to cache         
        $this->view->getCache()->save($cacheId,$component);
      
        return $component;
    }

    /**
     * Get page includes
     *
     * @param ComponentDescriptorInterface $component  
     * @param string $name  
     * @param string $language
     * @return array
     */
    protected function getPageIncludes(ComponentDescriptorInterface $component, string $name, string $language): array
    {
        $includes = $this->view->getCache()->fetch('html.page.includes.' . $name . '.' . $language);
        if ($includes !== false) {           
            return $includes;
        }
      
        // page include files
        $pageFiles = $this->getPageIncludeFiles($component);
        // template include files        
        $templatefiles = $this->getTemplateIncludeFiles($component->getTemplateName());     
      
        // set page component includ files
        $includes['page_files'] = $component->getFiles();
        // merge template and page include files
        $includes['template_files'] = \array_replace_recursive($templatefiles,$pageFiles);
                
        // get index file
        $includes['index'] = Self::getIndexFile($component,$this->getCurrentTemplate());   

        // save to cache
        $this->view->getCache()->save('html.page.includes.' . $name . '.' . $language,$includes);

        return $includes;      
    }

    /**
     * Get page index file
     *
     * @param ComponentDescriptorInterface $component
     * @return string
     */
    public static function getIndexFile(ComponentDescriptorInterface $component, string $currentTemlate): string
    {        
        switch ($component->getLocation()) {
            case ComponentDescriptor::TEMPLATE_COMPONENT:
                $templateName = $component->getTemplateName();
                break;

            case ComponentDescriptor::PRIMARY_TEMLATE:
                $templateName = $currentTemlate;
                break;

            case ComponentDescriptor::EXTENSION_COMPONENT:
                $templateName = $component->getTemplateName() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
                break;

            default:
                $templateName = $currentTemlate;
                break;
        }
    
        return DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $component->getBasePath() . DIRECTORY_SEPARATOR . 'index.html';            
    }

    /**
     * Get page code
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return string
     */
    public function getCode(ComponentDescriptorInterface $component, array $params = []): string
    {            
        $properties = $component->getProperties();
        $head = $properties['head'] ?? null;
        
        if (\is_array($head) == true) {
            $templateUrl = $params['template_url'] ?? '';
            $this->head->param('template_url',$templateUrl); 
         
            $head = Text::renderMultiple($properties['head'],$this->head->getParams());  

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

        $params = \array_merge_recursive($params,$properties);
     
        return $this->view->fetch($component->getTemplateFile(),$params);       
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
     * Include files
     *
     * @param string $component
     * @param array $pageFiles
     * @param array $templateFiles
     * @return array
     */
    public function getIncludeFiles(string $componentName, array $pageFiles, array $templateFiles): array 
    {       
        $files = $this->view->getCache()->fetch('page.include.files.' . $componentName);
        if ($files !== false) {        
            return $files;
        }

        // from component template   
        foreach($pageFiles as $key => $value) {
            if (isset($templateFiles[$key]) == false) {
                $templateFiles[$key] = (\is_array($value) == true) ? [] : $value;
            } 
            $templateFiles[$key] = (\is_array($value) == true) ? \array_unique(\array_merge($templateFiles[$key],$value)) : $value;                 
        }                    
                                   
        // Save to cache
        $this->view->getCache()->save('page.include.files.' . $componentName,$templateFiles);

        return $templateFiles;
    }

    /**
     * Set default language
     *
     * @param string $language
     * @return void
     */
    public static function setDefaultLanguage(string $language): void
    {
        Self::$defaultLanguage = $language;
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
        Self::$currentLanguage = $language;
    }

    /**
     * Get current language
     *
     * @return string
     */
    public static function getCurrentLanguage(): string
    {
        return Self::$currentLanguage;
    }

    /**
     * Return current page language
     *
     * @return string
     */
    public function getLanguage(): string 
    {  
        return $this->language;
    }

    /**
     * Get page include files
     *
     * @param ComponentDescriptorInterface $component
     * @return array
    */
    public function getPageIncludeFiles(ComponentDescriptorInterface $component): array
    {
        // from cache 
        $include = $this->view->getCache()->fetch('cache.page.include.files.' . $component->getName());
        if ($include !== false) {        
            return $include;
        }

        // from page options
        $include = $component->getOption('include');
        if (empty($options) == false) {            
            $include = $this->resolveIncludeFiles($include,$component->getUrl(),$component->getTemplateName());
            $this->view->getCache()->save('cache.page.include.files.' . $component->getName(),$include);   
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
        foreach ($include['components'] as $item) {               
            $descriptor = $this->createComponentDescriptor($item,'en');
            $files = $descriptor->getIncludeFiles();

            if (empty($files['js']) == false) {
                $include['js'][] = $files['js'];
            }
            if (empty($files['css']) == false) {
                $include['css'][] = $files['css'];    
            }                   
        }    
       
        // UI Libraries                    
        $include['library_files'] = $this->getLibraryIncludeFiles($include['library'],$templateName);    

        return $include;
    }

    /**
     * Return library properties
     *
     * @param string $name
     * @param string|null $version
     * @return Collection
     */
    public static function getLibraryProperties(string $name, ?string $version = null)
    {
        $properties = PackageManager::loadPackageProperties($name,Path::LIBRARY_PATH);
       
        if (empty($version) == true) {
            $properties['files'] = $properties->get('files',[]);
            return $properties;
        }
        $versions = $properties->get('versions',[]);
        $properties['files'] = (isset($versions[$version]) == true) ? $versions[$version]['files'] : $properties->get('files',[]);

        return $properties;
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
            list($libraryName,$libraryVersion,$forceInclude) = Self::parseLibraryName($libraryItem);

            $properties = Self::getLibraryProperties($libraryName,$libraryVersion);            
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
     * Parse library name   (name:version)
     *
     * @param string $libraryName
     * @return array
     */
    public static function parseLibraryName(string $libraryName): array
    {
        $nameTokens = \explode(':',$libraryName);
        $libraryName = $nameTokens[0] ?? $libraryName;
        $libraryVersion = $nameTokens[1] ?? null;
        $libraryOption = $nameTokens[2] ?? $libraryVersion;
        $include = ($libraryOption == 'include');

        return [$libraryName,$libraryVersion,$include];
    }

    /**
     * Get library details
     *
     * @param string $libraryName
     * @return array
     */
    public function getLibraryDetails(string $libraryName): array
    {
        list($name, $version) = Self::parseLibraryName($libraryName);
        $properties = Self::getLibraryProperties($name,$version);                   
        $files = [];

        foreach($properties->get('files') as $file) {   
            $libraryFile = Path::getLibraryFilePath($libraryName,$file); 
            $fileType = \pathinfo($libraryFile,PATHINFO_EXTENSION);       
            $files[$fileType][] = [
                'url' => (Utils::isValidUrl($file) == true) ? $file : Url::getLibraryFileUrl($name,$file) 
            ];               
        }  

        return [
            'files'       => $files,            
            'library'     => $libraryName,
            'async'       => $properties->get('async',false),
            'crossorigin' => $properties->get('crossorigin',null)
        ];
    }

    /**
     * Render page not found 
     *
     * @param array $data
     * @param string|null $language  
     * @param string|null $templateName        
     * @return ComponentDescriptorInterface
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
     * @return ComponentDescriptorInterface
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
     * @return ComponentDescriptorInterface
     */
    public function renderSystemError(array $error = [], ?string $language = null, ?string $templateName = null)
    {    
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';        
        $language = $language ?? Self::$defaultLanguage;

        return $this->render($templateName . Self::SYSTEM_ERROR_PAGE,$error,$language);      
    }
}
