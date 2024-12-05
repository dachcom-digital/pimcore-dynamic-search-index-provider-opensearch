<?php

namespace DsOpenSearchBundle\Controller\Admin;

use DsOpenSearchBundle\Builder\ClientBuilderInterface;
use DsOpenSearchBundle\Service\IndexPersistenceService;
use DynamicSearchBundle\Builder\ContextDefinitionBuilderInterface;
use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Generator\IndexDocumentGeneratorInterface;
use DynamicSearchBundle\Provider\PreConfiguredIndexProviderInterface;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AdminAbstractController
{
    public function __construct(
        protected ContextDefinitionBuilderInterface $contextDefinitionBuilder,
        protected IndexDocumentGeneratorInterface $indexDocumentGenerator,
        protected ClientBuilderInterface $clientBuilder,
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
            $contextDefinition = $this->contextDefinitionBuilder->buildContextDefinition($contextName, ContextDefinitionInterface::CONTEXT_DISPATCH_TYPE_INDEX);

            if (!$contextDefinition instanceof ContextDefinitionInterface) {
                throw new \Exception(
                    sprintf('no context definition with name "%s" found', $contextName)
                );
            }

            $indexDocument = $this->indexDocumentGenerator->generateWithoutData($contextDefinition, ['preConfiguredIndexProvider' => true]);

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
        } catch (\Throwable $e) {
            return new Response($e->getMessage(), 500);
        }

        return new Response();
    }

}
