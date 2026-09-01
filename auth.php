<?php
session_start();

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function loginUser(int $userId, string $username): void
{
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}
