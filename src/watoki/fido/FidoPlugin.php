<?php
namespace watoki\fido;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;
use Composer\Repository\PackageRepository;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class FidoPlugin implements PluginInterface, EventSubscriberInterface {

    const EXTRA_REQUIRE_KEY = 'fido-fetch';

    const REQUIRE_PREFIX = 'fido-fetch:';

    const DEFAULT_BASE_DIR = 'assets/vendor';

    const TYPE_GIT = 'git';

    const TYPE_FILE = 'file';

    private $baseDir;

    /** @var string */
    private $root;

    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    /** @var array|string[] Targets to copy indexed by sources */
    private $targets = array();

    private $devs = array();

    function __construct($rootDir = '.') {
        $this->root = $rootDir;
    }

    public static function getSubscribedEvents() {
        return array(
                ScriptEvents::PRE_UPDATE_CMD=> array(
                        array('pre', 0),
                ),
                ScriptEvents::PRE_INSTALL_CMD=> array(
                        array('pre', 0),
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

        $this->start();
    }

    public function start() {
        $package = $this->composer->getPackage();

        $extra = $package->getExtra();
        $fetches = !empty($extra[self::EXTRA_REQUIRE_KEY]) ? $extra[self::EXTRA_REQUIRE_KEY] : array();

        $requires = array();
        foreach ($package->getRequires() as $name => $require) {
            /** @var Link $require */
            if (substr($name, 0, strlen(self::REQUIRE_PREFIX)) == self::REQUIRE_PREFIX) {
                $fetches[substr($name, strlen(self::REQUIRE_PREFIX))] = $require->getPrettyConstraint();
            } else {
                $requires[$name] = $require;
            }
        }

        $devRequires = array();
        $devs = array();
        foreach ($package->getDevRequires() as $name => $require) {
            if (substr($name, 0, strlen(self::REQUIRE_PREFIX)) == self::REQUIRE_PREFIX) {
                $version = $require->getPrettyConstraint();
                $key = substr($name, strlen(self::REQUIRE_PREFIX));
                $fetches[$key] = $version ? : array();
                $devs[] = $key;
            } else {
                $devRequires[$name] = $require;
            }
        }
        $package->setDevRequires($devRequires);

        $this->baseDir = self::DEFAULT_BASE_DIR;
        if (isset($fetches['base-dir'])) {
            $this->baseDir = $fetches['base-dir'];
        }
        foreach ($fetches as $key => $fetch) {
            if ($key == 'base-dir') {
                continue;
            }

            $source = $key;
            if (isset($fetch['source'])) {
                $source = $fetch['source'];
            }
            $name = 'fido/' . str_replace('.', '_', basename($source)) . '-' . md5($source);
            $requires[$name] = new Link($package->getName(), $name);

            if (in_array($key, $devs)) {
                $this->devs[] = $name;
            }

            $type = substr($source, -4) == '.git' ? self::TYPE_GIT : self::TYPE_FILE;
            if (isset($fetch['type'])) {
                $type = $fetch['type'];
            }

            if ($type == self::TYPE_FILE) {
                if (is_string($fetch) && $fetch != '*') {
                    $target = $fetch;
                } else if (isset($fetch['target'])) {
                    $target = $fetch['target'];
                } else {
                    $target = basename($source);
                }
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
            } else if ($type == self::TYPE_GIT) {
                if (is_string($fetch)) {
                    $reference = $fetch;
                } else if (isset($fetch['reference'])) {
                    $reference = $fetch['reference'];
                } else {
                    $reference = null;
                }

                if (isset($fetch['target'])) {
                    $target = $fetch['target'];
                } else {
                    $target = substr(basename($source), 0, -4);
                }
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
            } else {
                throw new \Exception("Cannot require asset [$key] of type [$type]: Unkown type");
            }
        }

        $package->setRequires($requires);
    }

    public function pre(Event $event) {
        if ($event->isDevMode()) {
            return;
        }

        $requires = $this->composer->getPackage()->getRequires();
        foreach ($requires as $name => $link) {
            if (in_array($name, $this->devs)) {
                unset($requires[$name]);
            }
        }
        var_dump(array_keys($requires));
        $this->composer->getPackage()->setRequires($requires);
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
        if (!file_exists($source)) {
            return;
        }
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