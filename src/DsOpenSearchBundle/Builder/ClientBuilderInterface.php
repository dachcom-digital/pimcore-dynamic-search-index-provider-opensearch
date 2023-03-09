<?php

namespace DsOpenSearchBundle\Builder;

use OpenSearch\Client;

interface ClientBuilderInterface
{
    public function build(array $indexOptions): Client;
}
