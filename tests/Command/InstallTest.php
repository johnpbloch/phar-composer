<?php

use Clue\PharComposer\Command\Install;
use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Phar\Packager;
use Clue\PharComposer\Phar\PharComposer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallTest extends TestCase
{
    public function testCtorCreatesPackager()
    {
        $command = new Install();

        $ref = new ReflectionProperty($command, 'packager');
        $packager = $ref->getValue($command);

        $this->assertInstanceOf(Packager::class, $packager);
    }

    public function testExecuteInstallWillInstallPackager()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', null]
        ]);
        $output = $this->createStub(OutputInterface::class);

        $package = $this->createStub(Package::class);
        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager, false);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWithExplicitTarget()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', 'targetDir']
        ]);
        $output = $this->createStub(OutputInterface::class);

        $package = $this->createStub(Package::class);
        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, 'targetDir')->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager, false);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionYieldsYes()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', null]
        ]);
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willReturn(true);

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $package = $this->createStub(Package::class);
        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->once())->method('install')->with($pharer, __FILE__);

        $command = new Install($packager, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillNotInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionShouldNotOverwrite()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', null]
        ]);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('Aborting');

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willReturn(false);

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $package = $this->createStub(Package::class);
        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->never())->method('install');

        $command = new Install($packager, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillReportErrorOnWindows()
    {
        $input = $this->createStub(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with($this->stringContains('platform'));

        $command = new Install(null, true);

        $this->assertStringEndsWith(' (not available on Windows)', $command->getDescription());

        $ret = $command->run($input, $output);

        $this->assertEquals(1, $ret);
    }
}
