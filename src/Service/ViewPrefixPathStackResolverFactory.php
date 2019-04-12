<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\View\Resolver\PrefixPathStackResolver;

class ViewPrefixPathStackResolverFactory
{
    /**
     * Create the template prefix view resolver
     *
     * Creates a Zend\View\Resolver\PrefixPathStackResolver and populates it with the
     * ['view_manager']['prefix_template_path_stack']
     */
    public function __invoke(ContainerInterface $container) : PrefixPathStackResolver
    {
        $config   = $container->get('config');
        $prefixes = [];

        if (isset($config['view_manager']['prefix_template_path_stack'])) {
            $prefixes = $config['view_manager']['prefix_template_path_stack'];
        }

        return new PrefixPathStackResolver($prefixes);
    }
}
