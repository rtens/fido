<?php
namespace rtens\fido;

use Composer\Composer;

abstract class Fetcher {

    /** @var \Composer\Composer */
    protected $composer;

    function __construct(Composer $composer) {
        $this->composer = $composer;
    }

    /**
     * @return string
     */
    abstract public function type();

    /**
     * @param array $data
     * @param string $name
     * @return array Files to be copied: <source> => <target>
     */
    abstract public function fetch($data, $name);

} 