<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Login Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="login-log-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Login Log', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'login_time',
            'ip',
            'username',
            'area',
            //'browser',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
