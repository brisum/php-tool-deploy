<?php

namespace Brisum\Deploy\Lib\Client;

use Brisum\Lib\ObjectManager;

class ClientFactory
{
    protected $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $config
     * @return ClientInterface
     */
    public function create(array $config)
    {
        /** @var ClientInterface $client */
        $client = $this->objectManager->create(
            '\Brisum\Deploy\Lib\Client\\' . ucfirst($config['type']) . 'Client',
            ['config' => $config]
        );

        return $client;
    }
}
