<?php
namespace spec\rtens\fido;

use watoki\scrut\Specification;

/**
 * @property \spec\rtens\fido\fixture\ComposerFixture fix <-
 * @property \spec\rtens\fido\fixture\FileFixture file <-
 */
class DevModeTest extends Specification {

    public function background() {
        $this->fix->givenTheRemoteFile_Containing("http://example.com/some/file.txt", "Got me");
        $this->fix->givenTheRemoteFile_Containing("http://example.com/some/test.txt", "Got test");
    }

    function testIncludeDev() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch-dev": {
                    "some asset": {
                        "source":"http://example.com/some/test.txt"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("test/assets/vendor/test.txt", "Got test");
    }

    function testDifferentBaseDir() {
        $this->fix->givenTheComposerJson('{
            "extra": {
                "fido-fetch": {
                    "base-dir": "assets",
                    "some asset": {
                        "source":"http://example.com/some/file.txt"
                    }
                },
                "fido-fetch-dev": {
                    "base-dir": "spec",
                    "some test": {
                        "source":"http://example.com/some/test.txt"
                    }
                }
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("assets/file.txt", "Got me");
        $this->file->thenThereShouldBeAFile_Containing("spec/test.txt", "Got test");
    }

    function testIncludeDevRequires() {
        $this->fix->givenTheComposerJson('{
            "require-dev": {
                "fido-fetch:http://example.com/some/test.txt":"*"
            }
        }');
        $this->fix->whenIRunComposerWithThePlugin();
        $this->file->thenThereShouldBeAFile_Containing("test/assets/vendor/test.txt", "Got test");
    }

} 