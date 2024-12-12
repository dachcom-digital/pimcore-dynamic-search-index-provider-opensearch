<?php

namespace DsOpenSearchBundle\Controller\Admin;

use DsOpenSearchBundle\Manager\IndexManager;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AdminAbstractController
{
    public function __construct(
        protected IndexManager $indexManager
    )
    {
    }

    public function rebuildMappingAction(Request $request): Response
    {
        $contextName = $request->get('context');

        if (empty($contextName)) {
            return new Response('no context given', 400);
        }

        try {
            $this->indexManager->rebuildIndex($contextName);
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }

        return new Response();
    }

}
