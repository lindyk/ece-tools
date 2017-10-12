<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

class Redis implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redisConfig = $this->environment->getRelationship('redis');
        $config = $this->configReader->read();

        if (count($redisConfig)) {
            $this->logger->info('Updating env.php Redis cache configuration.');
            $redisCache = [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => $redisConfig[0]['host'],
                    'port' => $redisConfig[0]['port'],
                    'database' => 1,
                ],
            ];
            $cacheConfig = [
                'frontend' => [
                    'default' => $redisCache,
                    'page_cache' => $redisCache,
                ],
            ];

            $config['cache'] = empty($config['cache'])
                ? $cacheConfig
                : array_replace_recursive($config['cache'], $cacheConfig);

            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $redisConfig[0]['host'],
                    'port' => $redisConfig[0]['port'],
                    'database' => 0,
                ],
            ];
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $this->configWriter->write($config);
    }

    /**
     * Clears configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisConfiguration($config)
    {
        $this->logger->info('Removing redis cache and session configuration from env.php.');

        if (isset($config['session']['save']) && $config['session']['save'] == 'redis') {
            $config['session']['save'] = 'db';
            if (isset($config['session']['redis'])) {
                unset($config['session']['redis']);
            }
        }

        if (isset($config['cache']['frontend'])) {
            foreach ($config['cache']['frontend'] as $cacheName => $cacheData) {
                if (isset($cacheData['backend']) && $cacheData['backend'] == 'Cm_Cache_Backend_Redis') {
                    unset($config['cache']['frontend'][$cacheName]);
                }
            }
        }

        return $config;
    }
}