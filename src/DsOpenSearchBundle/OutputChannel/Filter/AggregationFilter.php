<?php

namespace DsOpenSearchBundle\OutputChannel\Filter;

use DynamicSearchBundle\EventDispatcher\OutputChannelModifierEventDispatcher;
use DynamicSearchBundle\Filter\FilterInterface;
use DynamicSearchBundle\OutputChannel\Context\OutputChannelContextInterface;
use DynamicSearchBundle\OutputChannel\Query\Result\RawResultInterface;
use OpenSearchDSL\Aggregation\Bucketing\TermsAggregation;
use OpenSearchDSL\Query\Compound\BoolQuery;
use OpenSearchDSL\Query\TermLevel\TermQuery;
use OpenSearchDSL\Search;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AggregationFilter implements FilterInterface
{
    public const VIEW_TEMPLATE_PATH = '@DsOpenSearch/output-channel/filter';

    protected array $options;
    protected string $name;
    protected OutputChannelContextInterface $outputChannelContext;
    protected OutputChannelModifierEventDispatcher $eventDispatcher;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['label', 'show_in_frontend', 'add_as_post_filter', 'multiple', 'relation_label', 'field', 'size', 'query_type']);
        $resolver->setAllowedTypes('show_in_frontend', ['bool']);
        $resolver->setAllowedTypes('add_as_post_filter', ['bool']);
        $resolver->setAllowedTypes('multiple', ['bool']);
        $resolver->setAllowedTypes('label', ['string', 'null']);
        $resolver->setAllowedTypes('relation_label', ['closure', 'null']);
        $resolver->setAllowedTypes('field', ['string']);
        $resolver->setAllowedTypes('size', ['int']);
        $resolver->setAllowedTypes('query_type', ['string']);

        $resolver->setDefaults([
            'query_type'         => BoolQuery::MUST,
            'show_in_frontend'   => true,
            'add_as_post_filter' => false,
            'multiple'           => true,
            'relation_label'     => null,
            'label'              => null,
            'field'              => null,
            'size'               => 10,
        ]);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEventDispatcher(OutputChannelModifierEventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setOutputChannelContext(OutputChannelContextInterface $outputChannelContext): void
    {
        $this->outputChannelContext = $outputChannelContext;
    }

    public function supportsFrontendView(): bool
    {
        return $this->options['show_in_frontend'];
    }

    public function enrichQuery($query): mixed
    {
        if (!$query instanceof Search) {
            return $query;
        }

        $runtimeOptions = $this->outputChannelContext->getRuntimeOptions();
        $queryFields = $runtimeOptions['request_query_vars'];

        $termsAggregation = new TermsAggregation($this->name, $this->options['field']);
        $termsAggregation->addParameter('size', $this->options['size']);
        $query->addAggregation($termsAggregation);

        $this->addQueryFilter($query, $queryFields);

        return $query;
    }

    public function findFilterValueInResult(RawResultInterface $rawResult): mixed
    {
        // not supported?
        return null;
    }

    public function buildViewVars(RawResultInterface $rawResult, $filterValues, $query): ?array
    {
        $response = $rawResult->getParameter('fullDatabaseResponse');

        $viewVars = [
            'name' => $this->name,
            'template' => [sprintf('%s/%s.html.twig', self::VIEW_TEMPLATE_PATH, $this->name), sprintf('%s/aggregation.html.twig', self::VIEW_TEMPLATE_PATH)],
            'label'    => $this->options['label'],
            'multiple' => $this->options['multiple'],
            'values'   => [],
        ];

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

            if ($this->options['multiple'] === true && !is_array($value)) {
                continue;
            }

            if ($this->options['multiple'] === false && is_array($value)) {
                continue;
            }

            $value = $this->options['multiple'] === false ? [$value] : $value;

            $boolQuery = new BoolQuery();

            foreach ($value as $relationValue) {
                $relationQuery = new TermQuery($this->options['field'], $relationValue);
                $boolQuery->add($relationQuery, $this->options['query_type']);
            }

            if ($this->options['add_as_post_filter'] === true) {
                $query->addPostFilter($boolQuery);
            } else {
                $query->addQuery($boolQuery);
            }
        }
    }

    protected function buildResultArray(array $buckets): array
    {
        $runtimeOptions = $this->outputChannelContext->getRuntimeOptions();
        $queryFields = $runtimeOptions['request_query_vars'];
        $prefix = $runtimeOptions['prefix'];

        $fieldName = $this->name;

        $values = [];
        foreach ($buckets as $bucket) {

            $relationLabel = null;
            if ($this->options['relation_label'] !== null) {
                $relationLabel = call_user_func($this->options['relation_label'], $bucket['key'], $queryFields['locale'] ?? null);
            } else {
                $relationLabel = $bucket['key'];
            }

            $active = false;
            if (isset($queryFields[$fieldName])) {
                if ($this->options['multiple'] === true) {
                    $active = in_array($bucket['key'], $queryFields[$fieldName], true);
                } else {
                    $active = $bucket['key'] === $queryFields[$fieldName];
                }
            }

            $multiple = $this->options['multiple'] ? '[]' : '';

            $values[] = [
                'name'           => $bucket['key'],
                'form_name'      => $prefix !== null ? sprintf('%s[%s]%s', $prefix, $fieldName, $multiple) : sprintf('%s%s', $fieldName, $multiple),
                'value'          => $bucket['key'],
                'count'          => $bucket['doc_count'],
                'active'         => $active,
                'relation_label' => $relationLabel
            ];
        }

        return $values;
    }
}
