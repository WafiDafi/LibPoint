<?php
/** Escape a value for safe HTML output. */
function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an internal URL: url('books', ['q' => 'php']) */
function url(string $page, array $params = []): string {
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function peso($n): string {
    return '₱' . number_format((float)$n, 2);
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}
