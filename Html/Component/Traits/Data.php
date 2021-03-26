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

use Arikaim\Core\Interfaces\View\ComponentDataInterface;

/**
 * Data source for components
 */
trait Data
{
    /**
     * Component data file
     *
     * @var string|null
     */
    protected $dataFile = null;

    /**
     * Get component data file.
     * 
     * @return string|null
     */
    public function getDataFile(): ?string
    {
        return $this->dataFile;
    }

    /**
     * Resolve component data file
     *
     * @return void
     */
    protected function resolveDataFile(): void
    {
        $fileName = $this->fullPath . $this->name . '.php';
       
        $this->dataFile = (\file_exists($fileName) == true) ? $fileName : null;       
    }

    /**
     * Process daat file
     *
     * @return array|null
     */
    protected function processDataFile(): ?array
    {
        $this->resolveDataFile();

        if (empty($this->dataFile) == false) {
            // include data file
            $componentData = require $dataFile;                       
            if ($componentData instanceof ComponentDataInterface) {                   
                return $componentData->getData($params);              
            }          
        }

        return null;
    }
}
