<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LoginLog */

$this->title = 'Create Login Log';
$this->params['breadcrumbs'][] = ['label' => 'Login Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="login-log-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
