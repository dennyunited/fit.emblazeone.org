<?php

namespace backend\modules\orders\models;


use backend\modules\products\models\Products;
use backend\modules\telegram\models\TelegramBot;
use common\models\Config;
use common\models\WbpActiveRecord;
use frontend\models\Cart;
use Yii;

class Orders extends WbpActiveRecord
{
    public static $times=[
        1=>'7-8',
        2=>'8-9',
        3=>'9-10',
        4=>'10-11',
    ];

    public static $seoKey = 'orders';
    public static $statuses=['Новый','В работе','Выполнен'];
    public static $payment_types=[1=>'Безналичная оплата на карту',2=>'Наличными курьеру'];
    public static $payment_statuses=['Не оплачен','Ожидаем оплаты','Оплачен'];

    public static function tableName()
    {
        return '{{%orders}}';
    }

    public function rules(){
        return [
            [['name','phone','email','street','house','appart','entrance','key_code','payment_type','notes','subscribe'],'safe', 'on'=>'cart'],
            [['name','phone','street','house','appart','date','time'],'required', 'on'=>'cart'],
            [['name','phone','email','street','house','appart','entrance','key_code','notes','subscribe','payment_status','status','payment_type','date','time'],'safe', 'on'=>['edit','add']],
        ];
    }

    public function getOrderItems(){
        return $this->hasMany(OrdersItems::className(),['order_id'=>'id']);
    }


    public function attributeLabels()
    {
        return [
            'name' =>           Yii::t('index', 'Имя'),
            'phone' =>          Yii::t('index','Телефон'),
            'email' =>          Yii::t('index','Почта'),
            'street' =>         Yii::t('index','Улица'),
            'house' =>          Yii::t('index','Дом'),
            'appart' =>         Yii::t('index','Квартира/Офис'),
            'entrance' =>       Yii::t('index','Подъезд'),
            'key_code' =>       Yii::t('index','Домофон'),
            'payment_type' =>   Yii::t('index','Оплата'),
            'notes' =>          Yii::t('index','Дополнительно'),
            'subscribe' =>      Yii::t('index','Подписка'),
            'payment_status' => Yii::t('index','Статус оплаты'),
            'status' =>         Yii::t('index','Статус заказа'),
            'date' =>           Yii::t('index','Дата доставки'),
            'time' =>           Yii::t('index','Время доставки'),
        ];
    }

    public function beforeSave($insert)
    {
        if($insert && $this->scenario=='cart'){
            $this->amount=Cart::getInstance()->getTotal();
            $this->discount=Cart::getInstance()->getDiscountPrice();
            if(Cart::getInstance()->getDiscount()) $this->discount_code=Cart::getInstance()->getDiscount()->code;
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($insert && $this->scenario=='cart'){
            foreach (Cart::getInstance()->getItems() as $num=>$item){
                $orderItem=new OrdersItems();
                $orderItem->order_id=$this->id;
                $orderItem->product_id=$item->product->id;
                $orderItem->title=$item->product->title;
                $orderItem->title_ua=$item->product->title_ua;
                $orderItem->size=$item->size;
                $orderItem->price=$item->price;
                $orderItem->save();
            }


            $telegramUsers=TelegramBot::find()->all();
            foreach ($telegramUsers as $telegramUser){
                $telegramUser->sendMessage(Yii::t('index',"Новый заказ").": \n".str_replace('<br />', "", $this->getOrderText()));
            }
            $this->sendEmail();
            $this->sendEmailClient();
        }
        return parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
    }

    public function getOrderItemsText(){
        $result=[];
        foreach ($this->orderItems as $orderItem){
            $result[]=$orderItem->title." (".Products::getLengthsDays()[$orderItem->size].")";
        }
        return implode('<br />', $result);
    }

    public function getOrderText(){
        return "
".Yii::t('index','Заказ')." №: ".sprintf("%06d", $this->id)."<br />
<br />
".Yii::t('index','Заказ').":<br />
".$this->getOrderItemsText()."<br />
".Yii::t('index','Сумма к оплате').": ".$this->amount."грн.<br />
<br />
".Yii::t('index','Контактные данные').":<br />
".Yii::t('index','Имя').": ".$this->name."<br />
".Yii::t('index','Телефон').": ".$this->phone."<br />
".Yii::t('index','Email').": ".$this->email."<br />
<br />
".Yii::t('index','Адрес доставки').":<br />
".Yii::t('index','Улица').": ".$this->street."<br />
".Yii::t('index','Дом').": ".$this->house."<br />
".Yii::t('index','Квартира/офис').": ".$this->appart."<br />
".Yii::t('index','Подъезд').": ".$this->entrance."<br />
".Yii::t('index','Домофон').": ".$this->key_code."<br />
<br />
".Yii::t('index','Дата доставки').": ".$this->date."<br />
".Yii::t('index','Время доставки').": ".Orders::$times[$this->time]."<br />
<br />
".Yii::t('index','Тип оплаты').":<br />
- ".Yii::t('index', Orders::$payment_types[$this->payment_type])."<br />
<br />
".Yii::t('index','Комментарий к заказу').":<br />
- ".$this->notes;
    }

    public function sendEmailClient()
    {
        if ($this->email){
            return Yii::$app->mailer->compose()
                ->setTo($this->email)
                ->setFrom(['info@healthy-kitchen.com.ua' => Config::getParameter('title', false)])
                ->setSubject(Yii::t('index',"Спасибо за заказ"))
                ->setHtmlBody("
                ".Yii::t('index','Вы осуществили заказ на сервисе здорового питания Healthy Kitchen.')."<br />
                ".Yii::t('index','Ваш заказ был принят и сейчас находится в обработке.')."<br />
                <br />" . $this->getOrderText())
                ->send();
        }else return false;
    }

    public function sendEmail()
    {
        return Yii::$app->mailer->compose()
            ->setTo(Config::getParameter('email', false))
            ->setFrom(['info@healthy-kitchen.com.ua' => Config::getParameter('title', false)])
            ->setSubject(Yii::t('index',"Новый заказ")." №".sprintf("%06d", $this->id))
            ->setHtmlBody("
                ".Yii::t('index',"Новы заказ был принят на сайте.")."<br />
                <br />".$this->getOrderText())
            ->send();
    }


}