<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\View\TestAsset;

use ArrayObject;
use Zend\View\Model\ModelInterface as Model;
use Zend\View\Renderer\RendererInterface as Renderer;
use Zend\View\Resolver\ResolverInterface as Resolver;

/**
 * Mock renderer
 */
class DumbStrategy implements Renderer
{
    protected $resolver;

    public function getEngine()
    {
        return $this;
    }

    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function render($nameOrModel, $values = null)
    {
        $options = [];
        $values  = (array) $values;
        if ($nameOrModel instanceof Model) {
            $options   = $nameOrModel->getOptions();
            $variables = $nameOrModel->getVariables();
            if ($variables instanceof ArrayObject) {
                $variables = $variables->getArrayCopy();
            }
            $values = array_merge($variables, $values);
            if (array_key_exists('template', $options)) {
                $nameOrModel = $options['template'];
            } else {
                $nameOrModel = '[UNKNOWN]';
            }
        }

        return sprintf('%s (%s): %s', $nameOrModel, json_encode($options), json_encode($values));
    }
}
