<?php
namespace spec\watoki\fido;

use Composer\Config;
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

    public function background() {
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
        $this->fido->thenTheOutputShouldContain('Downloading $root/some/file.txt to $root/assets/vendor/file.txt');
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
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/asset.js", "Got me");
    }

    function testOtherBaseDir() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "base-dir": "my/base",
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("my/base/file.txt", "Got me");
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
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    function testTargetAsValue() {
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "http://example.com/some/file.txt":"my/target.txt"
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/target.txt", "Got me");
    }

    function testSpecifyType() {
        $this->file->givenTheRemoteFile_Containing("http://example.com/some/file.git", "Got git");
        $this->fido->givenTheComposerJson('{
            "extra":{
                "require-assets": {
                    "some asset": {
                        "source":"http://example.com/some/file.git",
                        "type":"file"
                    }
                }
            }
        }');
        $this->fido->whenIRunThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.git", "Got git");
    }

} 