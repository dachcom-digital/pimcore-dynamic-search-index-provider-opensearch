<?php

namespace DsOpenSearchBundle\Builder;

use DynamicSearchBundle\Logger\LoggerInterface;
use OpenSearch\Client;

class ClientBuilder implements ClientBuilderInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {}

    public function build(array $indexOptions): Client
    {
        $client = \OpenSearch\ClientBuilder::create();
        $client->setHosts($indexOptions['index']['hosts']);

        if (!empty($indexOptions['index']['credentials']['username']) && $indexOptions['index']['credentials']['password']) {
            $client->setBasicAuthentication($indexOptions['index']['credentials']['username'], $indexOptions['index']['credentials']['password']);
        }

        return $client->build();
    }
}
