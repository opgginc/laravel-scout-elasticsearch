<?php

namespace Matchish\ScoutElasticSearch\ElasticSearch;

use Laravel\Scout\Builder;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

final class SearchFactory
{
    /**
     * @param Builder $builder
     * @param array $options
     * @return Search
     */
    public static function create(Builder $builder, array $options = []): Search
    {
        $search = new Search();
        $query = new QueryStringQuery($builder->query);
        if (static::hasWhereFilters($builder)) {
            $boolQuery = new BoolQuery();
            if (! empty($builder->wheres)) {
                foreach ($builder->wheres as $field => $value) {
                    $boolQuery->add(new TermQuery((string) $field, $value), BoolQuery::FILTER);
                }
            }
            if (isset($builder->whereIns) && ! empty($builder->whereIns)) {
                foreach ($builder->whereIns as $field => $arrayOfValues) {
                    $boolQuery->add(new TermsQuery((string) $field, $arrayOfValues), BoolQuery::FILTER);
                }
            }
            $boolQuery->add($query, BoolQuery::MUST);
            $search->addQuery($boolQuery);
        } else {
            $search->addQuery($query);
        }
        if (array_key_exists('from', $options)) {
            $search->setFrom($options['from']);
        }
        if (array_key_exists('size', $options)) {
            $search->setSize($options['size']);
        }
        if (! empty($builder->orders)) {
            foreach ($builder->orders as $order) {
                $search->addSort(new FieldSort($order['column'], $order['direction']));
            }
        }

        return $search;
    }

    /**
     * @param Builder $builder
     * @return bool
     */
    private static function hasWhereFilters($builder): bool
    {
        return (! empty($builder->wheres) || (isset($builder->whereIns) && ! empty($builder->whereIns)));
    }
}
