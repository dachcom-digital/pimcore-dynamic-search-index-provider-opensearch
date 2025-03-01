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

namespace DsOpenSearchBundle\OutputChannel\Filter;

use DynamicSearchBundle\Filter\FilterInterface;
use DynamicSearchBundle\OutputChannel\Query\Result\RawResultInterface;
use OpenSearchDSL\Aggregation\Bucketing\RangeAggregation;
use OpenSearchDSL\Query\TermLevel\RangeQuery;
use OpenSearchDSL\Search;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RangeAggregationFilter extends AggregationFilter implements FilterInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['mode', 'ranges']);
        $resolver->setAllowedTypes('mode', ['string']);
        $resolver->setAllowedValues('mode', ['gt', 'gte', 'lt', 'lte']);
        $resolver->setAllowedTypes('ranges', ['array']);

        $resolver->setDefaults([
            'mode'               => 'gte',
            'ranges'             => []
        ]);
    }

    public function enrichQuery($query): mixed
    {
        if (!$query instanceof Search) {
            return $query;
        }

        $runtimeOptions = $this->outputChannelContext->getRuntimeOptions();
        $queryFields = $runtimeOptions['request_query_vars'];

        if (!empty($this->options['ranges'])) {
            $rangeAggregation = new RangeAggregation($this->name, $this->options['field'], $this->options['ranges']);
            $query->addAggregation($rangeAggregation);
        }

        $this->addQueryFilter($query, $queryFields);

        return $query;
    }

    public function buildViewVars(RawResultInterface $rawResult, $filterValues, $query): ?array
    {
        $response = $rawResult->getParameter('fullDatabaseResponse');

        $viewVars = [
            'name'     => $this->name,
            'template' => [sprintf('%s/%s.html.twig', self::VIEW_TEMPLATE_PATH, $this->name), sprintf('%s/aggregation.html.twig', self::VIEW_TEMPLATE_PATH)],
            'label'    => $this->options['label'],
            'multiple' => $this->options['multiple'],
            'values'   => [],
        ];

        if (!isset($response['aggregations'][$this->name])) {
            return $viewVars;
        }

        if (count($response['aggregations'][$this->name]['buckets']) === 0) {
            return null;
        }

        $viewVars['values'] = $this->buildResultArray($response['aggregations'][$this->name]['buckets']);

        return $viewVars;
    }

    protected function addQueryFilter(Search $query, array $queryFields): void
    {
        if (count($queryFields) === 0) {
            return;
        }

        foreach ($queryFields as $key => $value) {
            if ($key !== $this->name) {
                continue;
            }

            $rangeQuery = new RangeQuery($this->options['field'], [
                $this->options['mode'] => $value
            ]);

            if ($this->options['add_as_post_filter'] === true) {
                $query->addPostFilter($rangeQuery);
            } else {
                $query->addQuery($rangeQuery);
            }
        }
    }
}
