<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\View\Template\Tags;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * Component tag node
 */
class ComponentNode extends Node implements NodeOutputInterface
{
    /**
     * Twig extension class   
     */
    const TWIG_EXTENSION_CLASS = 'Arikaim\\Core\\View\\Template\\Extension';

    /**
     * Twig extension class  
     *
     * @var string
     */
    protected $twigExtensionClass;

    /**
     * Constructor
     *
     * @param Node $body
     * @param array $params
     * @param integer $line
     * @param string $tag
     * @param string|null $twigExtensionClass
     */
    public function __construct(Node $body, $params = [], $line = 0, $tag = 'component', $twigExtensionClass = null)
    {
        $this->twigExtensionClass = $twigExtensionClass ?? Self::TWIG_EXTENSION_CLASS;

        parent::__construct(['body' => $body],$params,$line,$tag);
    }

    /**
     * Compile node
     *
     * @param Compiler $compiler
     * @return void
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        $componentName = $this->getAttribute('name');
        $body = $this->getNode('body');
        
        $compiler
            ->write('ob_start();')
            ->subcompile($body,true)
            ->write("\$context['content'] = trim(ob_get_clean());")
            ->write('echo $this->env->getExtension("' . $this->twigExtensionClass . '")->loadComponent("' . $componentName . '",$context);');    
    }
}
