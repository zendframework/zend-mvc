<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Container\ViewPrefixPathStackResolverFactory;
use Zend\View\Resolver\PrefixPathStackResolver;
use ZendTest\Mvc\ContainerTrait;

/**
 *
 * @covers \Zend\Mvc\Container\ViewPrefixPathStackResolverFactory
 * @covers \Zend\Mvc\Container\ViewManagerConfigTrait
 */
class ViewPrefixPathStackResolverFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreateService()
    {
        $container = $this->mockContainerInterface();

        $this->injectServiceInContainer($container, 'config', [
            'view_manager' => [
                'prefix_template_path_stack' => [
                    'album/' => [],
                ],
            ],
        ]);

        $factory  = new ViewPrefixPathStackResolverFactory();
        $resolver = $factory($container->reveal(), 'ViewPrefixPathStackResolver');

        $this->assertInstanceOf(PrefixPathStackResolver::class, $resolver);
    }
}
