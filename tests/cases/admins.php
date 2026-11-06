<?php

function test_admin_account_lifecycle()
{
    $admins = new AdminHandler();

    assertTrue($admins->addAccount('TestUser', 'secret', ADMIN_ADMIN, 'test-host', false));
    assertFalse($admins->addAccount('testuser', 'other', ADMIN_ADMIN, 'test-host', false));

    $user = 'testuser';
    $password = 'secret';
    assertTrue($admins->adminExists($user));
    assertTrue($admins->isPasswordCorrect($user, $password));

    $wrong = 'wrong';
    assertFalse($admins->isPasswordCorrect($user, $wrong));

    $info = $admins->getAdminInfo($user);
    assertSame(ADMIN_ADMIN, $info['accessFlags']);
    assertSame('test-host', $info['connection']);

    assertSame(
        md5('testuser:Prism administration:secret'),
        $admins->getRealmDigest($user)
    );

    assertTrue($admins->addAccessFlags($user, ADMIN_ACCESS, false));
    $info = $admins->getAdminInfo($user);
    assertSame(ADMIN_ADMIN | ADMIN_ACCESS, $info['accessFlags']);

    assertTrue($admins->revokeAccessFlags($user, ADMIN_ACCESS, false));
    $info = $admins->getAdminInfo($user);
    assertSame(ADMIN_ADMIN, $info['accessFlags']);

    ob_start();
    assertTrue($admins->changePassword($user, 'new-secret', false));
    ob_end_clean();
    $password = 'new-secret';
    assertTrue($admins->isPasswordCorrect($user, $password));

    assertTrue($admins->deleteAccount($user, false));
    assertFalse($admins->adminExists($user));
}

function test_admin_unknown_account()
{
    $admins = new AdminHandler();
    $user = 'nobody';
    $password = 'nothing';
    assertFalse($admins->adminExists($user));
    assertFalse($admins->isPasswordCorrect($user, $password));
    assertFalse($admins->getAdminInfo($user));
    assertFalse($admins->deleteAccount($user, false));
}

return array(
    'admins/account-lifecycle' => 'test_admin_account_lifecycle',
    'admins/unknown-account'   => 'test_admin_unknown_account',
);
