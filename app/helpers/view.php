<?php

declare(strict_types=1);

function render(string $viewFile, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/' . ltrim($viewFile, '/');
}

function redirect(string $to): void
{
    header('Location: ' . $to, true, 303);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && hash_equals(csrf_token(), $submitted);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** @return array{type: string, message: string}|null */
function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) && isset($flash['type'], $flash['message']) ? $flash : null;
}
