<?php
namespace watoki\fido;

class Executor {

    public function execute($command) {
        exec($command);
    }
}