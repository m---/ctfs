<?php
session_start();

require_once('libs/CTF.php');
require_once('libs/DB.php');

$app = new CTF(__DIR__);

// config
$app->secret = $_ENV['USER_KEY_SECRET'];
$app->flag2 = 'CBCTF{y0U_ar3_r1Ch,_ple453_6iv3_m0n3y_70_ADm1n_w0rk1nG_4t_Ch34p_w463s}'; // Can't guess
$app->flag3 = 'CBCTF{1\'d_likE_5,o00,000,0O0,000,0Oo_yeN!_4nD_Y0u?}'; // Can't guess
$app->recaptchaSite = '6LfZ4mUUAAAAADzms_S8Yjm6ju13KjRH7FJMe6IA';
$app->recaptchaSecret = '***CENSORED***'; // Can't guess
$app->adminIp = gethostbyname($_ENV['ADMIN_HOST']);
$app->defaultBalance = 100000000;

$app->db = new DB($_ENV['DB_HOST'], 'mage', 'password', 'mage', 3);
$app->adminDb = new DB($_ENV['DB_HOST'], 'admin', 'password', 'mage');
$app->isLogin = isset($_SESSION['user_id']);

// pre route
$app->{'*'}('*', function ($path) use ($app) {
    if (strpos($path, '/admin') === 0) {
        if ($_SERVER['REMOTE_ADDR'] !== $app->adminIp) {
            $app->notfound();
        }
    }

    if (strpos($path, '/account') === 0) {
        if ($app->isLogin === false) {
            $app->redirect('/login#required+authentication');
        }
    }
});

$app->post('*', function ($path) use ($app) {
    if (strpos($path, '/account') === 0) {
        $response = $app->input(INPUT_POST, 'g-recaptcha-response');
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $query = http_build_query([
            'secret' => $app->recaptchaSecret,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
            'response' => $response
        ]);
        $result = json_decode(file_get_contents($url . '?' . $query));
        if ($result === null || $result->success === false) {
            $app->redirect('/#missing+recaptcha');
        }
    }
});

// route
include('routes/default.php');
include('routes/account.php');
include('routes/admin.php');

$app->run();
