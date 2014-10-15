<?php
namespace spec\watoki\fido;

use Composer\Composer;
use Composer\Config;
use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use watoki\fido\FidoPlugin;
use watoki\scrut\Specification;

class DownloadFileTest extends Specification {

    function testJustFileName() {
        $this->givenTheFile_Containing("http://example.com/some/file.txt", "Got me");
        $this->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "http://example.com/some/file.txt":{}
                }
            }
        }');
        $this->whenIRunThePlugin();
        $this->thenTheOutputShouldBe(
                'Fido: Downloading $root/some/file.txt ...' .
                'Fido: Done.');
        $this->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    function testCloneRepository() {
        $this->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "https://example.com/some/repo.git":{}
                }
            }
        }');
        $this->whenIRunThePlugin();
        $this->thenTheOutputShouldBe(
                'Fido: Cloning https://example.com/some/repo.git ...' .
                'Fido: Done.');
        $this->thenThereShouldBeADirectory('assets/vendor');
        $this->thenItShouldExecute('cd $root/assets/vendor && git clone https://example.com/some/repo.git');
    }

    function testUpdateRepository() {
        $this->givenTheDirectory('assets/vendor/repo');
        $this->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "https://example.com/some/repo.git":{}
                }
            }
        }');
        $this->whenIRunThePlugin();
        $this->thenTheOutputShouldBe(
                'Fido: Updating https://example.com/some/repo.git ...' .
                'Fido: Done.');
        $this->thenItShouldExecute('cd $root/assets/vendor/repo && git pull origin master');
    }

    #############################################################################################

    /** @var BufferIO */
    private $io;

    private $tmpDir;

    private $composerFile;

    /** @var TestExecutorStub */
    private $executor;

    protected function setUp() {
        parent::setUp();
        $this->tmpDir = __DIR__ . DIRECTORY_SEPARATOR . '__tmp';
        $this->composerFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'composer.json';
        @mkdir($this->tmpDir, 0777, true);

        $this->executor = new TestExecutorStub();
    }

    protected function tearDown() {
        $this->clear($this->tmpDir);
        parent::tearDown();
    }

    private function clear($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->clear($file);
            } else {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    private function givenTheFile_Containing($file, $content) {
        $path = $this->makeLocal($file);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }

    private function givenTheDirectory($path) {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function givenTheComposerJson($json) {
        $json = $this->makeLocal($json);
        $data = json_decode($json, true);
        $data["name"] = "test/test";
        $data["version"] = "1.0";
        file_put_contents($this->composerFile, json_encode($data));
    }

    private function whenIRunThePlugin() {
        $composer = new Composer();
        $this->io = new BufferIO();

        $file = new JsonFile($this->composerFile);
        $localConfig = $file->read();

        $config = new Config();
        $config->merge($localConfig);
        $composer->setConfig($config);

        $loader  = new ArrayLoader();
        $package = $loader->load($localConfig, 'Composer\Package\RootPackage');

        $composer->setPackage($package);

        $plugin = new FidoPlugin($this->tmpDir, $this->executor);
        $plugin->activate($composer, $this->io);
    }

    private function thenThereShouldBeAFile_Containing($file, $content) {
        $fullPath = $this->tmpDir . DIRECTORY_SEPARATOR . $file;
        $this->assertFileExists($fullPath);
        $this->assertEquals($content, file_get_contents($fullPath));
    }

    private function thenThereShouldBeADirectory($path) {
        $fullPath = $this->tmpDir . DIRECTORY_SEPARATOR . $path;
        $this->assertFileExists($fullPath);
    }

    private function thenItShouldExecute($string) {
        foreach ($this->executor->executedCommands as $command) {
            if ($this->makeRooted($command) == $string) {
                return;
            }
        }
        $this->fail("Could not find [$string] in " . print_r($this->executor->executedCommands, true));
    }

    private function thenTheOutputShouldBe($output) {
        $this->assertEquals($output, str_replace("\n", "", $this->makeRooted($this->io->getOutput())));
    }

    private function makeLocal($file) {
        return str_replace('http://example.com', $this->tmpDir, $file);
    }

    private function makeRooted($command) {
        return str_replace($this->tmpDir, '$root', $command);
    }

} 