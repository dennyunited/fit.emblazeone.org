<?php

namespace backend\modules\clients\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class SearchModel extends Model
{
    public $from,
        $to,
        $search,
        $per_page=20,
        $order;

    public static $pageSizeList=[
        '10'=>'10',
        '20'=>'20',
        '50'=>'50',
        '100'=>'100',
        '-1'=>'все'
    ];

    public function rules()
    {
        return [
            [['order', 'from', 'to', 'search','per_page'], 'safe']
        ];
    }

    public function search($modelName, $params)
    {
        $query = $modelName::find();

        $query->andWhere(['!=','id',\Yii::$app->user->identity->id]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'key' => 'id'
        ]);

//        $query = $this->getOrder($query);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        if($this->search){
            $query=$query->andWhere(['like','first_name',$this->search]);
            $query=$query->orWhere(['like','last_name',$this->search]);
        }
        if($this->per_page) $dataProvider->pagination->pageSize=$this->per_page;

        return $dataProvider;
    }

    public function attributeLabels()
    {
        return [
            'from' => 'Added From:',
            'to' => 'Added To:',
            'order' => \Yii::t('admin', 'Sort By:'),
        ];
    }
}