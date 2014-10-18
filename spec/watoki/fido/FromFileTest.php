<?php
namespace spec\watoki\fido;

use Composer\Config;
use spec\watoki\fido\fixture\ComposerFixture;
use spec\watoki\fido\fixture\FileFixture;
use watoki\scrut\Specification;

/**
 * @property FileFixture file <-
 * @property ComposerFixture $fix <-
 */
class FromFileTest extends Specification {

    public function background() {
        $this->fix->givenTheRemoteFile_Containing("http://example.com/some/file.txt", "Got me");
    }

    function testJustSource() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");

        $this->fix->thenTheOutputShouldContain('Installing fido/file_txt-3a011e75a5580c7726fd9bd3b6e66dbc (1.0)');
        $this->fix->thenTheOutputShouldContain('vendor/fido/file_txt-3a011e75a5580c7726fd9bd3b6e66dbc/file.txt -> assets/vendor/file.txt');
    }

    function testWithTarget() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.txt",
                        "target":"my/asset.js"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/asset.js", "Got me");

        $this->fix->thenTheOutputShouldContain('-> assets/vendor/my/asset.js');
    }

    function testOtherBaseDir() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "base-dir": "my/base",
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("my/base/file.txt", "Got me");

        $this->fix->thenTheOutputShouldContain('-> my/base/file.txt');
    }

    function testSourceAsKey() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "http://example.com/some/file.txt":{}
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

    function testTargetAsValue() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "http://example.com/some/file.txt":"my/target.txt"
              }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/my/target.txt", "Got me");
    }

    function testSpecifyType() {
        $this->fix->givenTheRemoteFile_Containing("http://example.com/some/file.git", "Got git");
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "some asset": {
                        "source":"http://example.com/some/file.git",
                        "type":"file"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.git", "Got git");
    }

    function testInRequire() {
        $this->fix->givenTheComposerJson('{
            "require": {
                "fido-fetch:http://example.com/some/file.txt":"*"
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/vendor/file.txt", "Got me");
    }

}