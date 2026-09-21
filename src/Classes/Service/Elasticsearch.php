<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    private Client $client;

    public function __construct(array $hosts = ['localhost:9200'])
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function createIndex(string $index, array $settings = [], array $mappings = []): array
    {
        return $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => $settings,
                'mappings' => $mappings,
            ],
        ]);
    }

    public function deleteIndex(string $index): array
    {
        return $this->client->indices()->delete(['index' => $index]);
    }

    public function indexDocument(string $index, string $id, array $body): array
    {
        return $this->client->index([
            'index' => $index,
            'id'    => $id,
            'body'  => $body,
        ]);
    }

    public function getDocument(string $index, string $id): array
    {
        return $this->client->get([
            'index' => $index,
            'id'    => $id,
        ]);
    }

    public function deleteDocument(string $index, string $id): array
    {
        return $this->client->delete([
            'index' => $index,
            'id'    => $id,
        ]);
    }

    public function search(string $index, array $query): array
    {
        return $this->client->search([
            'index' => $index,
            'body' => $query,
        ]);
    }

    public function bulk(array $operations): array
    {
        return $this->client->bulk(['body' => $operations]);
    }

    public function getMapping(string $index): array
    {
        return $this->client->indices()->getMapping(['index' => $index]);
    }

    public function scroll(string $scrollId, string $scroll = '1m'): array
    {
        return $this->client->scroll([
            'scroll_id' => $scrollId,
            'scroll' => $scroll,
        ]);
    }

    public function indexExists(string $index): bool
    {
        return $this->client->indices()->exists(['index' => $index]);
    }

    public function documentExists(string $index, string $id): bool
    {
        return $this->client->exists([
            'index' => $index,
            'id' => $id,
        ]);
    }

    public function aggregate(string $index, array $aggs): array
    {
        return $this->client->search([
            'index' => $index,
            'body' => [
                'size' => 0,
                'aggs' => $aggs
            ]
        ]);
    }
}