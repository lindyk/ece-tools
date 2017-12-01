<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Unit test for build-time static content compressor.
 */
class CompressStaticContentTest extends TestCase
{
    /**
     * @var CompressStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildConfigMock = $this->getMockBuilder(BuildConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->compressorMock = $this->getMockBuilder(StaticContentCompressor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->buildConfigMock,
            $this->compressorMock
        );
    }

    /**
     * Test build-time compression.
     */
    public function testExecute()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(BuildConfig::OPT_SCD_COMPRESSION_LEVEL, CompressStaticContent::COMPRESSION_LEVEL)
            ->willReturn(6);
        $this->environmentMock
            ->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->compressorMock
            ->expects($this->once())
            ->method('process')
            ->with(6);

        $this->process->execute();
    }

    /**
     * Test that build-time compression will fail appropriately.
     */
    public function testExecuteNoCompress()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}