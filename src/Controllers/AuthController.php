<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function showRegister(array $params): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }
        View::render('auth/register', ['pageTitle' => t('auth.create_account')]);
    }

    public function register(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $errors = [];

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors['username'] = t('error.username_format');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('error.email_invalid');
        }
        if (strlen($password) < 8) {
            $errors['password'] = t('error.password_short');
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = t('error.passwords_mismatch');
        }

        if (!$errors && User::findByUsername($username)) {
            $errors['username'] = t('error.username_taken');
        }
        if (!$errors && User::findByEmail($email)) {
            $errors['email'] = t('error.email_taken');
        }

        if ($errors) {
            View::render('auth/register', [
                'pageTitle' => t('auth.create_account'),
                'errors'    => $errors,
                'old'       => compact('username', 'email'),
            ]);
            return;
        }

        $userId = User::create($username, $email, $password);
        Auth::login($userId);
        Session::flash('success', t('flash.welcome', ['site' => t('site.name'), 'user' => $username]));
        Response::redirect('/');
    }

    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }
        View::render('auth/login', ['pageTitle' => t('auth.welcome_back')]);
    }

    public function login(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();

        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $errors     = [];

        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? User::findByEmail($identifier)
            : User::findByUsername($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['form'] = t('error.invalid_credentials');
        }

        if ($errors) {
            View::render('auth/login', [
                'pageTitle' => t('auth.welcome_back'),
                'errors'    => $errors,
                'old'       => ['identifier' => $identifier],
            ]);
            return;
        }

        Auth::login((int)$user['id']);
        $intended = Session::get('_intended', '/');
        Session::remove('_intended');
        Response::redirect($intended);
    }

    public function logout(array $params): void
    {
        Csrf::validate($_POST['_csrf'] ?? '') || Csrf::fail();
        Auth::logout();
        Response::redirect('/login');
    }
}
