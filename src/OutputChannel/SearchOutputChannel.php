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

namespace DsOpenSearchBundle\OutputChannel;

use DsOpenSearchBundle\Builder\ClientBuilderInterface;
use DsOpenSearchBundle\Service\IndexQueryService;
use DynamicSearchBundle\EventDispatcher\OutputChannelModifierEventDispatcher;
use DynamicSearchBundle\OutputChannel\Context\OutputChannelContextInterface;
use DynamicSearchBundle\OutputChannel\OutputChannelInterface;
use DynamicSearchBundle\OutputChannel\Query\SearchContainerInterface;
use OpenSearchDSL\Search;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchOutputChannel implements OutputChannelInterface
{
    protected array $options;
    protected OutputChannelContextInterface $outputChannelContext;
    protected OutputChannelModifierEventDispatcher $eventDispatcher;

    public function __construct(protected ClientBuilderInterface $clientBuilder)
    {
    }

    public static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'result_limit'
        ]);

        $resolver->setDefaults([
            'result_limit' => 10,
        ]);

        $resolver->setAllowedTypes('result_limit', ['int']);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setOutputChannelContext(OutputChannelContextInterface $outputChannelContext): void
    {
        $this->outputChannelContext = $outputChannelContext;
    }

    public function setEventDispatcher(OutputChannelModifierEventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getQuery(): mixed
    {
        $queryTerm = $this->outputChannelContext->getRuntimeQueryProvider()->getUserQuery();

        $cleanTerm = $this->eventDispatcher->dispatchFilter(
            'query.clean_term',
            [
                'raw_term'               => $queryTerm,
                'output_channel_options' => $this->options
            ]
        );

        $client = $this->clientBuilder->build($this->outputChannelContext->getIndexProviderOptions());
        $queryService = new IndexQueryService($client, $this->outputChannelContext->getIndexProviderOptions());

        $search = $queryService->createSearch();

        $eventData = $this->eventDispatcher->dispatchAction('post_query_build', [
            'query' => $search,
            'term'  => $cleanTerm
        ]);

        return $eventData->getParameter('query');
    }

    public function getResult(SearchContainerInterface $searchContainer): SearchContainerInterface
    {
        $query = $searchContainer->getQuery();

        if (!$query instanceof Search) {
            return $searchContainer;
        }

        $runtimeOptions = $this->outputChannelContext->getRuntimeOptions();
        $indexProviderOptions = $this->outputChannelContext->getIndexProviderOptions();

        $client = $this->clientBuilder->build($this->outputChannelContext->getIndexProviderOptions());

        $currentPage = is_numeric($runtimeOptions['current_page']) ? (int) $runtimeOptions['current_page'] : 1;
        $limit = $this->options['result_limit'] > 0 ? $this->options['result_limit'] : 10;

        // @todo: implement search_after

        if ($limit > 10000) {
            throw new \Exception(sprintf('Limit is restricted by 10,000 hits. If you need to page through more than 10,000 hits, use the search_after parameter instead.'));
        }

        $query->setFrom($currentPage > 1 ? (($currentPage - 1) * $limit) : 0);
        $query->setSize($limit);

        $params = [
            'index' => $indexProviderOptions['index']['identifier'],
            'body'  => $query->toArray(),
        ];

        $result = $client->search($params);
        $hits = $result['hits']['hits'];

        $eventData = $this->eventDispatcher->dispatchAction('post_result_execute', [
            'result' => $hits,
        ]);

        $hits = $eventData->getParameter('result');
        $hitCount = $result['hits']['total']['value'] ?? 0;

        unset($result['hits']['hits']);

        $searchContainer->result->setData($hits);
        $searchContainer->result->addParameter('fullDatabaseResponse', $result);
        $searchContainer->result->setHitCount($hitCount);

        return $searchContainer;
    }
}
