<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Bootstrapper;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Bootstrapper\Aggregate;
use Zend\Mvc\Bootstrapper\BootstrapperInterface;

/**
 * @covers \Zend\Mvc\Bootstrapper\Aggregate
 */
class AggregateTest extends TestCase
{
    public function testAcceptsListOfBootstrappers()
    {
        $bootstrappers = [
            $this->prophesize(BootstrapperInterface::class)->reveal(),
            $this->prophesize(BootstrapperInterface::class)->reveal(),
        ];
        $bootstrapper  = new Aggregate($bootstrappers);
        $this->assertSame($bootstrappers, $bootstrapper->getBootstrappers());
    }

    public function testInvokesAllBootstrappersForApplicationInOrder()
    {
        $application = $this->prophesize(ApplicationInterface::class)->reveal();
        $callIndex   = 0;
        $mock1       = $this->prophesize(BootstrapperInterface::class);
        $mock1->bootstrap($application)
            ->shouldBeCalled()
            ->will(function () use (&$callIndex) {
                $callIndex++;
                Assert::assertSame(1, $callIndex);
            });
        $mock2 = $this->prophesize(BootstrapperInterface::class);
        $mock2->bootstrap($application)
            ->shouldBeCalled()
            ->will(function () use (&$callIndex) {
                $callIndex++;
                Assert::assertSame(2, $callIndex);
            });
        $mock3 = $this->prophesize(BootstrapperInterface::class);
        $mock3->bootstrap($application)
            ->shouldBeCalled()
            ->will(function () use (&$callIndex) {
                $callIndex++;
                Assert::assertSame(3, $callIndex);
            });
        $bootstrapper = new Aggregate([
            $mock1->reveal(),
            $mock2->reveal(),
            $mock3->reveal(),
        ]);
        $bootstrapper->bootstrap($application);
    }
}
