<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Controller\Plugin;

use Zend\Http\Header\Accept\FieldValuePart\AbstractFieldValuePart;
use Zend\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart;
use Zend\Http\Headers;
use Zend\Http\Request;
use Zend\Mvc\Exception\UnexpectedValueException;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\InvalidArgumentException;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\ViewModel;

/**
 * Controller Plugin to assist in selecting an appropriate View Model type based on the
 * User Agent's accept header.
 */
class AcceptableViewModelSelector extends AbstractPlugin
{
    /**
     *
     * @var string the Key to inject the name of a viewmodel with in an Accept Header
     */
    const INJECT_VIEWMODEL_NAME = '_internalViewModel';

    /**
     *
     * @var null|\Zend\Mvc\MvcEvent
     */
    protected $event;

    /**
     *
     * @var null|\Zend\Http\Request
     */
    protected $request;

    /**
     * Default array to match against.
     *
     * @var array|null
     */
    protected $defaultMatchAgainst;

    /**
     *
     * @var string Default ViewModel
     */
    protected $defaultViewModelName = ViewModel::class;

    /**
     * Detects an appropriate viewmodel for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched
     * @throws InvalidArgumentException If the supplied and matched View Model could not be found
     * @return ModelInterface|null
     */
    public function __invoke(
        array $matchAgainst = null,
        $returnDefault = true,
        & $resultReference = null
    ) {
        return $this->getViewModel($matchAgainst, $returnDefault, $resultReference);
    }

    /**
     * Detects an appropriate viewmodel for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched
     * @throws InvalidArgumentException If the supplied and matched View Model could not be found
     * @return ModelInterface|null
     */
    public function getViewModel(
        array $matchAgainst = null,
        $returnDefault = true,
        & $resultReference = null
    ) {
        $name = $this->getViewModelName($matchAgainst, $returnDefault, $resultReference);

        if (! $name) {
            return null;
        }

        if (! class_exists($name)) {
            throw new InvalidArgumentException('The supplied View Model could not be found');
        }

        $viewModel = new $name();

        if (! $viewModel instanceof ModelInterface) {
            throw InvalidArgumentException::unexpectedType(ModelInterface::class, $viewModel);
        }

        return $viewModel;
    }

    /**
     * Detects an appropriate viewmodel name for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched.
     * @return string|null Returns null if $returnDefault = false and no match could be made
     */
    public function getViewModelName(
        array $matchAgainst = null,
        $returnDefault = true,
        & $resultReference = null
    ) {
        $res = $this->match($matchAgainst);
        if ($res) {
            $resultReference = $res;
            return $this->extractViewModelName($res);
        }

        if ($returnDefault) {
            return $this->defaultViewModelName;
        }
    }

    /**
     * Detects an appropriate viewmodel name for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @return AcceptFieldValuePart|null The object that was matched
     */
    public function match(array $matchAgainst = null)
    {
        $request        = $this->getRequest();
        /** @var Headers $headers */
        $headers        = $request->getHeaders();

        if (! $headers->has('accept')) {
            return null;
        }

        if (! $matchAgainst) {
            $matchAgainst = $this->defaultMatchAgainst;
        }

        if (! $matchAgainst) {
            return null;
        }

        $matchAgainstString = '';
        foreach ($matchAgainst as $modelName => $modelStrings) {
            foreach ((array) $modelStrings as $modelString) {
                $matchAgainstString .= $this->injectViewModelName($modelString, $modelName);
            }
        }

        /** @var \Zend\Http\Header\Accept $accept */
        $accept = $headers->get('Accept');
        /** @var AcceptFieldValuePart|false $match */
        $match = $accept->match($matchAgainstString);
        if ($match === false) {
            return null;
        }

        return $match;
    }

    /**
     * Set the default View Model (name) to return if no match could be made
     * @param string $defaultViewModelName The default View Model name
     * @return AcceptableViewModelSelector provides fluent interface
     */
    public function setDefaultViewModelName($defaultViewModelName)
    {
        $this->defaultViewModelName = (string) $defaultViewModelName;
        return $this;
    }

    /**
     * Set the default View Model (name) to return if no match could be made
     * @return string
     */
    public function getDefaultViewModelName()
    {
        return $this->defaultViewModelName;
    }

    /**
     * Set the default Accept Types and View Model combinations to match against if none are specified.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @return AcceptableViewModelSelector provides fluent interface
     */
    public function setDefaultMatchAgainst(array $matchAgainst = null)
    {
        $this->defaultMatchAgainst = $matchAgainst;
        return $this;
    }

    /**
     * Get the default Accept Types and View Model combinations to match against if none are specified.
     *
     * @return array|null
     */
    public function getDefaultMatchAgainst()
    {
        return $this->defaultMatchAgainst;
    }

    /**
     * Inject the viewmodel name into the accept header string
     *
     * @param string|array $modelAcceptString
     * @param string $modelName
     * @return string
     */
    protected function injectViewModelName($modelAcceptString, $modelName)
    {
        $modelName = str_replace('\\', '|', $modelName);
        $modelAcceptString = is_array($modelAcceptString)
            ? $modelAcceptString[key($modelAcceptString)]
            : $modelAcceptString;
        return $modelAcceptString . '; ' . self::INJECT_VIEWMODEL_NAME . '="' . $modelName . '", ';
    }

    /**
     * Extract the viewmodel name from a match
     * @param AbstractFieldValuePart $res
     * @return string
     */
    protected function extractViewModelName(AbstractFieldValuePart $res)
    {
        $matchedAgainst = $res->getMatchedAgainst();
        if (null === $matchedAgainst) {
            throw new UnexpectedValueException('Unable to find matchedAgainst value');
        }
        $modelName = $matchedAgainst->params[self::INJECT_VIEWMODEL_NAME];
        return str_replace('|', '\\', $modelName);
    }

    /**
     * Get the request
     *
     * @return Request
     * @throws DomainException if unable to find request
     */
    protected function getRequest()
    {
        if ($this->request) {
            return $this->request;
        }

        $event = $this->getEvent();
        $request = $event->getRequest();
        if (! $request instanceof Request) {
            throw new DomainException(
                'The event used does not contain a valid Request, but must.'
            );
        }

        $this->request = $request;
        return $request;
    }

    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws DomainException if unable to find event
     */
    protected function getEvent()
    {
        if ($this->event) {
            return $this->event;
        }

        $controller = $this->getController();
        if (! $controller instanceof InjectApplicationEventInterface) {
            throw new DomainException(
                'A controller that implements InjectApplicationEventInterface '
                  . 'is required to use ' . __CLASS__
            );
        }

        $event = $controller->getEvent();
        if (! $event instanceof MvcEvent) {
            $params = $event->getParams();
            $event = new MvcEvent();
            $event->setParams($params);
        }
        $this->event = $event;

        return $this->event;
    }
}
