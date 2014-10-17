<?php
namespace spec\watoki\fido\fixture;

use Composer\Util\ProcessExecutor;

class TestProcessExecutorMock extends ProcessExecutor {

    public $commands = array();

    public function execute($command, &$output = null, $cwd = null) {
        if ($cwd) {
            $this->commands[] = 'cd ' . $cwd;
        }
        $this->commands[] = $command;
        return 0;
    }

} 