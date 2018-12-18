<?php
$app->get('/', function () use ($app) {
    return $app->render('views/index.phtml');
});

$app->get('/register', function () use ($app) {
    return $app->render('views/register.phtml');
});

$app->post('/register', function () use ($app) {
    $userId = $app->input(INPUT_POST, 'user_id');
    $password = $app->input(INPUT_POST, 'password');

    if (strlen($userId) > 128) {
        return $app->redirect('/register#invalid+userid');
    }

    if (strlen($password) < 8 || $userId === $password) {
        return $app->redirect('/register#weak+password!+are+you+really+CTFer?:thinking_face:');
    }

    $users = $app->db->fetch(
        'SELECT password FROM users WHERE user_id = :user_id',
        [':user_id' => $userId]
    );
    if ($users !== false) {
        return $app->redirect('/register#invalid+userid');
    }

    $app->db->query(
        "INSERT INTO users (user_id, password, balance, reported) VALUES (:user_id, :password, {$app->defaultBalance}, 0)",
        [':user_id' => $userId, ':password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => 12])]
    );

    return $app->redirect('/login#success+register');
});

$app->get('/login', function () use ($app) {
    return $app->render('views/login.phtml');
});

$app->post('/login', function () use ($app) {
    $userId = $app->input(INPUT_POST, 'user_id');
    $password = $app->input(INPUT_POST, 'password');

    $users = $app->db->fetch(
        'SELECT password FROM users WHERE user_id = :user_id',
        [':user_id' => $userId]
    );
    if ($users === false || password_verify($password, $users['password']) === false) {
        return $app->redirect('/login#incorrect+userid+or+password');
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_key'] = hash_hmac('sha512', $userId, $app->secret);
    $app->redirect('/account#logged+in');
});

$app->get('/logout', function () use ($app) {
    unset($_SESSION['user_id']);
    session_destroy();
    return $app->redirect('/#logged+out');
});
