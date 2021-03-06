<?php

namespace luya\admin\models;

use luya\admin\file\Item;
use luya\admin\ngrest\base\NgRestModel;
use luya\admin\Module;

/**
 * This is the model class for table "admin_storage_filter".
 *
 * @property integer $id
 * @property string $identifier
 * @property string $name
 */
final class StorageFilter extends NgRestModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_storage_filter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['identifier'], 'required'],
            [['identifier'], 'string', 'max' => 100],
            [['name'], 'string', 'max' => 255],
            [['identifier'], 'unique'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'identifier' => Module::t('model_storagefilter_identifier'),
            'name' => Module::t('model_storagefilter_name'),
        ];
    }

    /**
     * Remove image sources of the file.
     */
    public function removeImageSources()
    {
        foreach (StorageImage::find()->where(['filter_id' => $this->id])->all() as $img) {
            $img->deleteSource();
        }
    }
    
    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            foreach (StorageImage::find()->where(['filter_id' => $this->id])->all() as $img) {
                $img->delete();
            }
            return true;
        }
        
        return false;
    }

    /**
     * Apply the current filter chain.
     *
     * @param \luya\admin\file\Item $file
     * @param unknown $fileSavePath
     * @return boolean
     */
    public function applyFilterChain(Item $file, $fileSavePath)
    {
        $loadFrom = $file->getServerSource();
        
        foreach (StorageFilterChain::find()->where(['filter_id' => $this->id])->joinWith('effect')->all() as $chain) {
            $response = $chain->applyFilter($loadFrom, $fileSavePath);
            $loadFrom = $fileSavePath;
        }
        
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function ngRestApiEndpoint()
    {
        return 'api-admin-filter';
    }

    /**
     * @inheritdoc
     */
    public function ngRestAttributeTypes()
    {
        return [
            'name' => 'text',
            'identifier' => 'text',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function ngRestConfig($config)
    {
        $this->ngRestConfigDefine($config, 'list', ['name', 'identifier']);
        
        return $config;
    }
}
