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

namespace DsOpenSearchBundle\Manager;

use DsOpenSearchBundle\Builder\ClientBuilderInterface;
use DsOpenSearchBundle\Service\IndexPersistenceService;
use DynamicSearchBundle\Builder\ContextDefinitionBuilderInterface;
use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Generator\IndexDocumentGeneratorInterface;
use DynamicSearchBundle\Provider\PreConfiguredIndexProviderInterface;

class IndexManager
{
    public function __construct(
        protected ContextDefinitionBuilderInterface $contextDefinitionBuilder,
        protected IndexDocumentGeneratorInterface $indexDocumentGenerator,
        protected ClientBuilderInterface $clientBuilder,
    ) {
    }

    public function rebuildIndex(string $contextName): void
    {
        $contextDefinition = $this->contextDefinitionBuilder->buildContextDefinition($contextName, ContextDefinitionInterface::CONTEXT_DISPATCH_TYPE_INDEX);

        if (!$contextDefinition instanceof ContextDefinitionInterface) {
            throw new \Exception(sprintf('no context definition with name "%s" found', $contextName));
        }

        try {
            $indexDocument = $this->indexDocumentGenerator->generateWithoutData($contextDefinition, ['preConfiguredIndexProvider' => true]);
        } catch (\Throwable $e) {
            throw new \Exception(
                sprintf(
                    '%s. (The current context index provider also requires pre-configured indices. Please make sure your document definition implements the "%s" interface)',
                    $e->getMessage(),
                    PreConfiguredIndexProviderInterface::class
                )
            );
        }

        if (!$indexDocument->hasIndexFields()) {
            throw new \Exception(
                sprintf(
                    'No Index Document found. The current context index provider requires pre-configured indices. Please make sure your document definition implements the "%s" interface',
                    PreConfiguredIndexProviderInterface::class
                )
            );
        }

        $options = $contextDefinition->getIndexProviderOptions();

        $client = $this->clientBuilder->build($options);
        $indexService = new IndexPersistenceService($client, $options);

        if ($indexService->indexExists()) {
            $indexService->dropIndex();
        }

        $indexService->createIndex($indexDocument);
    }
}
