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

use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\View\Html\HtmlComponent;
use Arikaim\Core\View\Html\Page;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Utils;

use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDataInterface;
use Arikaim\Core\Interfaces\EmailCompilerInterface;

/**
 * Render email component
 */
class EmailComponent extends HtmlComponent implements HtmlComponentInterface
{
    /**
     * Emeil compilers list
     *
     * @var array
     */
    protected $emailCompilers = [];

    /**
     * Render email component data
     *
     * @param ComponentDataInterface $component
     * @param array $params   
     * @return ComponentDataInterface
     */
    public function renderComponentData(ComponentDataInterface $component, $params = [])
    {       
        if ($component->hasError() == true) {         
            $error = $component->getError();
            $params['message'] = $this->getErrorText($error['code'],$component->getFullName());
            $component = $this->createComponentData(Self::COMPONENT_ERROR_NAME,$this->language,true,$this->framework);  
            $component->setError($error['code'],$error['params'],$params['message']);           
        }   
        // default params      
        $params['component_url'] = $component->getUrl();
        $params['template_url'] = $component->getTemplateUrl(); 
        $params['component_framework'] = $component->getFramework(); 
     
        $params = Arrays::merge($component->getProperties(),$params);

        if (empty($this->framework) == true) {
            $framework = $component->getOption('framework',null);
            if (empty($framework) == false) {
                $component->setFramework($framework);                
            }
            $templateFile = $component->getTemplateFile($framework);
        } else {
            $framework = $this->framework;
            $templateFile = $component->getTemplateFile();
        }
       
        $templateCode = $this->view->fetch($templateFile,$params);

        if (empty($framework) == false) {
            $component->setFramework($framework);
            $templateCode = $this->compileCssFrameworkCode($templateCode,$framework);
        }
     
        if (Utils::hasHtml($templateCode) == true) {
            // Email is html  
            $file = $component->getComponentFile('css');           
            $params['component_css_file'] = ($file !== false) ? File::read($component->getFullPath() . $file) : null;              
            $params['library_code'] = (empty($framework) == false) ? $this->readLibraryCode($framework) : [];
            $params['body'] = $templateCode;
            $indexFile = Page::getIndexFile($component,$this->view->getPrimaryTemplate());
            $templateCode = $this->view->fetch($indexFile,$params);                    
        }

        $component->setHtmlCode($templateCode);   

        return $component;
    }   

    /**
     * Compile email code
     *
     * @param string $code
     * @param string $framework
     * @return string
     */
    public function compileCssFrameworkCode($code, $framework)
    {
        $compilerClass = (isset($this->emailCompilers[$framework]) == true) ? $this->emailCompilers[$framework] : null;

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
    public function readLibraryCode($name)
    {
        list($libraryName,$libraryVersion,$forceInclude) = Page::parseLibraryName($name);
        $properties = Page::getLibraryProperties($libraryName,$libraryVersion); 
        $content = [];

        foreach($properties->get('files') as $file) {
            $libraryFile = $this->view->getLibraryPath($libraryName) . $file;
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
    public function setEmailCompillers(array $compilers)
    {
        $this->emailCompilers = $compilers;       
    }
}
