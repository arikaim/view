<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Html\Component;

use Arikaim\Core\View\Html\AbstractComponent;
use Arikaim\Core\View\Html\Page;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Utils\Utils;

use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\EmailCompilerInterface;

/**
 * Render email component
 */
class EmailComponent extends AbstractComponent implements HtmlComponentInterface
{
    /**
     * Emeil compilers list
     *
     * @var array
     */
    protected $emailCompilers = [];

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {         
    }

    /**
     * Render email component data
     *
     * @param ComponentDescriptorInterface $component
     * @param array $params   
     * @return ComponentDescriptorInterface
     */
    public function renderComponentDescriptor(ComponentDescriptorInterface $component, array $params = [])
    {       
        $component = $this->processOptions($component);
        
        if ($component->hasError() == true) {         
            return $this->renderComponentError($component,$params);           
        }   
        // default params           
        $params = $this->resolevDefultParams($component,$params);
        $params = \array_merge_recursive($component->getProperties(),$params);    
 
        $library = $component->getOption('library');
        $templateFile = $component->getTemplateFile();
        $templateCode = $this->service->fetch($templateFile,$params);

        if (empty($library) == false) {
            $templateCode = $this->compileCssFrameworkCode($templateCode,$library);
        }
     
        if (Utils::hasHtml($templateCode) == true) {
            // Email is html  
            $file = $component->getComponentFile('css');           
            $params['component_css_file'] = ($file !== false) ? File::read($component->getFullPath() . $file) : null;              
            $params['library_code'] = (empty($library) == false) ? $this->readLibraryCode($library) : [];
            $params['body'] = $templateCode;

            $indexFile = Page::getIndexFile($component,$this->primaryTemplate);
            $templateCode = $this->service->fetch($indexFile,$params);                    
        }

        $component->setHtmlCode($templateCode);   

        return $component;
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
        list($libraryName,$libraryVersion,$forceInclude) = Page::parseLibraryName($name);
        $properties = Page::getLibraryProperties($libraryName,$libraryVersion); 
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
