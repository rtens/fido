<?php
namespace spec\watoki\fido\fixture;

use watoki\scrut\Fixture;

class FileFixture extends Fixture {

    public $tmpDir;

    public function setUp() {
        parent::setUp();
        $this->tmpDir = __DIR__ . DIRECTORY_SEPARATOR . '__tmp';

        if (!file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        $that = $this;
        $this->spec->undos[] = function () use ($that) {
            $that->clear($that->tmpDir);
        };
    }

    public function givenTheFile_Containing($file, $content) {
        $this->putContent($this->absolute($file), $content);
    }

    public function givenTheDirectory($path) {
        $path = $this->absolute($path);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    public function thenThereShouldBeAFile_Containing($file, $content) {
        $fullPath = $this->absolute($file);
        $this->spec->assertFileExists($fullPath);
        $this->spec->assertEquals($content, file_get_contents($fullPath));
    }

    public function thenThereShouldBeADirectory($path) {
        $this->spec->assertFileExists($this->absolute($path));
    }

    public function clear($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->clear($file);
            } else {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    public function absolute($path) {
        return $this->tmpDir . DIRECTORY_SEPARATOR . $path;
    }

    public function makeLocal($file) {
        return str_replace('http://example.com', $this->tmpDir, $file);
    }

    public function makeRooted($string) {
        return str_replace($this->tmpDir . DIRECTORY_SEPARATOR, '', $string);
    }

    private function putContent($file, $content) {
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $content);
    }

} 