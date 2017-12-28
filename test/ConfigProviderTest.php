<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\ConfigProvider;

/**
 * @covers \Zend\Mvc\ConfigProvider
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testConfigIsSerializable()
    {
        $config = new ConfigProvider();
        \serialize($config());
    }
}
