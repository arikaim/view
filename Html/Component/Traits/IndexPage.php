<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\View\Html\Component\Traits;

use Arikaim\Core\Interfaces\View\ComponentInterface;

/**
 * Get index page
 */
trait IndexPage
{
    /**
     * Get page index file
     *    
     * @return string
     */
    public function getIndexFile(string $currentTemlate): string
    {        
        $templateName = $currentTemlate;

        switch ($this->location) {
            case ComponentInterface::TEMPLATE_COMPONENT:
                $templateName = $this->getTemplateName();
                break;
            case ComponentInterface::EXTENSION_COMPONENT:
                $templateName = $this->getTemplateName() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
                break; 
        }
    
        return DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $this->getBasePath() . DIRECTORY_SEPARATOR . 'index.html';            
    }
}
