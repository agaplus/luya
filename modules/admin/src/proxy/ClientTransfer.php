<?php

namespace luya\admin\proxy;

use yii\base\Object;
use Curl\Curl;
use luya\admin\file\Query;
use luya\traits\CacheableTrait;
use luya\helpers\FileHelper;

class ClientTransfer extends Object
{
    use CacheableTrait;
    
    /**
     * @var \luya\admin\proxy\ClientBuild
     */
    public $build;
    
    public function start()
    {
        $this->flushHasCache();
        
        foreach ($this->build->getTables() as $name => $table) {
            /* @var $table \luya\admin\proxy\ClientTable */
            if (!$table->isComplet()) {
                if ($this->build->optionStrict) {
                    $this->build->command->outputInfo('Rows Expected: ' . $table->getRows());
                    $this->build->command->outputInfo('Rows Downloaded: ' . count($table->getContentRows()));
                    return $this->build->command->outputError('Incomplet build, stop execution: ' . $name);
                }
            }
        }
        foreach ($this->build->getTables() as $name => $table) {
            $table->syncData();
        }
        
        // sync files
        foreach ((new Query())->all() as $file) {
            /* @var $file \luya\admin\file\Item */
            if (!$file->fileExists) {
                $curl = new Curl();
                $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
                $curl->get($this->build->fileProviderUrl, [
                    'buildToken' => $this->build->buildToken,
                    'machine' => $this->build->machineIdentifier,
                    'fileId' => $file->id,
                ]);
                
                if (!$curl->error) {
                    if (FileHelper::writeFile($file->serverSource, $curl->response)) {
                        $md5 = FileHelper::md5sum($file->serverSource);
                        if ($md5 == $file->getFileHash()) {
                            $this->build->command->outputInfo('[+] ' . $file->name . ' downloaded.');
                        } else {
                            $this->build->command->outputError('[!] ' . $file->name . ' error while downloading.');
                        }
                    }
                }
            }
        }
        
        // close the build
        
        $curl = new Curl();
        $curl->get($this->build->requestCloseUrl, ['buildToken' => $this->build->buildToken]);
        
        return true;
    }
}
