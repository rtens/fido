<?php
namespace rtens\fido;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class FidoPlugin implements PluginInterface, EventSubscriberInterface {

    const EXTRA_KEY = 'fido-fetch';

    const EXTRA_KEY_DEV = 'fido-fetch-dev';

    const REQUIRE_PREFIX = 'fido-fetch:';

    const DEFAULT_BASE_DIR = 'assets/vendor';

    const DEFAULT_BASE_DIR_DEV = 'test/assets/vendor';

    /** @var string */
    private $root;

    /** @var string */
    private $baseDir;

    /** @var string */
    private $baseDirDev;

    /** @var Composer */
    public $composer;

    /** @var IOInterface */
    private $io;

    /** @var array|string[] Targets to copy indexed by sources */
    public $targets = array();

    /** @var array Like targets, but for devMode */
    private $targetsDev = array();

    function __construct($rootDir = '.') {
        $this->root = $rootDir;
    }

    public static function getSubscribedEvents() {
        return array(
                ScriptEvents::PRE_UPDATE_CMD => array(
                        array('start', 0),
                ),
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
    }

    public function start(Event $e) {
        $package = $this->composer->getPackage();

        $fetches = array_merge(
                $this->getFetchesInExtra($package, self::EXTRA_KEY),
                $this->findFetchesInRequire($package));

        $this->baseDir = $this->determineBaseDir($fetches, self::DEFAULT_BASE_DIR);
        $this->targets = $this->processFetches($package, $fetches);

        if ($e->isDevMode()) {
            $devFetches = array_merge(
                    $this->getFetchesInExtra($package, self::EXTRA_KEY_DEV),
                    $this->findFetchesInRequireDev($package)
            );
            $this->baseDirDev = $this->determineBaseDir($devFetches, self::DEFAULT_BASE_DIR_DEV);
            $this->targetsDev = $this->processFetches($package, $devFetches);
        }
    }

    private function getFetchesInExtra(RootPackageInterface $package, $key) {
        $extra = $package->getExtra();
        if (empty($extra[$key])) {
            return array();
        };
        return $extra[$key];
    }

    private function findFetchesInRequire(RootPackageInterface $package) {
        $newFetches = array();
        $requires = array();

        $this->findFetches($package->getRequires(), $newFetches, $requires);

        $package->setRequires($requires);
        return $newFetches;
    }

    private function findFetchesInRequireDev(RootPackageInterface $package) {
        $newFetches = array();
        $requires = array();

        $this->findFetches($package->getDevRequires(), $newFetches, $requires);

        $package->setDevRequires($requires);
        return $newFetches;
    }

    private function findFetches($allRequires, &$newFetches, &$requires) {
        foreach ($allRequires as $name => $require) {
            /** @var Link $require */
            if (substr($name, 0, strlen(self::REQUIRE_PREFIX)) == self::REQUIRE_PREFIX) {
                $newFetches[substr($name, strlen(self::REQUIRE_PREFIX))] = $require->getPrettyConstraint();
            } else {
                $requires[$name] = $require;
            }
        }
    }

    private function determineBaseDir($fetches, $default) {
        if (isset($fetches['base-dir'])) {
            return $fetches['base-dir'];
        }
        return $default;
    }

    private function processFetches(RootPackageInterface $package, $fetches) {
        $targets = array();
        $requires = $package->getRequires();
        foreach ($fetches as $key => $data) {
            if ($key == 'base-dir') {
                continue;
            }

            $data = $this->handleShortSyntax($key, $data);
            $name = $this->packageName($data['source']);

            try {
                $fetcher = $this->createFetcher($this->determineType($data));
                $targets += $fetcher->fetch($data, $name);
            } catch (\Exception $e) {
                throw new \Exception("Cannot fetch [$key]: " . $e->getMessage(), 0, $e);
            }

            $requires[$name] = new Link($package->getName(), $name);
        }
        $package->setRequires($requires);

        return $targets;
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
        $this->finishWith($this->baseDir, $this->targets);
        $this->finishWith($this->baseDirDev, $this->targetsDev);
    }

    private function finishWith($baseDir, $targets) {
        if (!$baseDir) {
            return;
        }

        $this->clear($this->root . DIRECTORY_SEPARATOR . $baseDir);
        foreach ($targets as $source => $target) {
            $to = $baseDir . DIRECTORY_SEPARATOR . $target;
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