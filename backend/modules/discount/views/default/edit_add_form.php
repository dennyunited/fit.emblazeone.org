<?

use backend\assets\AppAsset;
use common\models\Identity;
use kartik\date\DatePicker;

\kartik\date\DatePickerAsset::register($this);
$bundle=AppAsset::register($this);

if(Yii::$app->request->isAjax  && !Yii::$app->request->get('_pjax')) $this->registerJs('
        $("#owners-form").on("beforeSubmit",function(){
            var form = $(this);
            if (form.find(".has-error").length) return false;
            
            $.ajax({
                url    : form.attr(\'action\'),
                type   : \'post\',
                data   : form.serialize(),
                success: function (response) {
                    $("body").append(response);
                },
                error  : function () {
                    console.log(\'internal server error\');
                }
            });
            return false;
        });
    ',\yii\web\View::POS_END);

$form=\yii\bootstrap\ActiveForm::begin(['id'=>'owners-form']);

?>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <?=$form->field($model,'code')->textInput()?>
            </div>
            <div class="col-md-3">
                <?=$form->field($model,'value')->textInput()?>
            </div>
            <div class="col-md-3">
                <?=$form->field($model,'type')->dropDownList([1=>'Сумма (грн)', 2=>'Процент (%)'])?>
            </div>
            <div class="col-md-3">
                <div class="form-group m-b-0">
                    <label class="control-label"><?=$model->attributeLabels()['status']?></label>
                </div>
                <?=$form->field($model,'status')->checkbox()->label('Активная')?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?=$form->field($model,'start_date')->widget(DatePicker::className(),[
                    'options'=>['class'=>'form-control', 'autocomplete'=>"off"],
                    'language' => 'ru',
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                    ]
                ])?>
            </div>
            <div class="col-md-4">
                <?=$form->field($model,'stop_date')->widget(DatePicker::className(),[
                    'options'=>['class'=>'form-control', 'autocomplete'=>"off"],
                    'language' => 'ru',
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                    ]
                ])?>
            </div>
        </div>
    </div>
    <div class="card-footer text-right">
        <?=\yii\helpers\Html::submitButton('Применить',['class'=>'btn btn-danger'])?>
    </div>
</div>

<? \yii\bootstrap\ActiveForm::end(); ?>

<?
if(Yii::$app->request->isAjax){ echo \wbp\uniqueOverlay\UniqueOverlay::script(); }
?>
