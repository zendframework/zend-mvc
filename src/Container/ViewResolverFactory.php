<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\PrefixPathStackResolver;
use Zend\View\Resolver\RelativeFallbackResolver;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;

class ViewResolverFactory
{
    /**
     * Create the aggregate view resolver
     *
     * Creates a Zend\View\Resolver\AggregateResolver and attaches the template
     * map resolver and path stack resolver
     *
     * @param  ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return AggregateResolver
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : AggregateResolver
    {
        $resolver = new AggregateResolver();

        /* @var $mapResolver ResolverInterface */
        $mapResolver             = $container->get(TemplateMapResolver::class);
        /* @var $pathResolver ResolverInterface */
        $pathResolver            = $container->get(TemplatePathStack::class);
        /* @var $prefixPathStackResolver ResolverInterface */
        $prefixPathStackResolver = $container->get(PrefixPathStackResolver::class);

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
