<?php

use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Phar\Packager;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PackagerTest extends TestCase
{
    private $packager;

    #[Before]
    public function setUpPackager()
    {
        $this->packager = new Packager();
    }

    /**
     * @param string $expectedOutput
     * @param string $command
     */
    #[DataProvider('provideExecCommands')]
    public function testExec($expectedOutput, $command)
    {
        $this->expectOutputString(str_replace("\n", PHP_EOL, $expectedOutput));

        $this->packager->exec($command);
    }

    public static function provideExecCommands()
    {
        return [
            [
                "\n    output\n",
                'echo output'
            ],
            [
                "\n    error\n",
                'echo error>&2'
            ],
            [
                "\n    mixed\n    errors\n",
                'php -r ' . escapeshellarg('fwrite(STDOUT, \'mixed\' . PHP_EOL);fwrite(STDERR,\'errors\' . PHP_EOL);')
            ]
        ];
    }

    public function testEmptyNotInstalled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not installed');
        $this->packager->getPharer(__DIR__ . '/../fixtures/01-empty');
    }

    public function testNoComposer()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a readable file');
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer');
    }

    public function testNoComposerMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a readable file');
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer/composer.json');
    }

    public function testGetPharerTriesToExecuteGitStubInDirectoryWithSpaceAndThrowsWhenGitStubDoesNotCreateTargetDirectory()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $path = getenv('PATH');

        $temp = sys_get_temp_dir() . '/test phar-composer-' . mt_rand();
        mkdir($temp);
        symlink(exec('which echo'), $temp . '/git');

        putenv('PATH=' . $temp);

        try {
            $this->packager->setOutput(false);
            $this->packager->getPharer('user@git.example.com:user/project.git');

            $this->fail();
        } catch (Exception $e) {
            putenv('PATH=' . $path);
            unlink($temp . '/git');
            rmdir($temp);

            $this->assertStringMatchesFormat('Unable to parse given path "/%s/phar-composer%d/composer.json"', $e->getMessage());
        }
    }

    public function testGetSystemBinDefaultsToPackageNameInBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([
            'name' => 'clue/phar-composer'
        ], '');

        $this->assertEquals('/usr/local/bin/phar-composer', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryBinWhenNameIsNotSet()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([], __DIR__);

        $this->assertEquals('/usr/local/bin/Phar', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryRealNameInBinWhenNameIsNotSet()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([], __DIR__ . '/../');

        $this->assertEquals('/usr/local/bin/tests', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsCustomPackageInBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([
            'name' => 'clue/phar-composer'
        ], '');

        $this->assertEquals('/usr/local/bin/foo', $this->packager->getSystemBin($package, 'foo'));
    }

    public function testGetSystemBinReturnsCustomTargetPath()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([
            'name' => 'clue/phar-composer'
        ], '');

        $this->assertEquals('/home/me/foo', $this->packager->getSystemBin($package, '/home/me/foo'));
    }

    public function testGetSystemBinReturnsDefaultPackageNameInCustomBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package([
            'name' => 'clue/phar-composer'
        ], '');

        $this->assertEquals('/usr/bin/phar-composer', $this->packager->getSystemBin($package, '/usr/bin'));
    }

    public static function provideValidPackageUrl()
    {
        return [
            ['https://github.com/clue/phar-composer.git'],
            ['git@github.com:clue/phar-composer.git'],
            ['github.com:clue/phar-composer.git']
        ];
    }

    /**
     * @param string $path
     */
    #[DataProvider('provideValidPackageUrl')]
    public function testIsPackageUrlReturnsTrue($path)
    {
        $this->assertTrue($this->packager->isPackageUrl($path));
    }

    public static function provideInvalidPackageUrl()
    {
        return [
            ['clue/phar-composer'],
            ['clue/phar-composer:^1.0'],
            ['clue/phar-composer:~1.0'],
            ['clue/packagewithoutdashes'],
            ['clue/packagewithoutdashes:1.2.34'],
            ['clue/packagewithoutdashes:^1.2.34'],
            ['clue/packagewithoutdashes:~1.2.34'],
            ['phar-composer.git'],
            ['github.com/clue/phar-composer.git'],
            ['git @github.com:clue/phar-composer.git'],
            ['-invalid@github.com:clue/phar-composer.git'],
            [':clue/phar-composer.git'],
            ['/home/alice/Desktop/package/acme.json'],
            ['C:\Users\Alice\Desktop\package\acme.json']
        ];
    }

    /**
     * @param string $path
     */
    #[DataProvider('provideInvalidPackageUrl')]
    public function testIsPackageUrlReturnsFalseForInvalidUrl($path)
    {
        $this->assertFalse($this->packager->isPackageUrl($path));
    }
}
