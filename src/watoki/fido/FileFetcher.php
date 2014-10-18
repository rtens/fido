<?php
namespace watoki\fido;

use Composer\Repository\PackageRepository;

class FileFetcher extends Fetcher {

    const TYPE = 'file';

    public function type() {
        return self::TYPE;
    }

    public function fetch($data, $name) {
        $this->composer->getRepositoryManager()->addRepository(new PackageRepository(array(
                'type' => 'package',
                'package' => array(
                        'name' => $name,
                        'version' => '1.0',
                        'dist' => array(
                                'url' => $data['source'],
                                'type' => $this->type()
                        )
                )
        )));

        return array($name . DIRECTORY_SEPARATOR . basename($data['source']) => $this->determineTarget($data));
    }

    private function determineTarget($data) {
        if (isset($data['value']) && $data['value'] != '*') {
            return $data['value'];
        } else if (isset($data['target'])) {
            return $data['target'];
        } else {
            return basename($data['source']);
        }
    }
}