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

use Arikaim\Core\View\Html\ComponentData;
use Arikaim\Core\View\Html\Component;
use Arikaim\Core\View\Html\HtmlComponent;

use Arikaim\Core\Collection\Collection;
use Arikaim\Core\View\Html\PageHead;
use Arikaim\Core\Packages\PackageManager;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\Text;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Http\Session;
use Arikaim\Core\Http\Url;

use Arikaim\Core\View\Interfaces\ComponentDataInterface;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;

/**
 * Html page
 */
class Page extends Component implements HtmlPageInterface
{    
    const SYSTEM_TEMPLATE_NAME = 'system';
  
    /**
     * Current language
     *
     * @var string|null
     */
    private static $currentLanguage = null;

    /**
     * Default language
     *
     * @var string|null
     */
    private static $defaultLanguage = null;

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
    public function createHtmlComponent($name, $params = [], $language = null, $withOptions = true, $framework = null)
    {       
        $templateName = $this->getCurrentTemplate();
        $language = $language ?? $this->language;
        $framework = $framework ?? $this->getFramework($templateName);
           
        $component = new HtmlComponent($this->view,$name,$params,$language,'components','component.json',$withOptions,$framework);      
        $component->setCurrentTemplate($templateName);

        return $component;
    }

    /**
     * Create email component
     *
     * @param string $name
     * @param array $params
     * @param string|null $framework
     * @param string|null $language
     * @return \Arikaim\Core\View\Html\EmailComponent
     */
    public function createEmailComponent($name, $params = [], $framework = null, $language = null)
    {       
        $templateName = $this->getCurrentTemplate();
        $language = (empty($language) == true) ? $this->getLanguage() : $language;
       
        $component = new \Arikaim\Core\View\Html\EmailComponent($this->view,$name,$params,$language,'emails','component.json',true,$framework);      
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
     * Load page
     *
     * @param Response $response
     * @param string $name
     * @param array|object $params
     * @param string|null $language
     * @return Response|false
     */
    public function load($response, $name, $params = [], $language = null)
    {
        $html = $this->getHtmlCode($name,$params,$language);
        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Get page html code
     *
     * @param string $name
     * @param array|object $params
     * @param string|null $language
     * @return string
     */
    public function getHtmlCode($name, $params = [], $language = null)
    {       
        $params = (\is_object($params) == true) ? $params->toArray() : $params;          
        $component = $this->render($name,$params,$language);
           
        return $component->getHtmlCode();
    }   

    /**
     * Render page
     *
     * @param string $name
     * @param array $params
     * @param string|null $language    
     * @return ComponentDataInterface
    */
    public function render($name, $params = [], $language = null)
    { 
        Session::set('page.name',$name);
        
        // fetch from cache
        $component = $this->view->getCache()->fetch('html.page.' . $name . '.' . $language);
        
        $component = (empty($component) == true) ? $this->createComponentData($name,$language) : $component;
        $params['component_url'] = $component->getUrl();
        $params['template_url'] = $component->getTemplateUrl(); 
        // set current page template name      
        $this->setCurrentTemplate($component->getTemplateName());
        
        // page include files
        $pageFiles = $this->getPageIncludeFiles($component);
        // template include files
        $templatefiles = $this->getTemplateIncludeFiles($component->getTemplateName());
        // set page component includ files
        $pageComponentFiles = $component->getFiles();
        $this->view->properties()->set('include.page.files',$pageComponentFiles);

        // merge template and page include files
        $includeFiles = $this->getIncludeFiles($component->getName(),$pageFiles,$templatefiles);
        $this->view->properties()->set('include.template.files',$includeFiles);
        $this->setFramework($includeFiles['framework'],$component->getTemplateName());        
        // UI Libraries
        $this->view->properties()->set('ui.included.libraries',$includeFiles['library']);

        // include ui lib files                
        $libraryFiles = $this->getLibraryIncludeFiles($includeFiles['library'],$component->getTemplateName(),$includeFiles['framework']);  
        // set includes library files
        $this->view->properties()->set('ui.library.files',$libraryFiles);  
    

        $body = $this->getCode($component,$params);
        $indexPage = Self::getIndexFile($component,$this->getCurrentTemplate());     
    
        $params = \array_merge($params,[
            'body'            => $body,
            'library_files'   => $libraryFiles,
            'template_files'  => $includeFiles,
            'page_files'      => $pageComponentFiles, 
            'component_files' => $this->getComponentsFiles(),    
            'head'            => $this->head->toArray()
        ]);   

        $component->setHtmlCode($this->view->fetch($indexPage,$params));

        // save to cache         
        $this->view->getCache()->save('html.page.' . $name . '.' . $language,$component,Self::$cacheSaveTime);
      
        return $component;
    }

    /**
     * Get page index file
     *
     * @param ComponentDataInterface $component
     * @return string
     */
    public static function getIndexFile(ComponentDataInterface $component, $currentTemlate)
    {               
        switch ($component->getType()) {
            case ComponentData::TEMPLATE_COMPONENT:
                $templateName = $component->getTemplateName();
                break;

            case ComponentData::PRIMARY_TEMLATE:
                $templateName = $currentTemlate;
                break;

            case ComponentData::EXTENSION_COMPONENT:
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
     * @param ComponentDataInterface $component
     * @param array $params
     * @return string
     */
    public function getCode(ComponentDataInterface $component, $params = [])
    {     
        $framework = $this->getFramework($this->getCurrentTemplate());
        $component->setFramework($framework);
        $properties = $component->getProperties();
        
        if (isset($properties['head']) == true) {
            $templateUrl = (isset($params['template_url']) == true) ? $params['template_url'] : '';
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
       
        return $this->view->fetch($component->getTemplateFile($framework),$params);
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
        $page = $this->createComponentData($pageName,$language);

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
     * Return template files
     *
     * @return array
     */
    public function getTemplateFiles()
    {
        return $this->view->properties()->get('include.template.files');
    }

    /**
     * Get page fles
     *
     * @return array
     */
    public function getPageFiles()
    {
        return $this->view->properties()->get('include.page.files');        
    }

    /**
     * Get component files
     *
     * @return array
     */
    public function getComponentsFiles()
    {    
        return $this->view->properties()->get('include.components.files');       
    }

    /**
     * Set curret page
     *
     * @param string $name
     * @return void
     */
    public function setCurrent($name)
    {   
        Session::set('page.name',$name);
    }

    /**
     * Set current template
     *
     * @param string $name
     * @return void
     */
    public function setCurrentTemplate($name)
    { 
        $this->view->properties()->set('current.template',$name);    
        Session::set('current.template',$name);
    }

    /**
     * Get current template name
     *
     * @return string|null
     */
    public function getCurrentTemplate()
    { 
        $name = $this->view->properties()->get('current.template',null);   
        if (empty($name) == false) {
            return $name;
        } 

        $name = Session::get('current.template',null);
        if (empty($name) == false) {
            return $name;
        } 

        return $this->view->getPrimaryTemplate();
    }

    /**
     * Get current page name
     *
     * @return string
     */
    public static function getCurrent()
    {
        return Session::get('page.name');
    }

    /**
     * Get language path
     *
     * @param string $path
     * @param string|null $language
     * @return string
     */
    public static function getLanguagePath($path, $language = null)
    {
        $language = (empty($language) == true) ? Self::getCurrentLanguage() : $language; 
          
        return (\substr($path,-1) == '/') ? $path . $language . '/' : $path . '/' . $language . '/';
    }

    /**
     * Get curret page url
     *
     * @param boolean $full
     * @return string
     */
    public static function getCurrentUrl($full = true)
    {       
        $path = Session::get('current.path');

        return ($full == true) ? Self::getFullUrl($path) : $path;
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
     * Return library files
     *
     * @return array
     */
    public function getLibraryFiles()
    {
        return $this->view->properties()->get('ui.library.files',[]);
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
        $this->view->getCache()->save('language',$language,Self::$cacheSaveTime);
        $this->view->properties()->set('language',$language);
        Self::$currentLanguage = $language;
        Session::set('language',$language);
    }

    /**
     * Get current language
     *
     * @return string
     */
    public static function getCurrentLanguage()
    {
        return (empty(Self::$currentLanguage) == false) ? Self::$currentLanguage : Session::get('language','en');
    }

    /**
     * Return current page language
     *
     * @return string
     */
    public function getLanguage() 
    {  
        $language = $this->view->properties()->get('language',null);
        if (empty($language) == false) {
            return $language; 
        } 

        $language = Session::get('language',null); 
        if (empty($language) == false) {
            return $language;
        }

        $language = $this->view->getCache()->fetch('language');

        return (empty($language) == false) ? $language : 'en';        
    }

    /**
     * Return current css frameowrk
     *
     * @param string $templateName
     * @return array
     */
    public function getFramework($templateName)
    {
        $item = 'ui.current.framework.' . $templateName;
        $frameowrk = $this->view->properties()->get($item,null);
        if (empty($frameowrk) == false) {
            return $frameowrk; 
        } 

        $frameowrk = Session::get($item,null);
        if (empty($frameowrk) == false) {
            return $frameowrk; 
        } 

        $frameowrk = $this->view->getCache()->fetch($item);

        return (empty($frameowrk) == false) ? $frameowrk : ComponentData::DEFAULT_CSS_FRAMEWORK;          
    }

    /**
     * Set current css frameowrk
     *
     * @param string $name
     * @param string $templateName
     * @return void
     */
    public function setFramework($name, $templateName)
    {
        $item = 'ui.current.framework.' . $templateName;

        $this->view->getCache()->save($item,$name,Self::$cacheSaveTime);
        $this->view->properties()->set($item,$name);
        Session::set($item,$name);
    }

    /**
     * Get included ui librarues
     *
     * @return array
     */
    public function getIncludedLibraries()
    {
        return $this->view->properties()->set('ui.included.libraries',[]);
    }
    
    /**
     * Get page include files
     *
     * @param ComponentDataInterface $component
     * @return array
    */
    public function getPageIncludeFiles(ComponentDataInterface $component)
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
        $options['framework'] = $options['framework'] ?? ComponentData::DEFAULT_CSS_FRAMEWORK;
        $frameworPath = ($options['framework'] == ComponentData::DEFAULT_CSS_FRAMEWORK) ? '' : $options['framework'] . '/';

        $url = Url::getTemplateUrl($templateName);    
      
        $options['js'] = \array_map(function($value) use($url,$frameworPath) {
            return $url . '/js/' . $frameworPath . $value; 
        },$options['js']);

        $options['css'] = \array_map(function($value) use($url,$frameworPath) {
            return $url . '/css/' . $frameworPath . $value;         
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

        $libraryParams = (isset($this->libraryOptions[$properties['name']]) == true) ? $this->libraryOptions[$properties['name']] : [];
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
        if (\is_array($files) == true) {
            $this->view->properties()->set('ui.library.files',$files); 
            $this->view->properties()->set('ui.included.libraries',$libraryList); 
            return $files;
        }
        $files = [];

        foreach ($libraryList as $libraryItem) {           
            list($libraryName,$libraryVersion,$forceInclude) = Self::parseLibraryName($libraryItem);

            $properties = Self::getLibraryProperties($libraryName,$libraryVersion);            
            $params = $this->resolveLibraryParams($properties);
            $libraryFramework = $properties->get('framework',false);
            if ($libraryFramework == true && $libraryName != $currentFramework && $forceInclude == false) {
                // Skip framework library which is not current
                continue;
            }
            foreach($properties->get('files') as $file) {
                $libraryFile = $this->view->getLibraryPath($libraryName) . $file;
                $item = [
                    'file'        => (Utils::isValidUrl($file) == true) ? $file : Url::getLibraryFileUrl($libraryName,$file),
                    'type'        => \pathinfo($libraryFile,PATHINFO_EXTENSION),
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
        $libraryName = (isset($nameTokens[0]) == true) ? $nameTokens[0] : $libraryName;
        $libraryVersion = (isset($nameTokens[1]) == true) ? $nameTokens[1] : null;
        $libraryOption = (isset($nameTokens[2]) == true) ? $nameTokens[2] : $libraryVersion;
        $include = ($libraryOption == 'include') ? true : false;

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
}
