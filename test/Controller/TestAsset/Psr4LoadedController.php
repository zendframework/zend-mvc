<?php

namespace ZendTest\Mvc\Controller\TestAsset
{
    use Zend\Mvc\Controller\AbstractActionController;

    class Psr4LoadedController extends AbstractActionController
    {
        public function indexAction()
        {
            return ['content' => 'test'];
        }
    }
}