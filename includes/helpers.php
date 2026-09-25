<?php
/**
 * View + request helpers shared by every page, partial and action.
 */

declare(strict_types=1);

/* ---------------------------------------------------------------------------
 * Output helpers
 * ------------------------------------------------------------------------- */

/** Escape a value for HTML text or attribute output. */
function e(mixed $value): string
{
    if ($value === null || $value === false) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Join CSS classes, skipping empty values (like clsx). */
function cx(string|array|null|false ...$parts): string
{
    $out = [];
    array_walk_recursive($parts, function ($p) use (&$out) {
        if ($p) {
            $out[] = $p;
        }
    });
    return implode(' ', $out);
}

/**
 * Inline Lucide SVG icon, named like the lucide-react component.
 *   <?= icon('CheckCircle2', 'w-4 h-4 text-brand-600') ?>
 *   <?= icon('X', 'w-4 h-4', ['stroke-width' => 2.5]) ?>
 */
function icon(string $name, string $class = '', array $attrs = []): string
{
    static $icons = null;
    $icons ??= require __DIR__ . '/icons.php';
    $inner = $icons[$name] ?? $icons['Circle'];
    $kebab = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name));
    $strokeWidth = $attrs['stroke-width'] ?? 2;
    unset($attrs['stroke-width']);
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="' . e($attrs['fill'] ?? 'none') . '" stroke="currentColor" stroke-width="' . e($strokeWidth) . '" stroke-linecap="round" stroke-linejoin="round" class="' . e(cx('lucide', 'lucide-' . $kebab, $class)) . '" aria-hidden="true"' . attrs(array_diff_key($attrs, ['fill' => 1])) . '>' . $inner . '</svg>';
}

/** Render an attribute list: attrs(['disabled' => true, 'title' => 'x']) => ' disabled title="x"' */
function attrs(array $attrs): string
{
    $out = '';
    foreach ($attrs as $k => $v) {
        if ($v === false || $v === null) {
            continue;
        }
        $out .= $v === true ? ' ' . $k : ' ' . $k . '="' . e($v) . '"';
    }
    return $out;
}

/** 'checked' / 'selected' / 'disabled' helpers for form markup. */
function checked(bool $on): string
{
    return $on ? ' checked' : '';
}
function selected(bool $on): string
{
    return $on ? ' selected' : '';
}
function disabled(bool $on): string
{
    return $on ? ' disabled' : '';
}

/**
 * Include a partial view with variables and echo it.
 *   partial('sidebar', ['campaign' => $campaign]);   // views/partials/sidebar.php
 */
function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require MD_ROOT . '/views/partials/' . $name . '.php';
}

