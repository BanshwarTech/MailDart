<?php
/**
 * Auth actions (login, register, logout, forgot, reset) — replaces src/context/AuthContext.tsx' Firebase calls.
 * Namespace is public (index.php lets logged-out visitors post here); $user may be null except for logout.
 * Validation mirrors src/components/AuthPage.tsx exactly; includes/auth.php re-checks everything server-side
 * and throws AuthFailure with the user-facing message on failure.
 */

/** Where to send the visitor after a successful login/register/reset (?next=<page>, validated against APP_PAGES). */
function auth_redirect_target(): string
{
    $next = input('next');
    return in_array($next, APP_PAGES, true) ? $next : 'overview';
}

return [
    'login' => function (?array $user): void {
        $email = input('email');
        $password = input_raw('password');
        $remember = input_bool('remember');

        if ($email === '' || $password === '') {
            flash('error', 'Please enter your email and password.');
            keep_old_input();
            redirect_back('login');
        }

        try {
            auth_login($email, $password, $remember);
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            keep_old_input();
            redirect_back('login');
        }

        redirect(auth_redirect_target());
    },

    'register' => function (?array $user): void {
        $name = input('name');
        $email = input('email');
        $password = input_raw('password');
        $confirm = input_raw('confirm');

        if ($email === '' || $password === '') {
            flash('error', 'Please enter your email and password.');
            keep_old_input();
            redirect_back('register');
        }
        if ($name === '') {
            flash('error', 'Please enter your full name.');
            keep_old_input();
            redirect_back('register');
        }
        if (!(strlen($password) >= 8 && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password) && preg_match('/[^A-Za-z0-9]/', $password))) {
            flash('error', 'Password must be at least 8 characters, with uppercase, lowercase, a number and a symbol.');
            keep_old_input();
            redirect_back('register');
        }
        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            keep_old_input();
            redirect_back('register');
        }

        try {
            // No "keep me logged in" checkbox on Sign Up (matches AuthPage.tsx, which keeps its default of true).
            auth_register($email, $password, $name, true);
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            keep_old_input();
            redirect_back('register');
        }

        redirect(auth_redirect_target());
    },

    'logout' => function (?array $user): void {
        auth_logout();
        redirect('home');
    },

    'forgot' => function (?array $user): void {
        $email = input('email');
        if ($email === '') {
            flash('error', 'Please enter your email address.');
            keep_old_input();
            redirect_back('forgot');
        }

        try {
            auth_send_password_reset($email);
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            keep_old_input();
            redirect_back('forgot');
        }

        flash('info', "If an account exists for that email, we've sent a password reset link.");
        redirect_back('forgot');
    },

    'reset' => function (?array $user): void {
        $token = input('token');
        $password = input_raw('password');
        $confirm = input_raw('confirm');

        if ($password === '' || $confirm === '') {
            flash('error', 'Please choose and confirm a new password.');
            redirect('reset', ['token' => $token]);
        }
        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('reset', ['token' => $token]);
        }

        try {
            auth_reset_password($token, $password);
        } catch (AuthFailure $e) {
            flash('error', $e->getMessage());
            redirect('reset', ['token' => $token]);
        }

        flash('success', 'Your password has been reset. You are now signed in.');
        redirect(auth_redirect_target());
    },
];
