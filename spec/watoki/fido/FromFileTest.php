<?php
namespace spec\watoki\fido;

use Composer\Config;
use spec\watoki\fido\fixture\FidoFixture;
use spec\watoki\fido\fixture\FileFixture;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property FidoFixture fido <-
 */
class FromFileTest extends Specification {

    public function background() {
        $this->fido->givenTheRemoteFile_Containing("http://example.com/some/file.txt", "Got me");
    }

    function testJustSource() {
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");

        $this->fido->thenTheOutputShouldContain('Installing fido/file_txt-3a011e75a5580c7726fd9bd3b6e66dbc (1.0)');
        $this->fido->thenTheOutputShouldContain('vendor/fido/file_txt-3a011e75a5580c7726fd9bd3b6e66dbc/file.txt -> assets/vendor/file.txt');
    }

    function testWithTarget() {
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt",
                        "target":"my/asset.js"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/asset.js", "Got me");

        $this->fido->thenTheOutputShouldContain('-> assets/vendor/my/asset.js');
    }

    function testOtherBaseDir() {
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "base-dir": "my/base",
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("my/base/file.txt", "Got me");

        $this->fido->thenTheOutputShouldContain('-> my/base/file.txt');
    }

    function testSourceAsKey() {
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "http://example.com/some/file.txt":{}
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    function testTargetAsValue() {
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "http://example.com/some/file.txt":"my/target.txt"
              }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/target.txt", "Got me");
    }

    function testSpecifyType() {
        $this->fido->givenTheRemoteFile_Containing("http://example.com/some/file.git", "Got git");
        $this->fido->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.git",
                        "type":"file"
                    }
                }
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.git", "Got git");
    }

    function testInRequire() {
        $this->fido->givenTheComposerJson('{
            "require": {
                "fido-fetch:http://example.com/some/file.txt":"*"
            }
        }');
        $this->fido->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

}