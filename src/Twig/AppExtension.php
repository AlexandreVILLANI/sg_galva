<?php

namespace App\Twig;

use App\Repository\ConfigurationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private ConfigurationRepository $configRepo)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_config', [$this, 'getConfig']),
        ];
    }

    public function getConfig(string $key): ?string
    {
        $config = $this->configRepo->findOneBy(['cle' => $key]);
        return $config ? $config->getValeur() : null;
    }
}
