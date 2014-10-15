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
                if ($key == 'base-dir') {
                    $baseDir = $this->root . DIRECTORY_SEPARATOR . $value;
                    continue;
                }

                $source = $key;
                if (isset($value['source'])) {
                    $source = $value['source'];
                }

                if (substr($source, -4) == '.git') {
                    $name = substr(basename($source), 0, -4);
                    $dir = $baseDir . DIRECTORY_SEPARATOR . $name;

                    if (file_exists($dir)) {
                        $io->write("Fido: Updating $source ...");
                        $gitCommand = "git pull origin master";
                    } else {
                        $io->write("Fido: Cloning $source ...");
                        $dir = $baseDir;
                        $gitCommand = "git clone " . $source;
                    }

                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $this->executor->execute("cd $dir && " . $gitCommand);
                } else {
                    $target = basename($source);
                    if (isset($value['target'])) {
                        $target = $value['target'];
                    }

                    $file = $baseDir . DIRECTORY_SEPARATOR . $target;
                    if (!file_exists(dirname($file))) {
                        mkdir(dirname($file), 0777, true);
                    }
                    $io->write("Fido: Downloading $source to $file ...");
                    file_put_contents($file, fopen($source, 'r'));
                }

                $io->write("Fido: Done.");
            }
        }
    }
}