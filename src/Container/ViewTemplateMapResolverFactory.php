<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Resolver\TemplateMapResolver;

class ViewTemplateMapResolverFactory
{
    use ViewManagerConfigTrait;

    /**
     * Create the template map view resolver
     *
     * Creates a Zend\View\Resolver\AggregateResolver and populates it with the
     * ['view_manager']['template_map']
     *
     * @param  ContainerInterface $container
     * @return TemplateMapResolver
     */
    public function __invoke(ContainerInterface $container) : TemplateMapResolver
    {
        $config = $this->getConfig($container);
        $map = [];
        if (is_array($config) && isset($config['template_map'])) {
            $map = $config['template_map'];
        }
        return new TemplateMapResolver($map);
    }
}
