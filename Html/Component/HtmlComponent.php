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

use Arikaim\Core\View\Html\Component\BaseComponent;
use Arikaim\Core\Interfaces\View\HtmlComponentInterface;
use Arikaim\Core\View\Interfaces\ComponentDescriptorInterface;
use Arikaim\Core\Interfaces\View\ComponentDataInterface;

use Arikaim\Core\Interfaces\View\RequireAccessInterface;

//use Arikaim\Core\View\Html\Traits\Access;
use Arikaim\Core\View\Html\Component\Traits\IncludeTrait;
use Arikaim\Core\View\Html\Component\Traits\Options;
use Arikaim\Core\View\Html\Component\Traits\Properties;
use Arikaim\Core\View\Html\Component\Traits\Data;

/**
 * Html component
 */
class HtmlComponent extends BaseComponent implements RequireAccessInterface
{
    use 
        Options,
        Properties,
        Data,
        IncludeTrait;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $language  
     * @param string $viewPath
     * @param string $extensionsPath
     * @param string $primaryTemplate
     */
    public function __construct(string $name,string $language,string $viewPath,string $extensionsPath,string $primaryTemplate) 
    {
        parent::__construct($name,'components',$language,$viewPath,$extensionsPath,$primaryTemplate,'arikaim');
    }   

    /**
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {       
    }

    /**
     * Process component options
     *
     * @return void
     */
    protected function processOptions(): void
    {            
        $this->processIncludeOption();      
    }

    /**
     * Render component data
     *     
     * @param array $params   
     * @return bool
     */
    public function resolve(array $params = []): bool
    {        
        $this->loadProperties();
        $this->loadOptions(); 
        $this->addComponentFile('js');    
        $this->addComponentFile('css');           
        $this->resolveHtmlContent();
        
        // add default params   
        $this->mergeContext($this->getDefultParams());

        if ($this->isValid() == false) {           
            $this->setError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $this->fullName]); 
            return false;                
        }

        $this->processOptions();
        // process data file
        $this->processDataFile();   
        
        $this->mergeContext($this->getProperties());
        $this->mergeContext($params);
        
        return true;
    }
}