/** Same as partial() but returns the HTML instead of echoing it. */
function render_partial(string $name, array $vars = []): string
{
    ob_start();
    partial($name, $vars);
    return (string) ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * URLs + redirects
 * ------------------------------------------------------------------------- */

/**
 * Build a clean app URL (relative to the app root; .htaccess maps it to index.php?page=…):
 *   url('recipients', ['q' => 'john']) => recipients?q=john     url('home') => ./     url('home', ['action' => 'x']) => ./?action=x
 */
function url(string $page = 'home', array $params = []): string
{
    unset($params['page']);
    $query = http_build_query($params);
    return ($page === 'home' ? './' : $page) . ($query !== '' ? '?' . $query : '');
}

/** URL of a GET action, e.g. action_url('logs.downloadCsv') => ./?action=logs.downloadCsv */
function action_url(string $action, array $params = []): string
{
    return url('home', ['action' => $action] + $params);
}

function asset(string $path): string
{
    $file = MD_ROOT . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return 'assets/' . $path . '?v=' . $v;
}

function redirect(string $page = 'home', array $params = [], string $hash = ''): never
{
    header('Location: ' . url($page, $params) . ($hash !== '' ? '#' . $hash : ''), true, 303);
    exit;
}

/** Redirect back to the page the form was posted from (hidden "return" field), else to $fallback. */
function redirect_back(string $fallback = 'overview', array $params = []): never
{
    $return = $_POST['_return'] ?? '';
    // Only same-app relative targets: "./", "./?…", "recipients?…" (never "//host" or "http:")
    if (is_string($return) && preg_match('#^(\./|[a-z]+|index\.php)(\?[^\s]*)?$#', $return)) {
        header('Location: ' . $return, true, 303);
        exit;
    }
    redirect($fallback, $params);
}

/** Current page URL (relative), used as the default "return" target of forms. */
function current_url(): string
{
    $params = $_GET;
    $page = is_string($params['page'] ?? null) ? $params['page'] : 'home';
    return url($page, $params);
}

/* ---------------------------------------------------------------------------
 * CSRF + forms
 * ------------------------------------------------------------------------- */

function csrf_token(): string
{
    md_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/**
 * Hidden fields every POST form needs: CSRF token, the action name and the page to return to.
 *   <form method="post"><?= form_action('recipients.add') ?> ... </form>
 */
function form_action(string $action, ?string $return = null): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="_return" value="' . e($return ?? current_url()) . '">';
}

/**
 * A one-button POST form (for actions like delete / retry / start).
 *   <?= post_button('recipients.delete', ['id' => $r['id']], icon('Trash2','w-4 h-4'), 'class...', ['title' => 'Delete', 'confirm' => 'Delete?']) ?>
 */
function post_button(string $action, array $fields, string $innerHtml, string $class = '', array $buttonAttrs = []): string
{
    $confirm = $buttonAttrs['confirm'] ?? null;
    unset($buttonAttrs['confirm']);
    $formClass = $buttonAttrs['form_class'] ?? 'inline';
    unset($buttonAttrs['form_class']);
    $html = '<form method="post" action="./" class="' . e($formClass) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . form_action($action);
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="' . e($class) . '"' . attrs($buttonAttrs) . '>' . $innerHtml . '</button></form>';
}

/** Posted string value, trimmed. */
function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Posted string value WITHOUT trimming (template HTML, passwords). */
function input_raw(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? str_replace("\r\n", "\n", $v) : $default;
}

function input_bool(string $key): bool
{
    return !empty($_POST[$key]) && $_POST[$key] !== '0';
}

/* ---------------------------------------------------------------------------
 * Flash messages + old input (survive one redirect)
 * ------------------------------------------------------------------------- */

/** Queue a message for the next page: flash('success', 'Saved!'); types: success | error | info | warning */
function flash(string $type, string $message): void
{
    md_session_start();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Take (and clear) all queued flash messages. */
function take_flashes(): array
{
    md_session_start();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Store arbitrary data for the next request only (e.g. a test result panel). */
function flash_data(string $key, mixed $value): void
{
    md_session_start();
    $_SESSION['flash_data'][$key] = $value;
}

function take_flash_data(string $key, mixed $default = null): mixed
{
    md_session_start();
    if (!array_key_exists($key, $_SESSION['flash_data'] ?? [])) {
        return $default;
    }
    $v = $_SESSION['flash_data'][$key];
    unset($_SESSION['flash_data'][$key]);
    return $v;
}

/** Remember posted fields so a form can be re-filled after a validation error. */
function keep_old_input(array $except = ['password', 'confirm', '_csrf']): void
{
    flash_data('_old', array_diff_key($_POST, array_flip($except)));
}

/** Previously posted value (after keep_old_input + redirect), else $default. */
function old(string $key, mixed $default = ''): mixed
{
    static $old = null;
    $old ??= take_flash_data('_old', []);
    return $old[$key] ?? $default;
}

/* ---------------------------------------------------------------------------
 * Formatting
 * ------------------------------------------------------------------------- */

function format_time_remaining(int $seconds): string
{
    if ($seconds <= 0) {
        return '00:00';
    }
    return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
}

/** ISO-8601 → local display like the React toLocaleTimeString()/toLocaleString() output. */
function format_time(?string $iso, string $format = 'h:i:s A'): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date($format, $ts) : '';
}

function format_datetime(?string $iso): string
{
    return format_time($iso, 'd/m/Y, h:i:s A');
}

/** Stream rows as a CSV download and stop. */
function send_csv(string $filename, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    $out = fopen('php://output', 'w');
    foreach ($rows as $row) {
        fputcsv($out, array_map(fn ($v) => $v === null ? '' : (string) $v, $row), ',', '"', '');
    }
    fclose($out);
    exit;
}

/** JSON-encode for embedding inside a <script type="application/json"> block. */
function json_for_script(mixed $value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
