<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\RelativeFallbackResolver;
use Zend\View\Resolver\ResolverInterface;

class ViewResolverFactory
{
    /**
     * Create the aggregate view resolver
     *
     * Creates a Zend\View\Resolver\AggregateResolver and attaches the template
     * map resolver and path stack resolver
     */
    public function __invoke(ContainerInterface $container) : AggregateResolver
    {
        $resolver = new AggregateResolver();

        /** @var ResolverInterface $mapResolver */
        $mapResolver = $container->get('ViewTemplateMapResolver');
        /** @var ResolverInterface $pathResolver */
        $pathResolver = $container->get('ViewTemplatePathStack');
        /** @var ResolverInterface $prefixPathStackResolver */
        $prefixPathStackResolver = $container->get('ViewPrefixPathStackResolver');

        $resolver
            ->attach($mapResolver)
            ->attach($pathResolver)
            ->attach($prefixPathStackResolver)
            ->attach(new RelativeFallbackResolver($mapResolver))
            ->attach(new RelativeFallbackResolver($pathResolver))
            ->attach(new RelativeFallbackResolver($prefixPathStackResolver));

        return $resolver;
    }
}
