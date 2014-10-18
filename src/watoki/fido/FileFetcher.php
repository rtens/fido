<?php
namespace watoki\fido;

use Composer\Repository\PackageRepository;

class FileFetcher extends Fetcher {

    const TYPE = 'file';

    public function type() {
        return self::TYPE;
    }

    public function fetch($fetch, $source, $name) {
        $this->plugin->targets[$name . DIRECTORY_SEPARATOR . basename($source)] = $this->determineTarget($fetch, $source);
        $this->plugin->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => '1.0',
                        'dist' => array(
                                'url' => $source,
                                'type' => $this->type()
                        )
                )
        )));
    }

    private function determineTarget($fetch, $source) {
        if (is_string($fetch) && $fetch != '*') {
            return $fetch;
        } else if (isset($fetch['target'])) {
            return $fetch['target'];
        } else {
            return basename($source);
        }
    }
}