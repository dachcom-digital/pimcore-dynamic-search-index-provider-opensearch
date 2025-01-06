<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace DsOpenSearchBundle\Controller\Admin;

use DsOpenSearchBundle\Manager\IndexManager;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AdminAbstractController
{
    public function __construct(
        protected IndexManager $indexManager
    ) {
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
