<?php

namespace cmstests\src\menu;

use Yii;
use cmstests\CmsFrontendTestCase;

class QueryTest extends CmsFrontendTestCase
{
    public function testContainerSelector()
    {
        Yii::$app->composition['langShortCode'] = 'fr';
        Yii::$app->menu->setLanguageContainer('fr', include('_dataFrArray.php'));
        
        $containers = ['footer-column-one', 'footer-column-two', 'footer-column-three'];
        
        foreach ($containers as $container) {
            foreach (Yii::$app->menu->find()->where(['container' => $container])->all() as $item) {
                $link = $item->link;
                
                if (is_null($link)) {
                    $this->assertNull($link); // internal self redirect
                } else {
                    $this->assertNotEmpty($link);
                }
            }
        }
    }
    
    private function generateItems($start, $end, $lang)
    {
        $data = [];
        foreach (range($start, $end) as $number) {
            $data[$number] = [
                    'id' => $number,
                    'nav_id' => $number,
                    'lang' => $lang,
                    'link' => '/public_html/' . $number,
                    'title' => 'Page-'. $number,
                    'alias' => 'page-' . $number,
                    'description' => null,
                    'keywords' => null,
                    'create_user_id' => 1,
                    'update_user_id' => 1,
                    'timestamp_create' => '1457091369',
                    'timestamp_update' => '1483367249',
                    'is_home' => 0,
                    'parent_nav_id' => '0',
                    'sort_index' => $number,
                    'is_hidden' => 0,
                    'type' => 1,
                    'nav_item_type_id' => $number,
                    'redirect' => false,
                    'module_name' => false,
                    'container' => 'default',
                    'depth' => 1,
            ];
        }
        
        return $data;
    }
    
    public function testWhereOperatorException()
    {
        Yii::$app->composition['langShortCode'] = 'en';
        Yii::$app->menu->setLanguageContainer('en', $this->generateItems(1, 10, 'en'));
        $this->expectException('luya\cms\Exception');
        $result = Yii::$app->menu->find()->where(['>', 'id', 4, 'error'])->all();
    }
    
    public function testWhereOperatorComperators()
    {
        Yii::$app->composition['langShortCode'] = 'en';
        Yii::$app->menu->setLanguageContainer('en', $this->generateItems(1, 10, 'en'));
        $this->assertSame(5, Yii::$app->menu->find()->where(['>', 'id', 5])->count());
        $this->assertSame(6, Yii::$app->menu->find()->where(['>=', 'id', 5])->count());
        $this->assertSame(4, Yii::$app->menu->find()->where(['<', 'id', 5])->count());
        $this->assertSame(5, Yii::$app->menu->find()->where(['<=', 'id', 5])->count());
        $this->assertSame(1, Yii::$app->menu->find()->where(['=', 'id', 5])->count());
        $this->assertSame(1, Yii::$app->menu->find()->where(['==', 'id', 5])->count());
        $this->assertSame(0, Yii::$app->menu->find()->where(['==', 'id', "5"])->count());
    }
    
    public function testAndWhereOperatorComperators()
    {
        Yii::$app->composition['langShortCode'] = 'en';
        Yii::$app->menu->setLanguageContainer('en', $this->generateItems(1, 10, 'en'));
        $this->assertSame(1, Yii::$app->menu->find()->where(['=', 'id', 5])->andWhere(['is_hidden' => 0])->count());
        $this->assertSame(0, Yii::$app->menu->find()->where(['=', 'id', 5])->andWhere(['lang' => 'another'])->count());
    }
    
    public function testLimitAndOffset()
    {
        Yii::$app->composition['langShortCode'] = 'en';
        Yii::$app->menu->setLanguageContainer('en', $this->generateItems(1, 20, 'en'));
        $data = Yii::$app->menu->find()->limit(5)->offset(10)->all();
        $i = 10;
        $this->assertSame(5, count($data));
        foreach ($data as $item) {
            $i++;
            $this->assertSame($i, $item->id);
        }
    }
}
