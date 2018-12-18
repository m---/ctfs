<?php
$app->get('/admin/log', function () use ($app) {
    $user_key = $app->input(INPUT_GET, 'user_key');
    $app->log = $app->adminDb->fetchAll(
        'SELECT * FROM admin_log WHERE user_key = :user_key',
        [':user_key' => $user_key]
    );

    return $app->render('views/admin-log.phtml');
});

$app->get('/admin/flag', function () use ($app) {
    return $app->flag3;
});

