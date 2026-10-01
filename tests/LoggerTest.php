<?php

use Clue\PharComposer\Logger;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    /**
     * instance to test
     *
     * @var Logger
     */
    private $logger;

    /**
     * set up test environment
     */
    #[Before]
    public function setUpLogger()
    {
        $this->logger = new Logger();
    }

    #[Test]
    public function echosToStdOutByDefault()
    {
        ob_start();
        $this->logger->log('some informational message');
        $this->assertEquals('some informational message' . PHP_EOL,
                            ob_get_contents()
        );
        ob_end_clean();
    }

    #[Test]
    public function callsGivenOutputFunctionWhenSet()
    {
        $this->logger->setOutput(function ($message) { $this->assertEquals('some informational message' . PHP_EOL, $message); });
        $this->logger->log('some informational message');
    }
}
