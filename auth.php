<?php
session_start();

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function loginUser(int $userId, string $username, string $role = 'user'): void
{
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
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

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireRole(string $role): void
{
    if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: index.php');
        exit;
    }
}
