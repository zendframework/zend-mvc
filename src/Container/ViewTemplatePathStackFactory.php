<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Resolver\TemplatePathStack;

class ViewTemplatePathStackFactory
{
    use ViewManagerConfigTrait;

    /**
     * Create the template path stack view resolver
     *
     * Creates a Zend\View\Resolver\TemplatePathStack and populates it with the
     * ['view_manager']['template_path_stack'] and sets the default suffix with the
     * ['view_manager']['default_template_suffix']
     *
     * @param  ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return TemplatePathStack
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : TemplatePathStack
    {
        $config = $this->getConfig($container);

        $templatePathStack = new TemplatePathStack();

        if (is_array($config)) {
            if (isset($config['template_path_stack'])) {
                $templatePathStack->addPaths($config['template_path_stack']);
            }
            if (isset($config['default_template_suffix'])) {
                $templatePathStack->setDefaultSuffix($config['default_template_suffix']);
            }
        }

        return $templatePathStack;
    }
}
