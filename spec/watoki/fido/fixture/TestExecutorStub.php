<?php
namespace spec\watoki\fido\fixture;

use watoki\fido\Executor;

class TestExecutorStub extends Executor {

    public $executedCommands = array();

    public function execute($command) {
        $this->executedCommands[] = $command;
    }

}