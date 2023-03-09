<?php

namespace DsOpenSearchBundle;

use DynamicSearchBundle\Provider\Extension\ProviderBundleInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DsOpenSearchBundle extends Bundle implements ProviderBundleInterface
{
    public const PROVIDER_NAME = 'opensearch';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }
}
