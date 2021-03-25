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

use Arikaim\Core\View\Html\Component\Traits\IncludeTrait;
use Arikaim\Core\View\Html\Component\Traits\Options;
use Arikaim\Core\View\Html\Component\Traits\Properties;

/**
 * Static html component
 */
class StaticHtmlComponent extends BaseComponent 
{
    use 
        Properties,
        Options,
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
        parent::__construct($name,'components',$language,$viewPath,$extensionsPath,$primaryTemplate,'static');
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
     * Init component
     *
     * @return void
     */
    public function init(): void 
    {
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
        $this->resolveHtmlContent();
        
        // add default params   
        $this->mergeContext($this->getDefultParams());

        if ($this->isValid() == false) {           
            $this->setError('TEMPLATE_COMPONENT_NOT_FOUND',['full_component_name' => $this->fullName]); 
            return false;                
        }

        $this->processOptions();
       
        $this->mergeContext($this->getProperties());
        $this->mergeContext($params);
        
        return true;
    }
}
