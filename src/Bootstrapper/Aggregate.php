<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Bootstrapper;

use Zend\Mvc\ApplicationInterface;

class Aggregate implements BootstrapperInterface
{
    /** @var BootstrapperInterface[] */
    private $bootstrappers = [];

    public function __construct(array $bootstrappers)
    {
        foreach ($bootstrappers as $bootstrapper) {
            $this->addBootstrapper($bootstrapper);
        }
    }

    public function bootstrap(ApplicationInterface $application) : void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $bootstrapper->bootstrap($application);
        }
    }

    /**
     * @return BootstrapperInterface[]
     */
    public function getBootstrappers() : array
    {
        return $this->bootstrappers;
    }

    private function addBootstrapper(BootstrapperInterface $bootstrapper) : void
    {
        $this->bootstrappers[] = $bootstrapper;
    }
}
