<?php
namespace watoki\fido;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class FidoPlugin implements PluginInterface {

    const REQUIRE_ASSETS_KEY = 'require-assets';

    private $root;

    private $executor;

    function __construct($rootDir = '.', Executor $executor = null) {
        $this->root = $rootDir;
        $this->executor = $executor ? : new Executor();
    }

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io) {
        $extra = $composer->getPackage()->getExtra();

        $baseDir = $this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'vendor';

        if (isset($extra[self::REQUIRE_ASSETS_KEY])) {
            foreach ($extra[self::REQUIRE_ASSETS_KEY] as $key => $value) {
                if (substr($key, -4) == '.git') {
                    $name = substr(basename($key), 0, -4);
                    $dir = $baseDir . DIRECTORY_SEPARATOR . $name;

                    if (file_exists($dir)) {
                        $gitCommand = "git pull origin master";
                    } else {
                        $dir = $baseDir;
                        $gitCommand = "git clone " . $key;
                    }

                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $this->executor->execute("cd $dir && " . $gitCommand);
                } else {
                    $file = $baseDir . DIRECTORY_SEPARATOR . basename($key);
                    if (!file_exists(dirname($file))) {
                        mkdir(dirname($file), 0777, true);
                    }
                    file_put_contents($file, fopen($key, 'r'));
                }
            }
        }
    }
}