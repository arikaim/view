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
use Arikaim\Core\View\Html\Component;
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

/**
 * Html page
 */
class Page extends Component implements HtmlPageInterface
{    
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
     * Constructor
     * 
     * @param ViewInterface $view
     */
    public function __construct(
        ViewInterface $view,
        $defaultLanguage,
        array $libraryOptions = [],
        $params = [],
        $language = null,
        $basePath = 'pages',
        $withOptions = true
    ) 
    {  
        parent::__construct($view,null,$params,$language,$basePath,'page.json',$withOptions);

        $this->libraryOptions = $libraryOptions;       
        $this->head = new PageHead();

        Self::$defaultLanguage = $defaultLanguage;
    }

    /**
     * Create html component
     *
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @param boolean $withOptions
     * @return HtmlComponent
     */
    public function createHtmlComponent($name, $params = [], $language = null, $withOptions = true)
    {       
        $templateName = $this->getCurrentTemplate();
        $language = $language ?? $this->language;
        
        $component = new HtmlComponent($this->view,$name,$params,$language,'components','component.json',$withOptions);      
        $component->setCurrentTemplate($templateName);

        return $component;
    }

    /**
     * Create email component
     *
     * @param string $name
     * @param array $params
     * @param string|null $language
     * @return \Arikaim\Core\View\Html\EmailComponent
     */
    public function createEmailComponent($name, $params = [], $language = null)
    {       
        $templateName = $this->getCurrentTemplate();
        $language = $language ?? $this->getLanguage();
       
        $component = new \Arikaim\Core\View\Html\EmailComponent($this->view,$name,$params,$language,'emails','component.json',true);      
        $component->setCurrentTemplate($templateName);

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
     * @return ComponentDescriptorInterface
    */
    public function render($name, $params = [], $language)
    {    
        // fetch from cache
        $component = $this->view->getCache()->fetch('html.page.component.' . $name . '.' . $language);
        $component = (empty($component) == true) ? $this->createComponentDescriptor($name,$language) : $component;
           
        // set current page template name      
        $this->setCurrentTemplate($component->getTemplateName());
        // add global variables 
        $this->view->getEnvironment()->addGlobal('current_language',$language);
        $this->view->getEnvironment()->addGlobal('page_name',$name);
        // curent route path        
        $this->view->getEnvironment()->addGlobal('current_url_path',$params['current_path'] ?? '');

        $includes = $this->getPageIncludes($component,$name,$language);        
        $params['template_url'] = $component->getTemplateUrl();
        $body = $this->getCode($component,$params);

        $params = \array_merge($params,[
            'component_url'       => $component->getUrl(),            
            'body'                => $body,
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
        $this->view->getCache()->save('html.page.component.' . $name . '.' . $language,$component,Self::$cacheSaveTime);
      
        return $component;
    }

    /**
     * Get page includes
     *
     * @param ComponentDescriptorInterface $component    
     * @param string $language
     * @return void
     */
    protected function getPageIncludes(ComponentDescriptorInterface $component, $language)
    {
        $includes = $this->view->getCache()->fetch('html.page.includes.' . $component->getName() . '.' . $language);
        if (empty($includes) == false) {
            return $includes;
        }

        $result = [];
        // page include files
        $pageFiles = $this->getPageIncludeFiles($component);
        // template include files
        $templatefiles = $this->getTemplateIncludeFiles($component->getTemplateName());       
        // set page component includ files
        $result['page_files'] = $component->getFiles();
        // merge template and page include files
        $result['template_files'] = $this->getIncludeFiles($component->getName(),$pageFiles,$templatefiles);
           
        // UI Libraries                    
        $result['library_files'] = $this->getLibraryIncludeFiles(
            $result['template_files']['library'],
            $component->getTemplateName()
        );         
        // get index file
        $result['index'] = Self::getIndexFile($component,$this->getCurrentTemplate());   
        // save to cache
        $this->view->getCache()->save('html.page.includes.' . $component->getName() . '.' . $language,$result,Self::$cacheSaveTime * 2);

        return $result;      
    }

    /**
     * Get page index file
     *
     * @param ComponentDescriptorInterface $component
     * @return string
     */
    public static function getIndexFile(ComponentDescriptorInterface $component, $currentTemlate)
    {               
        switch ($component->getType()) {
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
    
        return $templateName . DIRECTORY_SEPARATOR . $component->getBasePath() . DIRECTORY_SEPARATOR . 'index.html';            
    }

    /**
     * Get page code
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params
     * @return string
     */
    public function getCode(ComponentDescriptorInterface $component, $params = [])
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

        $params = \array_merge_recursive($params,(array)$properties);
        $code = $this->view->fetch($component->getTemplateFile(),$params);
      
        return $code;
    }
    
    /**
     * Return true if page exists
     *
     * @param string $pageName
     * @param string|null $language
     * @return boolean
     */
    public function has($pageName, $language = null) 
    {      
        $page = $this->createComponentDescriptor($pageName,$language);

        return $page->isValid();        
    }

    /**
     * Set page head properties
     *
     * @param Collection $head
     * @return void
     */
    public function setHead(Collection $head)
    {
        $this->head = $head;
    }

    /**
     * Get component files
     *
     * @return array
     */
    public function getComponentsFiles()
    {    
        return $this->view->getComponentFiles();       
    }

    /**
     * Get current template name
     *
     * @return string|null
     */
    public function getCurrentTemplate()
    { 
        return $this->currentTenplate ?? $this->view->getPrimaryTemplate();
    }

    /**
     * Get language path
     *
     * @param string $path
     * @param string $language
     * @return string
     */
    public static function getLanguagePath($path, $language)
    {   
        return (\substr($path,-1) == '/') ? $path . $language . '/' : $path . '/' . $language . '/';
    }

    /**
     * Return url link with current language code
     *
     * @param string $path
     * @param boolean $full
     * @param string|null $language
     * @return string
     */
    public static function getUrl($path = '', $full = false, $language = null)
    {       
        $path = (\substr($path,0,1) == '/') ? \substr($path,1) : $path;      
        $url = ($full == true) ? Url::BASE_URL : BASE_PATH;        
        $url = ($url == '/') ? $url : $url . '/'; 
        $url .= $path;      
        if (empty($language) == true) {
            return $url;
        }

        return (Self::$defaultLanguage != $language) ? Self::getLanguagePath($url,$language) : $url;
    }

    /**
     * Get full page url
     *
     * @param string $path
     * @return string
     */
    public static function getFullUrl($path)
    {
        return Self::getUrl($path,true);
    }

    /**
     * Include files
     *
     * @param string $component
     * @return array
     */
    public function getIncludeFiles($componentName, $pageFiles, $templateFiles) 
    {       
        $files = $this->view->getCache()->fetch('page.include.files.' . $componentName);
        if (\is_array($files) == true) {        
            return $files;
        }

        // from component template 
        if (\is_array($pageFiles) == true) {          
            foreach($pageFiles as $key => $value) {
                if (isset($templateFiles[$key]) == false) {
                    $templateFiles[$key] = (\is_array($value) == true) ? [] : $value;
                } 
                $templateFiles[$key] = (\is_array($value) == true) ? \array_unique(\array_merge($templateFiles[$key],$value)) : $value;                 
            }                    
        }
         
        $templateFiles['library'] = $templateFiles['library'] ?? [];       
                         
        // Save to cache
        $this->view->getCache()->save('page.include.files.' . $componentName,$templateFiles,Self::$cacheSaveTime);

        return $templateFiles;
    }

    /**
     * Set default language
     *
     * @param string $language
     * @return void
     */
    public static function setDefaultLanguage($language)
    {
        Self::$defaultLanguage = $language;
    }

    /**
     * Set current language
     *
     * @param string $language Language code
     * @return void
    */
    public function setLanguage($language)
    {
        $this->language = $language;
        Self::$currentLanguage = $language;
    }

    /**
     * Get current language
     *
     * @return string
     */
    public static function getCurrentLanguage()
    {
        return Self::$currentLanguage;
    }

    /**
     * Return current page language
     *
     * @return string
     */
    public function getLanguage() 
    {  
        return $this->language;
    }

    /**
     * Get page include files
     *
     * @param ComponentDescriptorInterface $component
     * @return array
    */
    public function getPageIncludeFiles(ComponentDescriptorInterface $component)
    {
        // from cache 
        $options = $this->view->getCache()->fetch('cache.page.include.files.' . $component->getName());
        if (empty($options) == false) {              
            return $options;
        }

        // from page options
        $options = $component->getOption('include',null);

        $options['template'] = $options['template'] ?? '';
        $options['js'] = $options['js'] ?? [];
        $options['css'] = $options['css'] ?? [];

        if (empty($options) == false) {  
            // get include options from page.json file  
            $url = Url::getExtensionViewUrl($component->getTemplateName());
           
            $options['js'] = \array_map(function($value) use($url) {              
                return $url . '/js/' . $value; 
            },$options['js']);
          
            $options['css'] = \array_map(function($value) use($url) {
                return $url . '/css/' . $value;
            },$options['css']);
       
            // include components
            if (empty($options['components']) == false) { 
                foreach ($options['components'] as $item) {     
                    $files = $this->getComponentFiles($item);  
                
                    if (empty($files['js'][0]['url']) == false) {
                        $options['js'][] = $files['js'][0]['url'];
                    }
                    if (empty($files['css'][0]['url']) == false) {
                        $options['css'][] = $files['css'][0]['url']; 
                    }                      
                }    
            }
 
            $this->view->getCache()->save('cache.page.include.files.' . $component->getName(),$options,Self::$cacheSaveTime);           
        }

        return $options;
    }

    /**
     * Get template include files
     *
     * @param string $templateName
     * @return array
     */
    public function getTemplateIncludeFiles($templateName)
    {               
        $options = $this->view->getCache()->fetch('template.include.files.' . $templateName);
        if (\is_array($options) == true) {        
            return $options;
        }
       
        $templateOptions = PackageManager::loadPackageProperties($templateName,Path::TEMPLATES_PATH);
       
        $options = $templateOptions->getByPath('include',[]);
    
        $options['js'] = $options['js'] ?? [];
        $options['css'] = $options['css'] ?? [];
        $options['components'] = $options['components'] ?? [];

        $url = Url::getTemplateUrl($templateName);    
      
        $options['js'] = \array_map(function($value) use($url) {
            return $url . '/js/' . $value; 
        },$options['js']);

        $options['css'] = \array_map(function($value) use($url) {
            return $url . '/css/' . $value;         
        },$options['css']);
      
        // include components
        if (empty($options['components']) == false) { 
            foreach ($options['components'] as $item) {     
                $files = $this->getComponentFiles($item);  

                if (empty($files['js'][0]['url']) == false) {
                    $options['js'][] = $files['js'][0]['url'];
                }
                if (empty($files['css'][0]['url']) == false) {
                    $options['css'][] = $files['css'][0]['url'];    
                }                   
            }    
        }
    
        $this->view->getCache()->save('template.include.files.' . $templateName,$options,Self::$cacheSaveTime);
      
        return $options;
    }

    /**
     * Return library properties
     *
     * @param string|null $version
     * @return Collection
     */
    public static function getLibraryProperties($name, $version = null)
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
            'domian'    => DOMAIN,
            'base_url'  => Url::BASE_URL
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
     * @param string|null $currentFramework
     * @return array
     */
    public function getLibraryIncludeFiles(array $libraryList, $templateName, $currentFramework = null)
    {          
        $files = $this->view->getCache()->fetch('ui.library.files.' . $templateName);        
        if (empty($files) == false) {            
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
                $libraryFile = $this->view->getLibraryPath($libraryName) . $file;
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
        $this->view->getCache()->save('ui.library.files.' . $templateName,$files,Self::$cacheSaveTime); 
                               
        return $files;
    }

    /**
     * Parse library name   (name:version)
     *
     * @param string $libraryName
     * @return array
     */
    public static function parseLibraryName($libraryName)
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
    public function getLibraryDetails($libraryName)
    {
        list($name, $version) = Self::parseLibraryName($libraryName);
        $properties = Self::getLibraryProperties($name,$version);                   
        $files = [];

        foreach($properties->get('files') as $file) {   
            $libraryFile = $this->view->getLibraryPath($libraryName) . $file; 
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
    public function renderPageNotFound(array $data = [], $language = null, $templateName = null)
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
     * @return ComponentDescriptorInterface
     */
    public function renderApplicationError(array $data = [], $language = null, $templateName = null)
    {
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';
        $language = $language ?? $this->language;

        return $this->render($templateName . Self::APPLICATION_ERROR_PAGE,['error' => $data],$language);
    }

    /**
     * Render system error(s)
     *
     * @param array $data
     * @param string|null $language   
     * @param string|null $templateName       
     * @return ComponentDescriptorInterface
     */
    public function renderSystemError(array $data = [], $language = null, $templateName = null)
    {    
        $templateName = $templateName ?? $this->getCurrentTemplate();
        $templateName = ($templateName == Self::SYSTEM_TEMPLATE_NAME) ? $templateName . ':' : $templateName . '>';        
        $language = $language ?? $this->language;

        return $this->render($templateName . Self::SYSTEM_ERROR_PAGE,['error' => $data],$language);      
    }
}
