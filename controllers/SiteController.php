<?php

namespace app\controllers;

// Load the CAS lib
require_once __DIR__ . '/../vendor/jasig/phpcas/CAS.php';


use Yii;
use yii\web\Response;
use yii\base\Controller;
use app\models\ContactForm;
use phpCAS;

class SiteController extends BaseController
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('home', ['name' => '首页', 'message' => '这里是测试的首页']);
    }


    /**
     * Logout action.
     * @return bool
     */
    public function actionLogout()
    {
        phpCAS::client(
            CAS_VERSION_2_0,
            Yii::$app->params['cas']['cas_host'],
            Yii::$app->params['cas']['cas_port'],
            Yii::$app->params['cas']['cas_context']
        );

        $cas_server_url = Yii::$app->params['cas']['cas_host'].':'.Yii::$app->params['cas']['cas_port'].Yii::$app->params['cas']['cas_context'].'/logout?embed=true';
        $own_home_url = Yii::$app->getHomeUrl();
        phpCAS::setServerLogoutUrl('http://'.$cas_server_url.'&service='.$own_home_url);
        //no SSL validation for the CAS server
        phpCAS::setNoCasServerValidation();
        phpCAS::logout();
        return true;
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
