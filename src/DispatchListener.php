<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc;

use ArrayObject;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\Stdlib\ArrayUtils;

/**
 * Default dispatch listener
 *
 * Pulls controllers from the service manager's "ControllerManager" service.
 *
 * If the controller cannot be found a "404" result is set up. Otherwise it
 * will continue to try to load the controller.
 *
 * If the controller is not dispatchable it sets up a "404" result. In case
 * of any other exceptions it trigger the "dispatch.error" event in an attempt
 * to return a 500 status.
 *
 * If the controller subscribes to InjectApplicationEventInterface, it injects
 * the current MvcEvent into the controller.
 *
 * It then calls the controller's "dispatch" method, passing it the request and
 * response. If an exception occurs, it triggers the "dispatch.error" event,
 * in an attempt to return a 500 status.
 *
 * The return value of dispatching the controller is placed into the result
 * property of the MvcEvent, and returned.
 */
class DispatchListener extends AbstractListenerAggregate
{
    /**
     * @var Controller\ControllerManager
     */
    private $controllerManager;

    /**
     * @param Controller\ControllerManager $controllerManager
     */
    public function __construct(Controller\ControllerManager $controllerManager)
    {
        $this->controllerManager = $controllerManager;
    }

    /**
     * Attach listeners to an event manager
     *
     * @param  EventManagerInterface $events
     * @param  int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch']);
        if (function_exists('zend_monitor_custom_event_ex')) {
            $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'reportMonitorEvent']);
        }
    }

    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        if (null !== $e->getResult()) {
            return null;
        }

        $routeMatch        = $e->getRouteMatch();
        $controllerName    = $routeMatch instanceof RouteMatch
            ? $routeMatch->getParam('controller', 'not-found')
            : 'not-found';
        $application       = $e->getApplication();
        $controllerManager = $this->controllerManager;

        if (! $application instanceof Application) {
            throw new RuntimeException('Application is not an instance of ' . Application::class);
        }

        // Query abstract controllers, too!
        if (! $controllerManager->has($controllerName)) {
            $return = $this->marshalControllerNotFoundEvent(
                Application::ERROR_CONTROLLER_NOT_FOUND,
                $controllerName,
                $e,
                $application
            );
            return $this->complete($return, $e);
        }

        try {
            $controller = $controllerManager->get($controllerName);
        } catch (Exception\InvalidControllerException $exception) {
            $return = $this->marshalControllerNotFoundEvent(
                Application::ERROR_CONTROLLER_INVALID,
                $controllerName,
                $e,
                $application,
                $exception
            );
            return $this->complete($return, $e);
        } catch (InvalidServiceException $exception) {
            $return = $this->marshalControllerNotFoundEvent(
                Application::ERROR_CONTROLLER_INVALID,
                $controllerName,
                $e,
                $application,
                $exception
            );
            return $this->complete($return, $e);
        } catch (\Throwable $exception) {
            $return = $this->marshalBadControllerEvent($controllerName, $e, $application, $exception);
            return $this->complete($return, $e);
        } catch (\Exception $exception) {  // @TODO clean up once PHP 7 requirement is enforced
            $return = $this->marshalBadControllerEvent($controllerName, $e, $application, $exception);
            return $this->complete($return, $e);
        }

        if ($controller instanceof InjectApplicationEventInterface) {
            $controller->setEvent($e);
        }

        $request  = $e->getRequest();
        $response = $application->getResponse();
        $caughtException = null;
        $return = null;

        try {
            $return = $controller->dispatch($request, $response);
        } catch (\Throwable $ex) {
            $caughtException = $ex;
        } catch (\Exception $ex) {  // @TODO clean up once PHP 7 requirement is enforced
            $caughtException = $ex;
        }

        if ($caughtException !== null) {
            $e->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $e->setError($application::ERROR_EXCEPTION);
            $e->setController($controllerName);
            $e->setControllerClass(get_class($controller));
            $e->setParam('exception', $caughtException);

            $return = $application->getEventManager()->triggerEvent($e)->last();
            if (! $return) {
                $return = $e->getResult();
            }
        }

        return $this->complete($return, $e);
    }

    /**
     * @param MvcEvent $e
     */
    public function reportMonitorEvent(MvcEvent $e)
    {
        if (! \function_exists('zend_monitor_custom_event_ex')) {
            return;
        }

        $error     = $e->getError();
        $exception = $e->getParam('exception');
        // @TODO clean up once PHP 7 requirement is enforced
        if ($exception instanceof \Exception || $exception instanceof \Throwable) {
            zend_monitor_custom_event_ex(
                $error,
                $exception->getMessage(),
                'Zend Framework Exception',
                ['code' => $exception->getCode(), 'trace' => $exception->getTraceAsString()]
            );
        }
    }

    /**
     * Complete the dispatch
     *
     * @param  mixed $return
     * @param  MvcEvent $event
     * @return mixed
     */
    protected function complete($return, MvcEvent $event)
    {
        if (! is_object($return)) {
            if (ArrayUtils::hasStringKeys($return)) {
                $return = new ArrayObject($return, ArrayObject::ARRAY_AS_PROPS);
            }
        }
        $event->setResult($return);
        return $return;
    }

    /**
     * Marshal a controller not found exception event
     *
     * @param  string $type
     * @param  string $controllerName
     * @param  MvcEvent $event
     * @param  Application $application
     * @param  \Throwable|\Exception $exception
     * @return mixed
     */
    protected function marshalControllerNotFoundEvent(
        $type,
        $controllerName,
        MvcEvent $event,
        Application $application,
        $exception = null
    ) {
        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError($type);
        $event->setController($controllerName);
        $event->setControllerClass('invalid controller class or alias: ' . $controllerName);
        if ($exception !== null) {
            $event->setParam('exception', $exception);
        }

        $events  = $application->getEventManager();
        $results = $events->triggerEvent($event);
        $return  = $results->last();
        if (! $return) {
            $return = $event->getResult();
        }
        return $return;
    }

    /**
     * Marshal a bad controller exception event
     *
     * @param  string $controllerName
     * @param  MvcEvent $event
     * @param  Application $application
     * @param  \Throwable|\Exception $exception
     * @return mixed
     */
    protected function marshalBadControllerEvent(
        $controllerName,
        MvcEvent $event,
        Application $application,
        $exception
    ) {
        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError(Application::ERROR_EXCEPTION);
        $event->setController($controllerName);
        $event->setParam('exception', $exception);

        $events  = $application->getEventManager();
        $results = $events->triggerEvent($event);
        $return  = $results->last();
        if (! $return) {
            return $event->getResult();
        }

        return $return;
    }
}
