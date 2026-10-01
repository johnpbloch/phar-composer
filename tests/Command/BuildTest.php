<?php

use Clue\PharComposer\Command\Build;
use Clue\PharComposer\Phar\Packager;
use Clue\PharComposer\Phar\PharComposer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildTest extends TestCase
{
    public function testCtorCreatesPackager()
    {
        $command = new Build();

        $ref = new ReflectionProperty($command, 'packager');
        $packager = $ref->getValue($command);

        $this->assertInstanceOf(Packager::class, $packager);
    }

    public function testExecuteBuildWillBuildPharer()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', null]
        ]);
        $output = $this->createStub(OutputInterface::class);

        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->never())->method('setTarget');
        $pharer->expects($this->once())->method('build');

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);

        $command = new Build($packager);
        $command->run($input, $output);
    }

    public function testExecuteBuildWillBuildPharerWithExplicitTarget()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))->method('getArgument')->willReturnMap([
            ['project', 'dir'],
            ['target', 'targetDir']
        ]);
        $output = $this->createStub(OutputInterface::class);

        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('setTarget')->with('targetDir');
        $pharer->expects($this->once())->method('build');

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);

        $command = new Build($packager);
        $command->run($input, $output);
    }
}
