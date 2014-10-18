<?php
namespace watoki\fido;

abstract class Fetcher {

    /** @var \watoki\fido\FidoPlugin */
    protected $plugin;

    function __construct(FidoPlugin $plugin) {
        $this->plugin = $plugin;
    }

    abstract public function type();

    abstract public function fetch($fetch, $source, $name);

} 