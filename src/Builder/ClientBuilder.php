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

namespace DsOpenSearchBundle\Builder;

use DynamicSearchBundle\Logger\LoggerInterface;
use OpenSearch\Client;

class ClientBuilder implements ClientBuilderInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function build(array $indexOptions): Client
    {
        $client = \OpenSearch\ClientBuilder::create();
        $client->setHosts($indexOptions['index']['hosts']);

        $credentials = $indexOptions['index']['credentials'];

        if (!empty($credentials['username']) && $credentials['password']) {
            $client->setBasicAuthentication($credentials['username'], $credentials['password']);
        } else {
            if (!empty($credentials['sig_v4_region'])) {
                $client->setSigV4Region($credentials['sig_v4_region']);
            }

            if (!empty($credentials['sig_v4_service'])) {
                $client->setSigV4Service($credentials['sig_v4_service']);
            }

            if ($credentials['sig_v4_credential_provider'] !== null) {
                $client->setSigV4CredentialProvider($credentials['sig_v4_credential_provider']);
            }
        }

        if ($credentials['ssl_verification'] !== null) {
            $client->setSSLVerification($credentials['ssl_verification']);
        }

        return $client->build();
    }
}
