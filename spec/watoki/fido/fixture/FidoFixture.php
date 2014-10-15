<?php
namespace spec\watoki\fido\fixture;

use Composer\Composer;
use Composer\Config;
use Composer\IO\BufferIO;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use watoki\fido\FidoPlugin;
use watoki\scrut\Fixture;

/**
 * @property FileFixture file <-
 */
class FidoFixture extends Fixture {

    /** @var BufferIO */
    public $io;

    /** @var TestExecutorStub */
    public $executor;

    public function setUp() {
        parent::setUp();
        $this->executor = new TestExecutorStub();
    }

    public function givenTheComposerJson($json) {
        $json = $this->file->makeLocal($json);
        $data = json_decode($json, true);
        $data["name"] = "test/test";
        $data["version"] = "1.0";
        $this->file->givenTheFile_Containing('composer.json', json_encode($data));
    }

    public function whenIRunThePlugin() {
        $composer = new Composer();
        $this->io = new BufferIO();

        $file = new JsonFile($this->file->absolute('composer.json'));
        $localConfig = $file->read();

        $config = new Config();
        $config->merge($localConfig);
        $composer->setConfig($config);

        $loader  = new ArrayLoader();
        $package = $loader->load($localConfig, 'Composer\Package\RootPackage');

        $composer->setPackage($package);

        $plugin = new FidoPlugin($this->file->tmpDir, $this->executor);
        $plugin->activate($composer, $this->io);
    }

    public function thenTheOutputShouldBe($output) {
        $this->spec->assertEquals($output, str_replace("\n", "", $this->file->makeRooted($this->io->getOutput())));
    }

    public function thenItShouldExecute($string) {
        foreach ($this->executor->executedCommands as $command) {
            if ($this->file->makeRooted($command) == $string) {
                return;
            }
        }
        $this->spec->fail("Could not find [$string] in " . print_r($this->executor->executedCommands, true));
    }

} 