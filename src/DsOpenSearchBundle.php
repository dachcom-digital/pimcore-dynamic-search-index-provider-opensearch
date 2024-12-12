<?php

namespace DsOpenSearchBundle;

use DynamicSearchBundle\Provider\Extension\ProviderBundleInterface;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
