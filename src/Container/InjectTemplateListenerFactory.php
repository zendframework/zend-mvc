<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Interop\Container\ContainerInterface;
use Zend\Mvc\View\Http\InjectTemplateListener;

class InjectTemplateListenerFactory
{
    use ViewManagerConfigTrait;

    /**
     * Create and return an InjectTemplateListener instance.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return InjectTemplateListener
     */
    public function __invoke(
        ContainerInterface $container,
        string $name,
        array $options = null
    ) : InjectTemplateListener {
        $listener = new InjectTemplateListener();
        $config   = $this->getConfig($container);

        if (isset($config['controller_map'])
            && (is_array($config['controller_map']))
        ) {
            $listener->setControllerMap($config['controller_map']);
        }

        return $listener;
    }
}
