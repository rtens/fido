<?php
namespace spec\watoki\fido;

use Composer\Composer;
use Composer\Config;
use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use spec\watoki\fido\fixture\FidoFixture;
use spec\watoki\fido\fixture\FileFixture;
use spec\watoki\fido\fixture\TestExecutorStub;
use watoki\fido\FidoPlugin;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property FidoFixture fido <-
 */
class FromFileTest extends Specification {

    protected function background() {
        $this->file->givenTheRemoteFile_Containing("http://example.com/some/file.txt", "Got me");
    }

    function testJustSource() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Downloading $root/some/file.txt to file.txt ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    function testWithTarget() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt",
                        "target":"my/asset.js"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Downloading $root/some/file.txt to my/asset.js ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/asset.js", "Got me");
    }

    function testSourceAsKey() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "http://example.com/some/file.txt":{}
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->fido->thenTheOutputShouldBe(
                'Fido: Downloading $root/some/file.txt to file.txt ...' .
                'Fido: Done.');
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    #############################################################################################

} 