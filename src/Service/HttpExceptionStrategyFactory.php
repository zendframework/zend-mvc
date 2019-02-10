<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\Mvc\View\Http\ExceptionStrategy;

class HttpExceptionStrategyFactory
{
    use HttpViewManagerConfigTrait;

    public function __invoke(ContainerInterface $container) : ExceptionStrategy
    {
        $strategy = new ExceptionStrategy();
        $config   = $this->getConfig($container);

        $this->injectDisplayExceptions($strategy, $config);
        $this->injectExceptionTemplate($strategy, $config);

        return $strategy;
    }

    /**
     * Inject strategy with configured display_exceptions flag.
     *
     * @param ExceptionStrategy $strategy
     * @param array             $config
     */
    private function injectDisplayExceptions(ExceptionStrategy $strategy, array $config)
    {
        $flag = $config['display_exceptions'] ?? false;
        $strategy->setDisplayExceptions($flag);
    }

    /**
     * Inject strategy with configured exception_template
     *
     * @param ExceptionStrategy $strategy
     * @param array             $config
     */
    private function injectExceptionTemplate(ExceptionStrategy $strategy, array $config)
    {
        $template = $config['exception_template'] ?? 'error';
        $strategy->setExceptionTemplate($template);
    }
}
