<?php
$app->get('/account', function () use ($app) {
    $app->users = $app->db->fetch(
        'SELECT * FROM users WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );

    $app->account = $app->db->fetchAll(
        'SELECT * FROM account WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );

    return $app->render('views/account.phtml');
});

$app->get('/account/transactor', function () use ($app) {
    $app->transactorSrc = $app->db->fetchAll(
        'SELECT * FROM transactor WHERE dst_user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    $app->transactorDst = $app->db->fetchAll(
        'SELECT * FROM transactor WHERE src_user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    return $app->render('views/account-transactor.phtml');
});

$app->post('/account/transactor/generate', function () use ($app) {
    // transactor restricted during the campaign period :p
    if ($app->defaultBalance > 0) {
        $result = $app->db->fetch(
            'SELECT code FROM transactor WHERE dst_user_id = :user_id',
            [':user_id' => $_SESSION['user_id']]
        );
        if ($result !== false) {
            $app->redirect('/account/transactor#already+generate');
        }
    }

    while (true) {
        $code = bin2hex(openssl_random_pseudo_bytes(32));
        $result = $app->db->fetch(
            'SELECT code FROM transactor WHERE code = :code',
            [':code' => $code]
        );
        if ($result === false) {
            break;
        }
    }
    $app->db->query(
        'INSERT INTO transactor (dst_user_id, code) VALUES (:user_id, :code)',
        [':user_id' => $_SESSION['user_id'], ':code' => $code]
    );

    return $app->redirect('/account/transactor#success+generate');
});

$app->post('/account/transactor/register', function () use ($app) {
    $code = $app->input(INPUT_POST, 'code');
    if (preg_match('/\A[a-f0-9]{64}\z/i', $code) !== 1) {
        $app->redirect('/account/transactor#invalid+code');
    }

    $transactor = $app->db->fetch(
        'SELECT * FROM transactor WHERE code = :code',
        [':code' => $code]
    );
    if ($transactor === false || $transactor['src_user_id'] !== null) {
        $app->redirect('/account/transactor#invalid+code');
    }

    // transactor restricted during the campaign period :p
    if ($app->defaultBalance > 0) {
        $result = $app->db->fetch(
            'SELECT transactor_id FROM transactor WHERE src_user_id = :user_id',
            [':user_id' => $_SESSION['user_id']]
        );
        if ($result !== false) {
            $app->redirect('/account/transactor#already+register');
        }

        $registered = $app->db->fetch(
            'SELECT src_user_id FROM transactor WHERE dst_user_id = :user_id',
            [':user_id' => $_SESSION['user_id']]
        );
        if ($registered !== false
            && $registered['src_user_id'] !== null
            && $registered['src_user_id'] !== $transactor['dst_user_id']) {
            $app->redirect('/account/transactor#restrict+transactor');
        }

        $registered = $app->db->fetch(
            'SELECT dst_user_id FROM transactor WHERE src_user_id = :user_id',
            [':user_id' => $transactor['dst_user_id']]
        );
        if ($registered !== false && $registered['dst_user_id'] !== $_SESSION['user_id']) {
            $app->redirect('/account/transactor#restrict+transactor');
        }
    }

    $app->db->query(
        'UPDATE transactor SET src_user_id = :user_id WHERE code = :code',
        [':user_id' => $_SESSION['user_id'], ':code' => $code]
    );

    $app->redirect('/account/transactor#registerd+transactor');
});

$app->get('/account/transfer', function () use ($app) {
    $app->users = $app->db->fetch(
        'SELECT * FROM users WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );

    $app->transactor = $app->db->fetchAll(
        'SELECT * FROM transactor WHERE src_user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    return $app->render('views/account-transfer.phtml');
});

$app->post('/account/transfer', function () use ($app) {
    $transactorId = $app->input(INPUT_POST, 'transactor_id');
    if (is_numeric($transactorId) === false || $transactorId < 0) {
        $app->redirect('/account#invalid+transactor_id');
    }

    $amount = $app->input(INPUT_POST, 'amount');
    if (is_numeric($amount) === false || $amount < 0) {
        $app->redirect('/account#invalid+amount');
    }

    $users = $app->db->fetch(
        'SELECT balance FROM users WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    if ($amount > $users['balance']) {
        $app->redirect('/account#invalid+amount');
    }

    $transactor = $app->db->fetch(
        'SELECT dst_user_id FROM transactor WHERE transactor_id = :transactor_id AND src_user_id = :src_user_id',
        [':transactor_id' => $transactorId, ':src_user_id' => $_SESSION['user_id']]
    );
    if ($transactor === false) {
        $app->redirect('/account#not+exists+transactor');
    }

    $users = $app->db->fetch(
        'SELECT * FROM users WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    if ($users['balance'] < $amount) {
        $app->redirect('/account#invalid+amount');
    }

    // transaction
    try {
        $dstUsers = $app->db->fetch(
            'SELECT * FROM users WHERE user_id = :user_id',
            [':user_id' => $transactor['dst_user_id']]
        );

        // credit
        $newBalance = $dstUsers['balance'] + $amount;
        $notes = sprintf('%s remitted', $_SESSION['user_id']);
        $app->db->query(
            "UPDATE users SET balance = ${newBalance} WHERE user_id = :user_id",
            [':user_id' => $dstUsers['user_id']]
        );
        $app->db->query(
            "INSERT INTO account (user_id, debit, credit, notes) VALUES (:user_id, 0, ${amount}, :notes)",
            [':user_id' => $dstUsers['user_id'], ':notes' => $notes]
        );

        // debit
        $newBalance = $users['balance'] - $amount;
        $notes = sprintf('Remited to %s', $dstUsers['user_id']);
        $app->db->query(
            "UPDATE users SET balance = ${newBalance} WHERE user_id = :user_id",
            [':user_id' => $_SESSION['user_id']]
        );
        $app->db->query(
            "INSERT INTO account (user_id, debit, credit, notes) VALUES (:user_id, ${amount}, 0, :notes)",
            [':user_id' => $_SESSION['user_id'], ':notes' => $notes]
        );

        // logging
        $app->adminDb->query(
            "INSERT INTO admin_log (user_id, user_key, type, data, create_time) VALUES (:user_id, :user_key, 'transfer', ${amount}, NOW())",
            [':user_id' => $_SESSION['user_id'], ':user_key' => $_SESSION['user_key']]
        );

        $app->redirect('/account#success+transfer');
    } catch (Exception $e) {
        // logging
        $app->adminDb->query(
            "INSERT INTO admin_log (user_id, user_key, type, data, create_time) VALUES (:user_id, :user_key, 'message', :data, NOW())",
            [':user_id' => $_SESSION['user_id'], ':user_key' => $_SESSION['user_key'], ':data' => $e->getMessage()]
        );

        $app->redirect('/account#failed+transfer');
    }
});

$app->post('/account/report', function () use ($app) {
    $users = $app->db->fetch(
        'SELECT reported FROM users WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );
    if ($users['reported'] === '1') {
        return $app->redirect('/account#already+reported');
    }

    $app->db->query(
        'UPDATE users SET reported = 1 WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    );

    return $app->redirect('/account#success+report');
});

