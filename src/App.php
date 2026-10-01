<?php

namespace Clue\PharComposer;

use Symfony\Component\Console\Application as BaseApplication;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('phar-composer', '@dev');

        $this->addCommands([
            new Command\Build(),
            new Command\Search(),
            new Command\Install(),
        ]);

        $this->setDefaultCommand('search');
    }
}
