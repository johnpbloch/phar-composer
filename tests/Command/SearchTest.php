<?php

use Clue\PharComposer\Command\Search;
use Clue\PharComposer\Phar\Packager;
use Clue\PharComposer\Phar\PharComposer;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Version;
use Packagist\Api\Result\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SearchTest extends TestCase
{
    public function testCtorCreatesPackagerAndPackagist()
    {
        $command = new Search();

        $ref = new ReflectionProperty($command, 'packager');
        $packager = $ref->getValue($command);

        $ref = new ReflectionProperty($command, 'packagist');
        $packagist = $ref->getValue($command);

        $this->assertInstanceOf(Packager::class, $packager);
        $this->assertInstanceOf(Client::class, $packagist);
    }

    public function testExecuteWithoutProjectWillAskForProjectAndRunSearch()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn(null);
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willReturn('foo');

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectWillRunSearchWithoutAskingForProject()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndSearchReturnsNoMatchesWillReportAndAskForOtherProject()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->willReturnCallback($this->expectConsecutiveLines(
            'Searching for <info>foo</info>...',
            '<error>No matching packages found</error>'
        ));

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([]);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndSearchReturnsOneMatchWillAskForProject()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectSelectedWillSearchVersions()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))->method('writeln')->willReturnCallback($this->expectConsecutiveLines(
            'Searching for <info>foo</info>...',
            'Selected <info>foo/bar</info>, listing versions...'
        ));

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->once())->method('ask')->willReturn(
            '<info>foo</info>/bar                                  (⤓0)'
        );

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillQuitWhenAskedForActionYieldsQuit()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓0)',
            'dev-master (<error>no executable bin</error>)',
            'Quit'
        );

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->createMock(Version::class);
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->createMock(Package::class);
        $package->expects($this->once())->method('getVersions')->willReturn([$version]);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillBuildWhenAskedForActionYieldsBuild()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓0)',
            'dev-master (<error>no executable bin</error>)',
            'Build project'
        );

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('build');

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->createMock(Version::class);
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->createMock(Package::class);
        $package->expects($this->once())->method('getVersions')->willReturn([$version]);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillInstallWhenAskedForActionYieldsInstall()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓0)',
            'dev-master (<error>no executable bin</error>)',
            'Install project system-wide'
        );

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $package = $this->createStub(\Clue\PharComposer\Package\Package::class);
        $pharer = $this->createMock(PharComposer::class);
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);
        $pharer->expects($this->never())->method('build');

        $packager = $this->createMock(Packager::class);
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->createMock(Version::class);
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->createMock(Package::class);
        $package->expects($this->once())->method('getVersions')->willReturn([$version]);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedOnWindowsWillNotOfferInstallWhenAskedForAction()
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->createStub(OutputInterface::class);

        $answers = [
            '<info>foo</info>/bar                                  (⤓0)',
            'dev-master (<error>no executable bin</error>)',
            'Quit'
        ];
        $questions = [];
        $questionHelper = $this->createMock(QuestionHelper::class);
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnCallback(
            function ($_, $__, ChoiceQuestion $question) use (&$answers, &$questions) {
                $questions[] = $question;
                return array_shift($answers);
            }
        );

        $helpers = new HelperSet([
            'question' => $questionHelper
        ]);

        $packager = $this->createStub(Packager::class);

        $result = $this->createMock(Result::class);
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->createMock(Version::class);
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->createMock(Package::class);
        $package->expects($this->once())->method('getVersions')->willReturn([$version]);

        $packagist = $this->createMock(Client::class);
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn([$result]);
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, true);
        $command->setHelperSet($helpers);
        $command->run($input, $output);

        $this->assertCount(2, $questions[2]->getChoices());
    }

    /**
     * Returns a callback asserting it is invoked with the given lines in order
     */
    private function expectConsecutiveLines(string ...$lines): Closure
    {
        return function ($line) use (&$lines) {
            $this->assertSame(array_shift($lines), $line);
        };
    }
}
