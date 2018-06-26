<?php

namespace app\controllers;

require_once(__DIR__ . '/../phpcas/CAS.php');

use app\models\DailyReport;
use app\models\Menu;
use Yii;
use yii\web\Response;
use yii\web\Controller;
use app\models\ContactForm;
use phpCAS;
use app\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\filters\CasFilter;
use app\models\LoginLog;
use Zhuzhichao\IpLocationZh\Ip;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use yii\web\UploadedFile;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use PhpOffice\PhpWord\Settings;

class SiteController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ]
                ],
            ],
            //            'cas' => [
            //                'class' => CasFilter::className(),
            //                'except' => ['index'],
            //            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

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
     * 登录流程，先跳转到cas-server,
     * 登录成功后再跳转回来进行一次login，保留了之前的rbac流程不变
     */
    public function actionLogin()
    {
        //获取登陆的用户名
        $username = phpCAS::getUser();
        $userInfo = Yii::$app->user->identity;
        if (is_object($userInfo) && $userInfo['username'] == $username) {
            Yii::$app->user->login($userInfo);
        } else {
            $userInfo = User::findByUsername($username);
            // 该用户是否是第一次进入系统,是的话帮他注册一下
            if (!$userInfo || !$userInfo->getId()) {
                $new_user = new User();
                $new_user->username = $username;
                $new_user->email = $username . '@wangyaoguang.com';
                $new_user->status = User::STATUS_ACTIVE;
                $new_user->save();
                Yii::$app->user->login($new_user);
            } else {
                Yii::$app->user->login($userInfo);
            }
        }


        // 登录以后记录一下登录日志
        $logInfo = new LoginLog();
        $logInfo->username = $username;
        $logInfo->ip = Yii::$app->request->getUserIP();
        $logInfo->area = implode(' ', Ip::find($logInfo->ip));
        $logInfo->browser = Yii::$app->request->getUserAgent();
        $logInfo->login_time = date('Y-m-d H:i:s');
        $logInfo->save();

        // 登录成功后跳转首页
        // Yii::$app->response->redirect('/site/index' , 301)->send();
        return $this->redirect('/site/index');
    }


    /**
     * Logout action.
     * @return bool
     */
    public function actionLogout()
    {
        $cas_server_url = Yii::$app->params['cas']['cas_host'] . ':' . Yii::$app->params['cas']['cas_port'] . Yii::$app->params['cas']['cas_context'] . '/logout?embed=true';
        $own_server_url = Yii::$app->request->getHostInfo();
        phpCAS::setServerLogoutURL('http://' . $cas_server_url . '&service=' . $own_server_url . '/');

        phpCAS::logout();
        Yii::$app->user->logout();
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
     * @api {get} /site/about 关于页面
     * @apiName actionAbout
     * @apiGroup Site
     *
     * @apiParam {Number} id
     *
     * @apiSuccess {String} firstname
     * @apiSuccess {String} lastname
     */
    public function actionAbout()
    {
        return $this->render('about');
    }


    /**
     * 导出 excel报表
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExportReport()
    {
        $username = Yii::$app->user->identity->username;
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator($username)
            ->setLastModifiedBy($username)
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $activeSheet = $spreadsheet->setActiveSheetIndex(0);

        // Rename worksheet
        $activeSheet->setTitle('日报');


        $xlsModel = new DailyReport();
        // 列名
        $cellArrays = $xlsModel->attributeLabels();
        // 列表数据
        $results = $xlsModel->getDailyReportByUserName($username);
        $row_num = 1;
        foreach ($results as $val) {
            $column_num = 1;
            foreach ($cellArrays as $key => $a) {
                if ($row_num == 1) {
                    // 追加写入列名,循环结束后row_num+2
                    $activeSheet->setCellValueByColumnAndRow($column_num, $row_num, $a);
                    $activeSheet->setCellValueByColumnAndRow($column_num, $row_num + 1, $val[$key]);
                } else {
                    $activeSheet->setCellValueByColumnAndRow($column_num, $row_num, $val[$key]);
                }
                $column_num++;
            }

            if ($row_num == 1) {
                $row_num = $row_num + 2;
            } else {
                $row_num++;
            }
        }

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dailyReport' . date('Ymd-His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        //         If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }


    /**
     * 递归获取树形菜单menu
     * @return string
     */
    public function actionMenuList()
    {
        $menuList = Menu::find()
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $t1 = microtime(true);
        $result = $this->getTree($menuList, 0);
        $t2 = microtime(true);
        // 计算一下执行时间
        return (($t2 - $t1) * 1000) . 'ms';
    }


    /**
     * 获取树形目录
     * @param $menuList
     * @param $pId
     * @return array
     */
    function getTree($menuList, $pId)
    {
        $tree = [];
        foreach ($menuList as $node) {
            $parentId = intval($node->parent);
            if ($parentId == $pId) {
                $menu = [
                    'id' => $node->id,
                    'name' => $node->name,
                    'route' => $node->route,
                    'parentId' => $parentId,
                    'childList' => $this->getTree($menuList, $node->id),
                ];
                $tree[] = $menu;
            }
        }
        return $tree;
    }


    public function actionExchangeWordToHtml()
    {
        // 上传路径是否存在
        $filePath = Yii::$app->basePath . '/uploads';
        if (!file_exists($filePath)) {
            mkdir($filePath);
        }

        // 获取上传的文件对象
        $tmp = UploadedFile::getInstanceByName('resumeFile');
        if ($tmp) {
            if ($tmp->size > 3 * 1024 * 1024) {
                return "file too large!";
            }
            if (!in_array($tmp->getExtension(), ['doc', 'docx', 'pdf', 'jpg', 'png'])) {
                return "格式不符合!";
            }
            $newPath = $filePath . '/' . $tmp->name;
            $tmp->saveAs($newPath);

            // phpWord读取并输出 因为目前只有word无法预览，所以直接处理转换
            try {
                if ($tmp->getExtension() == 'doc') {
                    $phpWord = PhpWordIOFactory::load($newPath, 'MsDoc');
                    // second, set PHPWord PDF rendering variables
                    Settings::setPdfRendererPath(__DIR__.'/../vendor/dompdf/dompdf');
                    Settings::setPdfRendererName("DomPDF");
                    set_time_limit(60);
                    echo date('H:i:s') . " Write to PDF format";
                    $objWriter = PhpWordIOFactory::createWriter($phpWord, 'PDF');
                    $objWriter->save($filePath . '/' . $tmp->getBaseName() . '.pdf');

                } else if ($tmp->getExtension() == 'docx') {
                    $phpWord = PhpWordIOFactory::load($newPath);
                    echo date('H:i:s') . " Write to HTML format";
                    $objWriter = PhpWordIOFactory::createWriter($phpWord, 'HTML');
                    $objWriter->save($filePath . '/' . $tmp->getBaseName() . '.html');
                }
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
        }
    }
}
