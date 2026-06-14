<?php
/**
 * Auth integration test — exercises the live HTTP stack.
 *
 * Tests:
 *   1. Registration with valid data
 *   2. Registration rejected on duplicate username/email
 *   3. Login with email
 *   4. Login with username
 *   5. Login rejected with wrong password
 *   6. Logout
 *
 * Run: php tests/auth_test.php
 */

declare(strict_types=1);

// PHP 7.3 polyfills
function yc_str_contains(string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) !== false;
}
function yc_str_ends_with(string $haystack, string $needle): bool {
    return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
}

const BASE_URL  = 'http://localhost';
const TEST_USER = 'testuser_auth_' . __LINE__;  // unique enough for one run

// Unique values so repeated runs don't collide (cleaned up at the end)
$ts       = time();
$username = "tst_{$ts}";
$email    = "tst_{$ts}@example.test";
$password = 'T3stPass!word';

// ── Helpers ───────────────────────────────────────────────────────────────────

$passed = 0;
$failed = 0;

function ok(string $label, bool $cond): void
{
    global $passed, $failed;
    if ($cond) {
        echo "\033[32m  ✓\033[0m  {$label}\n";
        $passed++;
    } else {
        echo "\033[31m  ✗\033[0m  {$label}\n";
        $failed++;
    }
}

/**
 * Performs a cURL request.
 *
 * @return array{body: string, status: int, location: string}
 */
function req(
    string $method,
    string $path,
    array  $fields   = [],
    string $cookieJar = '',
): array {
    $ch = curl_init(BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,   // we inspect redirects manually
        CURLOPT_TIMEOUT        => 10,
    ]);

    if ($cookieJar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST,       true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }

    $raw    = curl_exec($ch);
    $hSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headers  = substr($raw, 0, $hSize);
    $body     = substr($raw, $hSize);
    $location = '';
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
        $location = trim($m[1]);
    }

    return ['body' => $body, 'status' => $status, 'location' => $location];
}

/** Extract the CSRF token from an HTML page. */
function csrf(string $html): string
{
    preg_match('/<input[^>]+name="_csrf"[^>]+value="([^"]+)"/i', $html, $m);
    return $m[1] ?? '';
}

/** Returns true if the response is a redirect to $path (absolute or relative). */
function redirectsTo(array $res, string $path): bool
{
    return $res['status'] >= 300 && $res['status'] < 400
        && (yc_str_ends_with(rtrim($res['location'], '/'), $path)
            || $res['location'] === $path);
}

/** Check whether the profile nav link (username) appears in a page body. */
function isLoggedInBody(string $body, string $username): bool
{
    return yc_str_contains($body, '/profile/' . $username);
}

// ── Test setup ────────────────────────────────────────────────────────────────

$jar = tempnam(sys_get_temp_dir(), 'yc_test_cookies_');

echo "\n\033[1mYouCan Auth Integration Tests\033[0m\n";
echo "User: {$username}  /  Email: {$email}\n\n";

// ── 1. Register — show form ───────────────────────────────────────────────────
echo "Registration\n";

$res  = req('GET', '/register', cookieJar: $jar);
$token = csrf($res['body']);

ok('GET /register returns 200',     $res['status'] === 200);
ok('Registration form has CSRF token', $token !== '');

// ── 2. Register — submit valid data ──────────────────────────────────────────
$res = req('POST', '/register', [
    '_csrf'            => $token,
    'username'         => $username,
    'email'            => $email,
    'password'         => $password,
    'password_confirm' => $password,
], $jar);

ok('POST /register redirects on success', redirectsTo($res, '/'));

// Follow redirect to home, confirm logged in
$home = req('GET', '/', cookieJar: $jar);
ok('Logged in after registration (nav shows username)', isLoggedInBody($home['body'], $username));

// ── 3. Register — duplicate username ─────────────────────────────────────────
echo "\nDuplicate / validation\n";

// New session for duplicate check
$jar2  = tempnam(sys_get_temp_dir(), 'yc_test_cookies_');
$res   = req('GET', '/register', cookieJar: $jar2);
$token = csrf($res['body']);

$res = req('POST', '/register', [
    '_csrf'            => $token,
    'username'         => $username,             // same username
    'email'            => "other_{$ts}@example.test",
    'password'         => $password,
    'password_confirm' => $password,
], $jar2);

ok('Duplicate username rejected (stays on register)', $res['status'] === 200 && yc_str_contains($res['body'], 'register'));

