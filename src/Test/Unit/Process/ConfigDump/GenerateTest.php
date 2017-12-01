<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\ConfigDump;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\ConfigDump\Generate;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class GenerateTest extends TestCase
{
    /**
     * @var Generate
     */
    private $process;

    /**
     * @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var FileList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileListMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var ArrayManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);

        $this->process = new Generate(
            $this->connectionMock,
            $this->fileListMock,
            $this->fileMock,
            $this->arrayManagerMock,
            ['modules']
        );
    }

    public function testExecute()
    {
        $expectedConfig = [
            'modules' => [
                'Magento_Store' => 1,
                'Magento_Directory' => 1,
            ],
            'admin_user' => [
                'locale' => [
                    'code' => ['fr_FR', 'ua_UA',],
                ],
            ],
        ];

        $this->fileListMock->method('getConfig')
            ->willReturn(__DIR__ . '/_files/app/etc/config.php');
        $this->arrayManagerMock->method('nest')
            ->willReturnOnConsecutiveCalls(
                [
                    'modules' => [
                        'Magento_Store' => 1,
                        'Magento_Directory' => 1,
                    ],
                ]
            );
        $this->connectionMock->method('select')
            ->with('SELECT DISTINCT `interface_locale` FROM `admin_user`')
            ->willReturn([
                ['interface_locale' => 'fr_FR'],
                ['interface_locale' => 'ua_UA'],
            ]);
        $this->fileMock->method('filePutContents')
            ->with(
                __DIR__ . '/_files/app/etc/config.php',
                '<?php' . "\n" . 'return ' . var_export($expectedConfig, true) . ";\n"
            );

        $this->process->execute();
    }
}