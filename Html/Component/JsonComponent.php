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

use Arikaim\Core\View\Html\Component\Traits\Properties;

/**
 * Joson component for render messages
 */
class JsonComponent extends BaseComponent implements HtmlComponentInterface
{
    use Properties;

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
        parent::__construct($name,'components',$language,$viewPath,$extensionsPath,$primaryTemplate,'json');
    }

    /**
     * Return true if component is valid
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->hasProperties();
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
}