// Duplicate email
$res   = req('GET', '/register', cookieJar: $jar2);
$token = csrf($res['body']);

$res = req('POST', '/register', [
    '_csrf'            => $token,
    'username'         => "other_{$ts}",
    'email'            => $email,                // same email
    'password'         => $password,
    'password_confirm' => $password,
], $jar2);

ok('Duplicate email rejected (stays on register)', $res['status'] === 200 && yc_str_contains($res['body'], 'register'));

// ── 4. Log out first session so we can test login fresh ──────────────────────
echo "\nLogin with email\n";

$home  = req('GET', '/', cookieJar: $jar);
$token = csrf($home['body']);

$res = req('POST', '/logout', ['_csrf' => $token], $jar);
ok('Logout redirects to /login', redirectsTo($res, '/login'));

// Confirm logged out
$home = req('GET', '/', cookieJar: $jar);
ok('Logged out (nav no longer shows username)', !isLoggedInBody($home['body'], $username));

// ── 5. Login with email ───────────────────────────────────────────────────────
$loginPage = req('GET', '/login', cookieJar: $jar);
$token     = csrf($loginPage['body']);

$res = req('POST', '/login', [
    '_csrf'      => $token,
    'identifier' => $email,
    'password'   => $password,
], $jar);

ok('POST /login with email redirects', redirectsTo($res, '/'));

$home = req('GET', '/', cookieJar: $jar);
ok('Logged in via email (nav shows username)', isLoggedInBody($home['body'], $username));

// ── 6. Login with username ────────────────────────────────────────────────────
echo "\nLogin with username\n";

// Logout first
$home  = req('GET', '/', cookieJar: $jar);
$token = csrf($home['body']);
req('POST', '/logout', ['_csrf' => $token], $jar);

$loginPage = req('GET', '/login', cookieJar: $jar);
$token     = csrf($loginPage['body']);

$res = req('POST', '/login', [
    '_csrf'      => $token,
    'identifier' => $username,
    'password'   => $password,
], $jar);

ok('POST /login with username redirects', redirectsTo($res, '/'));

$home = req('GET', '/', cookieJar: $jar);
ok('Logged in via username (nav shows username)', isLoggedInBody($home['body'], $username));

// ── 7. Wrong password ─────────────────────────────────────────────────────────
echo "\nInvalid credentials\n";

$jar3      = tempnam(sys_get_temp_dir(), 'yc_test_cookies_');
$loginPage = req('GET', '/login', cookieJar: $jar3);
$token     = csrf($loginPage['body']);

$res = req('POST', '/login', [
    '_csrf'      => $token,
    'identifier' => $email,
    'password'   => 'wrongpassword',
], $jar3);

ok('Wrong password stays on login page (200)', $res['status'] === 200);
ok('Wrong password shows error message', yc_str_contains($res['body'], 'Invalid') || yc_str_contains($res['body'], 'Neplatné') || yc_str_contains($res['body'], 'Neplatný'));

// ── 8. Wrong username ────────────────────────────────────────────────────────
$loginPage = req('GET', '/login', cookieJar: $jar3);
$token     = csrf($loginPage['body']);

$res = req('POST', '/login', [
    '_csrf'      => $token,
    'identifier' => 'nonexistent_user_xyz',
    'password'   => $password,
], $jar3);

ok('Nonexistent username stays on login page (200)', $res['status'] === 200);
ok('Nonexistent username shows error message', yc_str_contains($res['body'], 'Invalid') || yc_str_contains($res['body'], 'Neplatné') || yc_str_contains($res['body'], 'Neplatný'));

// ── Cleanup ───────────────────────────────────────────────────────────────────
@unlink($jar);
@unlink($jar2);
@unlink($jar3);

// Remove test user from DB
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';
(require BASE_PATH . '/src/Core/env.php')();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$stmt = $pdo->prepare('DELETE FROM users WHERE username = ?');
$stmt->execute([$username]);
$cleaned = $stmt->rowCount();

echo "\nCleanup: removed {$cleaned} test user(s) from DB\n";

// ── Summary ───────────────────────────────────────────────────────────────────
$total = $passed + $failed;
echo "\n\033[1m{$passed}/{$total} passed\033[0m";
if ($failed) {
    echo "  \033[31m({$failed} failed)\033[0m";
}
echo "\n\n";

exit($failed > 0 ? 1 : 0);
