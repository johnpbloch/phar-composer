<?php

use Clue\PharComposer\Package\Bundle;
use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Phar\PharComposer;
use Clue\PharComposer\Phar\TargetPhar;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class TargetPharTest extends TestCase
{
    #[Test]
    public function addFileCalculatesLocalPartForBox()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPharComposer = $this->createMock(PharComposer::class);
        $mockPharComposer->expects($this->once())
                         ->method('getPathLocalToBase')
                         ->with($this->equalTo('path/to/package/file.php'))
                         ->willReturn('file.php');
        $mockPhar->expects($this->once())
                 ->method('addFile')
                 ->with($this->equalTo('path/to/package/file.php'), $this->equalTo('file.php'));

        $targetPhar = new TargetPhar($mockPhar, $mockPharComposer);
        $targetPhar->addFile('path/to/package/file.php');
    }

    #[Test]
    public function buildFromIteratorProvidesBasePathForBox()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPharComposer = $this->createMock(PharComposer::class);
        $mockPackage = new Package([], 'path/to/package');
        $mockTraversable = $this->createStub(\Iterator::class);
        $mockPharComposer->expects($this->once())
                         ->method('getPackageRoot')
                         ->willReturn($mockPackage);
        $mockPhar->expects($this->once())
                 ->method('buildFromIterator')
                 ->with($this->equalTo($mockTraversable), $this->equalTo('path/to/package/'));

        $targetPhar = new TargetPhar($mockPhar, $mockPharComposer);
        $targetPhar->buildFromIterator($mockTraversable);
    }

    #[Test]
    public function addPackageAddsResourcesFromCalculatedBundle()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPharComposer = $this->createMock(PharComposer::class);
        $bundle = new Bundle();
        $bundle->addFile('path/to/package/file.php');
        $mockPharComposer->expects($this->once())
                         ->method('getPathLocalToBase')
                         ->with($this->equalTo('path/to/package/file.php'))
                         ->willReturn('file.php');
        $mockPhar->expects($this->once())
                 ->method('addFile')
                 ->with($this->equalTo('path/to/package/file.php'), $this->equalTo('file.php'));
        $mockFinder = $this->createStub(Finder::class);
        $bundle->addDir($mockFinder);
        $mockPackage = new Package([], 'path/to/package');
        $mockPharComposer->expects($this->once())
                         ->method('getPackageRoot')
                         ->willReturn($mockPackage);
        $mockPhar->expects($this->once())
                 ->method('buildFromIterator')
                 ->with($this->equalTo($mockFinder), $this->equalTo('path/to/package/'));

        $targetPhar = new TargetPhar($mockPhar, $mockPharComposer);
        $targetPhar->addBundle($bundle);
    }

    #[Test]
    public function setsStubOnUnderlyingPhar()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPhar->expects($this->once())
                 ->method('setStub')
                 ->with($this->equalTo('some stub code'));

        $targetPhar = new TargetPhar($mockPhar, $this->createStub(PharComposer::class));
        $targetPhar->setStub('some stub code');
    }

    #[Test]
    public function stopBufferingStopsBufferingOnUnderlyingPhar()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPhar->expects($this->once())
                 ->method('stopBuffering');

        $targetPhar = new TargetPhar($mockPhar, $this->createStub(PharComposer::class));
        $targetPhar->stopBuffering();
    }

    #[Test]
    public function addFromStringOnUnderlyingPhar()
    {
        $mockPhar = $this->createMock(MockablePhar::class);
        $mockPhar->expects($this->once())
                 ->method('addFromString')
                 ->with('path/file', 'contents');

        $targetPhar = new TargetPhar($mockPhar, $this->createStub(PharComposer::class));
        $targetPhar->addFromString('path/file', 'contents');
    }
}

/**
 * PHPUnit cannot reflect a default value for the internal `Phar::setStub()`
 * `$length` parameter and would generate an implicitly nullable signature,
 * which is deprecated as of PHP 8.4. Redeclaring it here gives the test
 * double a proper signature to copy.
 */
class MockablePhar extends \Phar
{
    public function setStub($stub, int $length = -1): true
    {
        return parent::setStub($stub, $length);
    }
}
