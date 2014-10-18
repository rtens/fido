<?php
namespace watoki\fido;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Composer\Repository\PackageRepository;
use Composer\Script\ScriptEvents;

class FidoPlugin implements PluginInterface, EventSubscriberInterface {

    const EXTRA_REQUIRE_KEY = 'fido-fetch';

    const REQUIRE_PREFIX = 'fido-fetch:';

    const DEFAULT_BASE_DIR = 'assets/vendor';

    const TYPE_GIT = 'git';

    const TYPE_FILE = 'file';

    /** @var string */
    private $root;

    /** @var string */
    private $baseDir = self::DEFAULT_BASE_DIR;

    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    /** @var array|string[] Targets to copy indexed by sources */
    private $targets = array();

    function __construct($rootDir = '.') {
        $this->root = $rootDir;
    }

    public static function getSubscribedEvents() {
        return array(
                ScriptEvents::POST_AUTOLOAD_DUMP => array(
                        array('finish', 0),
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
        $this->composer = $composer;
        $this->io = $io;

        $this->start();
    }

    public function start() {
        $package = $this->composer->getPackage();

        $extra = $package->getExtra();
        $fetches = array();
        if (!empty($extra[self::EXTRA_REQUIRE_KEY])) {
            $fetches = $extra[self::EXTRA_REQUIRE_KEY];
        };

        $fetches = array_merge($fetches, $this->findFetchesInRequire($package));

        $this->setBaseDir($fetches);

        $requires = $package->getRequires();
        foreach ($fetches as $key => $fetch) {
            if ($key == 'base-dir') {
                continue;
            }

            $source = $this->determineSource($key, $fetch);

            $name = $this->packageName($source);
            $requires[$name] = new Link($package->getName(), $name);

            $type = $this->determineType($source, $fetch);
            switch ($type) {
                case self::TYPE_FILE:
                    $this->fetchFile($fetch, $source, $name);
                    break;
                case self::TYPE_GIT:
                    $this->fetchGit($fetch, $source, $name);
                    break;
                default:
                    throw new \Exception("Cannot fetch [$key]: Unknown type [$type]");
            }
        }
        $package->setRequires($requires);
    }

    private function findFetchesInRequire(Package $package) {
        $newFetches = array();

        $requires = array();
        foreach ($package->getRequires() as $name => $require) {
            /** @var Link $require */
            if (substr($name, 0, strlen(self::REQUIRE_PREFIX)) == self::REQUIRE_PREFIX) {
                $newFetches[substr($name, strlen(self::REQUIRE_PREFIX))] = $require->getPrettyConstraint();
            } else {
                $requires[$name] = $require;
            }
        }
        $package->setRequires($requires);

        return $newFetches;
    }

    private function fetchFile($fetch, $source, $name) {
        $target = $this->determineFileTarget($fetch, $source);
        $this->targets[$name . DIRECTORY_SEPARATOR . basename($source)] = $target;

        $this->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => '1.0',
                        "dist" => array(
                                "url" => $source,
                                "type" => self::TYPE_FILE
                        )
                )
        )));
    }

    private function determineFileTarget($fetch, $source) {
        if (is_string($fetch) && $fetch != '*') {
            return $fetch;
        } else if (isset($fetch['target'])) {
            return $fetch['target'];
        } else {
            return basename($source);
        }
    }

    private function fetchGit($fetch, $source, $name) {
        $reference = $this->determineGitReference($fetch);
        $target = $this->determineGitTarget($fetch, $source);
        $this->targets[$name] = $target;

        $this->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => $reference ? : '1.0',
                        "source" => array(
                                "url" => $source,
                                "type" => self::TYPE_GIT,
                                "reference" => $reference ? : 'master'
                        )
                )
        )));
    }

    private function determineGitReference($fetch) {
        if (is_string($fetch)) {
            return $fetch;
        } else if (isset($fetch['reference'])) {
            return $fetch['reference'];
        } else {
            return null;
        }
    }

    private function determineGitTarget($fetch, $source) {
        if (isset($fetch['target'])) {
            return $fetch['target'];
        } else {
            return substr(basename($source), 0, -4);
        }
    }

    private function setBaseDir($fetches) {
        if (isset($fetches['base-dir'])) {
            $this->baseDir = $fetches['base-dir'];
        }
    }

    private function determineSource($key, $fetch) {
        if (isset($fetch['source'])) {
            return $fetch['source'];
        } else {
            return $key;
        }
    }

    private function determineType($source, $fetch) {
        if (isset($fetch['type'])) {
            return $fetch['type'];
        }
        return substr($source, -4) == '.git' ? self::TYPE_GIT : self::TYPE_FILE;
    }

    private function packageName($source) {
        return 'fido/' . str_replace('.', '_', basename($source)) . '-' . md5($source);
    }

    public function finish() {
        $this->io->write("<info>Copying fido's fetches</info>");
        $this->clear($this->root . DIRECTORY_SEPARATOR . $this->baseDir);
        foreach ($this->targets as $source => $target) {
            $to = $this->baseDir . DIRECTORY_SEPARATOR . $target;
            $from = $this->composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . $source;

            $this->io->write('  - ' . $from . ' -> ' . $to);
            $this->copy($from, $this->root . DIRECTORY_SEPARATOR . $to);
        }
    }

    private function copy($source, $target) {
        if (!is_dir($source)) {
            if (!file_exists(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($source, $target);
            return;
        }

        if (!file_exists($target)) {
            mkdir($target, 0777, true);
        }
        foreach (glob($source . '/*') as $file) {
            $this->copy($file, $target . DIRECTORY_SEPARATOR . basename($file));
        }
    }

    private function clear($file) {
        if (!file_exists($file)) {
            return;
        }
        if (is_dir($file)) {
            foreach (glob($file . '/*') as $child) {
                $this->clear($child);
            }
            rmdir($file);
        } else {
            unlink($file);
        }
    }
}