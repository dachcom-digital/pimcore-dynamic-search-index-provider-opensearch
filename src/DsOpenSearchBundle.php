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

namespace DsOpenSearchBundle;

use DynamicSearchBundle\Provider\Extension\ProviderBundleInterface;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

final class DsOpenSearchBundle extends AbstractPimcoreBundle implements ProviderBundleInterface
{
    public const PROVIDER_NAME = 'opensearch';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }
}
