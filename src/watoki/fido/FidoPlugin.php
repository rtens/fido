<?php
namespace watoki\fido;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

class FidoPlugin implements PluginInterface, EventSubscriberInterface {

    const EXTRA_REQUIRE_KEY = 'fido-fetch';

    const REQUIRE_PREFIX = 'fido-fetch:';

    const DEFAULT_BASE_DIR = 'assets/vendor';

    /** @var string */
    private $root;

    /** @var string */
    private $baseDir = self::DEFAULT_BASE_DIR;

    /** @var Composer */
    public $composer;

    /** @var IOInterface */
    private $io;

    /** @var array|string[] Targets to copy indexed by sources */
    public $targets = array();

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

        $fetches = array_merge(
                $this->getFetchesInExtra($package),
                $this->findFetchesInRequire($package));

        $this->processFetches($package, $fetches);
    }

    private function getFetchesInExtra(Package $package) {
        $fetches = array();
        $extra = $package->getExtra();
        if (!empty($extra[self::EXTRA_REQUIRE_KEY])) {
            $fetches = $extra[self::EXTRA_REQUIRE_KEY];
        };
        return $fetches;
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

    private function processFetches(Package $package, $fetches) {
        $this->setBaseDir($fetches);

        $requires = $package->getRequires();
        foreach ($fetches as $key => $data) {
            if ($key == 'base-dir') {
                continue;
            }

            $name = $this->fetch($key, $data);
            $requires[$name] = new Link($package->getName(), $name);
        }
        $package->setRequires($requires);
    }

    private function fetch($key, $data) {
        $data = $this->handleShortSyntax($key, $data);
        $name = $this->packageName($data['source']);

        try {
            $fetcher = $this->createFetcher($this->determineType($data));
            $this->targets += $fetcher->fetch($data, $name);
        } catch (\Exception $e) {
            throw new \Exception("Cannot fetch [$key]: " . $e->getMessage(), 0, $e);
        }

        return $name;
    }

    private function setBaseDir($fetches) {
        if (isset($fetches['base-dir'])) {
            $this->baseDir = $fetches['base-dir'];
        }
    }

    private function handleShortSyntax($key, $value) {
        if (is_string($value)) {
            $value = array(
                    'value' => $value
            );
        }

        if (!isset($value['source'])) {
            $value['source'] = $key;
        }

        return $value;
    }

    private function packageName($source) {
        return 'fido/' . str_replace('.', '_', basename($source)) . '-' . md5($source);
    }

    private function createFetcher($type) {
        foreach ($this->createFetchers() as $fetcher) {
            if ($fetcher->type() == $type) {
                return $fetcher;
            }
        }
        throw new \Exception("Unknown type [$type]");
    }

    /**
     * @return array|Fetcher[]
     */
    private function createFetchers() {
        return array(
                new FileFetcher($this->composer),
                new GitFetcher($this->composer)
        );
    }

    private function determineType($data) {
        if (isset($data['type'])) {
            return $data['type'];
        }
        return substr($data['source'], -4) == '.git' ? GitFetcher::TYPE : FileFetcher::TYPE;
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