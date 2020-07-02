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
use Arikaim\Core\View\Template\Template;
use Arikaim\Core\Collection\Collection;
use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\View\Html\PageHead;
use Arikaim\Core\View\Html\ResourceLocator;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Utils;
use Arikaim\Core\Utils\Text;
use Arikaim\Core\Http\Session;
use Arikaim\Core\Http\Url;

use Arikaim\Core\View\Interfaces\ComponentDataInterface;
use Arikaim\Core\Interfaces\View\HtmlPageInterface;
use Arikaim\Core\Interfaces\View\ViewInterface;
use Arikaim\Core\Interfaces\Packages\PackageFactoryInterface;

/**
 * Html page
 */
class Page extends Component implements HtmlPageInterface
{   
    /**
     * Page head properties
     *
     * @var PageHead
     */
    protected $head;
    
    /**
     * Package factory
     *
     * @var PackageFactoryInterface
     */
    protected $packageFactroy;

    /**
     * Constructor
     * 
     * @param ViewInterface $view
     */
    public function __construct(ViewInterface $view, PackageFactoryInterface $packageFactroy, $params = [], $language = null, $basePath = 'pages', $withOptions = true) 
    {  
        parent::__construct($view,null,$params,$language,$basePath,'page.json',$withOptions);

        $this->packageFactroy = $packageFactroy;       
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
    public function createHtmlComponent($name, $params = [], $language = null, $withOptions = true)
    {
        return new HtmlComponent($this->view,$name,$params,$language,'components','component.json',$withOptions);      
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
        if (empty($name) == true) {         
            return false;     
        }
        if (is_object($params) == true) {
            $params = $params->toArray();
        }
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
        $this->setCurrent($name);
        $component = $this->createComponentData($name,$language);
        $params['component_url'] = $component->getUrl();
        $params['template_url'] = $component->getTemplateUrl(); 
        
        $body = $this->getCode($component,$params);
        $indexPage = $this->getIndexFile($component);     
        
        $params = array_merge($params,[
            'body' => $body,
            'head' => $this->head->toArray()
        ]);   

        $component->setHtmlCode($this->view->fetch($indexPage,$params));

        return $component;
    }

    /**
     * Get page index file
     *
     * @param object $component
     * @return string
     */
    private function getIndexFile($component)
    {
        $type = $component->getType();
        $fullPath = $component->getRootComponentPath() . $component->getBasePath() . DIRECTORY_SEPARATOR . "index.html";

        if (file_exists($fullPath) == true) {
            if ($type == ComponentData::EXTENSION_COMPONENT) {
                return $component->getTemplateName() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . $component->getBasePath() . DIRECTORY_SEPARATOR . "index.html"; 
            } 
            return $component->getTemplateName() . DIRECTORY_SEPARATOR . $component->getBasePath() . DIRECTORY_SEPARATOR . "index.html";            
        }
    
        // get from system template
        return Template::SYSTEM_TEMPLATE_NAME . DIRECTORY_SEPARATOR . $component->getBasePath() . DIRECTORY_SEPARATOR . "index.html";          
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
        // include component files
        $this->view->properties()->merge('include.page.files',$component->getFiles());
        $this->includeFiles($component);

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
        $params = array_merge_recursive($params,(array)$properties);

        return $this->view->fetch($component->getTemplateFile(),$params);
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
        Session::set("page.name",$name);
    }

    /**
     * Get current page name
     *
     * @return string
     */
    public static function getCurrent()
    {
        return Session::get("page.name");
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
        if ($language == null) {
            $language = HtmlComponent::getLanguage();
        }
       
        return (substr($path,-1) == "/") ? $path . "$language/" : "$path/$language/";
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
        $path = (substr($path,0,1) == "/") ? substr($path,1) : $path;      
        $url = ($full == true) ? Url::BASE_URL : BASE_PATH;        
        $url = ($url == "/") ? $url : $url . "/"; 
        $url .= $path;      
        if (empty($language) == true) {
            return $url;
        }

        return (Session::get('default.language') != $language) ? Self::getLanguagePath($url,$language) : $url;
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
     * @param Component $component
     * @return bool
     */
    public function includeFiles($component) 
    {
        $files = $this->getPageIncludeOptions($component);
        $files = Arrays::setDefault($files,'library',[]);            
        $files = Arrays::setDefault($files,'loader',false);       
        
        $this->includeComponents($component);
     
        $this->view->getCache()->save("page.include.files." . $component->getName(),$files,10);
        $this->view->properties()->set('template.files',$files);
        // include ui lib files                
        $this->includeLibraryFiles($files['library']);  
      
        return true;
    }

    /**
     * Return template files
     *
     * @return array
     */
    public function getTemplateFiles()
    {
        return $this->view->properties()->get('template.files');
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
     * Get page include options
     *
     * @param Component $component
     * @return array
    */
    public function getPageIncludeOptions($component)
    {
        // from cache 
        $options = $this->view->getCache()->fetch("page.include.files." . $component->getName());
        if (is_array($options) == true) {          
            return $options;
        }

        // from page options
        $options = $component->getOption('include',null);
      
        if (empty($options) == false) {  
            // get include options from page.json file  
            $options = Arrays::setDefault($options,'template',null);   
            $options = Arrays::setDefault($options,'js',[]);  
            $options = Arrays::setDefault($options,'css',[]);   

            $url = Url::getExtensionViewUrl($component->getTemplateName());
           
            $options['js'] = array_map(function($value) use($url) {              
                return $url . "/js/" . $value; 
            },$options['js']);
          
            $options['css'] = array_map(function($value) use($url) {
                return $url . "/css/" . $value;
            },$options['css']);

            if (empty($options['template']) == false) {
                $options = array_merge_recursive($this->getTemplateIncludeOptions($options['template']),$options);              
            } elseif ($component->getType() == ComponentData::TEMPLATE_COMPONENT) {              
                $options = array_merge_recursive($this->getTemplateIncludeOptions($component->getTemplateName()),$options);                 
            }        
            
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

            // set loader from page.json
            if (isset($options['loader']) == true) {
                Session::set('template.loader',$options['loader']);
            }
            
            return $options;
        }

        // from component template 
        return $this->getTemplateIncludeOptions($component->getTemplateName());
    }

    /**
     * Include components files set in page.json include/components
     *
     * @param Component $component
     * @return void
     */
    protected function includeComponents($component)
    {
       // include component files
        $components = $component->getOption('include/components',null);        
        if (empty($components) == true) {
            return;
        }  

        foreach ($components as $item) {                        
            $files = $this->getComponentFiles($item);  
            $this->includeComponentFiles($files['js'],'js');
            $this->includeComponentFiles($files['css'],'css');              
        }      
    }

    /**
     * Get template include options
     *
     * @param string $templateName
     * @return array
     */
    public function getTemplateIncludeOptions($templateName)
    {
        $templateOptions = $this->packageFactroy->createPackage('template',$templateName)->getProperties();

        $options = $templateOptions->getByPath("include",[]);
    
        $options = Arrays::setDefault($options,'js',[]);  
        $options = Arrays::setDefault($options,'css',[]);   
        $options = Arrays::setDefault($options,'components',null);

        $url = Url::getTemplateUrl($templateName);    
      
        $options['js'] = array_map(function($value) use($url) {
            return $url . "/js/" . $value; 
        },$options['js']);

        $options['css'] = array_map(function($value) use($url) {
            return ResourceLocator::getResourceUrl($value,$url . "/css/" . $value);         
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
      
        return $options;
    }

    /**
     * Include library files
     *
     * @param array $libraryList
     * @return bool
     */
    public function includeLibraryFiles(array $libraryList)
    {          
        $libraryFiles = $this->view->getCache()->fetch("ui.library.files");        
        if (is_array($libraryFiles) == true) {
            $this->view->properties()->set('ui.library.files',$libraryFiles);   
            return true;
        }

        $frameworks = [];
        $includeLib = [];

        foreach ($libraryList as $libraryItem) {
            $nameTokens = explode(":",$libraryItem);
            $libraryName = (isset($nameTokens[0]) == true) ? $nameTokens[0] : $libraryItem;
            $libraryVersion = (isset($nameTokens[1]) == true) ? $nameTokens[1] : null;

            $library = $this->packageFactroy->createPackage('library',$libraryName);
            $files = $library->getFiles($libraryVersion);       
            $params = $library->resolveParams();

            foreach($files as $file) {
                $libraryFile = $this->view->getViewPath() . 'library' . DIRECTORY_SEPARATOR . $libraryName . DIRECTORY_SEPARATOR . $file;
                $item = [
                    'file'        => (Utils::isValidUrl($file) == true) ? $file : Url::getLibraryFileUrl($libraryName,$file),
                    'type'        => File::getExtension($libraryFile),
                    'params'      => $params,
                    'library'     => $libraryName,
                    'async'       => $library->getProperties()->get('async',false),
                    'crossorigin' => $library->getProperties()->get('crossorigin',null)
                ];
                array_push($includeLib,$item);
            }           
            if ($library->isFramework() == true) {
                array_push($frameworks,$libraryName);
            }
        }
        // save to cache
        $this->view->getCache()->save("ui.library.files",$includeLib,10);
        
        $this->view->properties()->set('ui.library.files',$includeLib);       
        Session::set("ui.included.libraries",json_encode($libraryList));
        Session::set("ui.included.frameworks",json_encode($frameworks));

        return true;
    }
}
