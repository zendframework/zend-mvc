<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Mvc\TestAsset;

use Psr\Container\ContainerInterface;
use Zend\Mvc\ConfigProvider;
use Zend\Router\ConfigProvider as RouterConfigProvider;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

class ApplicationConfigHelper
{
    public static function getConfig(array $extraConfig = null) : array
    {
        $config = (new ConfigProvider())();
        $config = ArrayUtils::merge(
            $config,
            (new RouterConfigProvider())()
        );
        if (! empty($extraConfig)) {
            $config = ArrayUtils::merge(
                $config,
                $extraConfig
            );
        }
        return $config;
    }

    public static function configureContainer(array $config) : ContainerInterface
    {
        $dependencies = $config['dependencies'];
        $dependencies['services']['config'] = $config;
        return new ServiceManager($dependencies);
    }
}
