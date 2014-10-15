<?php
namespace watoki\fido;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

class FidoPlugin implements PluginInterface, EventSubscriberInterface {

    const REQUIRE_ASSETS_KEY = 'require-assets';

    /** @var string */
    private $root;

    /** @var \watoki\fido\Executor */
    private $executor;

    /** @var array */
    private $extra;

    /** @var IOInterface */
    private $io;

    function __construct($rootDir = '.', Executor $executor = null) {
        $this->root = $rootDir;
        $this->executor = $executor ? : new Executor();
    }

    public static function getSubscribedEvents() {
        return array(
                ScriptEvents::POST_UPDATE_CMD => array(
                        array('run', 0),
                ),
                ScriptEvents::POST_INSTALL_CMD => array(
                        array('run', 0),
                ),
        );
    }

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io) {
        $this->extra = $composer->getPackage()->getExtra();
        $this->io = $io;
    }

    public function run() {
        $baseDir = $this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'vendor';

        if (isset($this->extra[self::REQUIRE_ASSETS_KEY])) {
            foreach ($this->extra[self::REQUIRE_ASSETS_KEY] as $key => $value) {
                if ($key == 'base-dir') {
                    $baseDir = $this->root . DIRECTORY_SEPARATOR . $value;
                    continue;
                }

                $source = $key;
                if (isset($value['source'])) {
                    $source = $value['source'];
                }

                $type = substr($source, -4) == '.git' ? 'git' : 'file';
                if (isset($value['type'])) {
                    $type = $value['type'];
                }

                if ($type == 'git') {
                    $this->installRepository($baseDir, $source, $value);
                } else if ($type == 'file') {
                    $this->installFile($baseDir, $source, $value);
                } else {
                    throw new \Exception("Cannot require asset [$key] of type [$type]: Unkown type");
                }

                $this->io->write("      Done.");
            }
        }
    }

    private function installRepository($baseDir, $source, $data) {
        $name = substr(basename($source), 0, -4);

        if (is_string($data)) {
            $data = array(
                    'tag' => $data
            );
        }

        $target = $name;
        if (isset($data['target'])) {
            $target = $data['target'];
            $name = basename($target);
        }
        $targetDir = $baseDir . DIRECTORY_SEPARATOR . $target;

        if (file_exists($targetDir)) {
            $this->io->write("Fido: Updating $source ...");
            $gitCommand = "git pull origin master 2>&1 && cd ..";
        } else {
            $this->io->write("Fido: Cloning $source to $targetDir ...");
            $targetDir = dirname($targetDir);
            $gitCommand = "git clone $source $name 2>&1";
        }

        if (isset($data['tag'])) {
            $tag = $data['tag'];
            $this->io->write("      Using tag $tag");
            $gitCommand .= " && cd $name && git checkout $tag 2>&1";
        }

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $command = "cd $targetDir && " . $gitCommand;
        $this->executor->execute($command);
    }

    private function installFile($baseDir, $source, $data) {
        $target = basename($source);

        if (is_string($data)) {
            $data = array(
                    'target' => $data
            );
        }

        if (isset($data['target'])) {
            $target = $data['target'];
        }

        $file = $baseDir . DIRECTORY_SEPARATOR . $target;
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $this->io->write("Fido: Downloading $source to $file ...");
        file_put_contents($file, fopen($source, 'r'));
    }
}