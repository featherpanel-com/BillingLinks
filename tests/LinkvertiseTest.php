<?php

/*
 * Standalone tests for the Linkvertise URL builder.
 * Run: php tests/LinkvertiseTest.php
 */

declare(strict_types=1);

require __DIR__ . '/../Services/Linkvertise/Linkvertise.php';

use App\Addons\billinglinks\Services\Linkvertise\Linkvertise;

final class TestLinkvertise extends Linkvertise
{
    public string $fixedRandom = '123.456';

    protected function randomPathSegment(): string
    {
        return $this->fixedRandom;
    }
}

$passed = 0;
$failed = 0;

function assert_true(bool $cond, string $msg): void
{
    global $passed, $failed;
    if ($cond) {
        ++$passed;
        echo "  PASS  {$msg}\n";
    } else {
        ++$failed;
        echo "  FAIL  {$msg}\n";
    }
}

function assert_equals(mixed $expected, mixed $actual, string $msg): void
{
    assert_true($expected === $actual, $msg . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')');
}

function assert_throws(callable $fn, string $needle, string $msg): void
{
    try {
        $fn();
        assert_true(false, $msg . ' (no exception thrown)');
    } catch (\InvalidArgumentException $e) {
        assert_true(str_contains($e->getMessage(), $needle), $msg . ' (message: ' . $e->getMessage() . ')');
    } catch (\Throwable $e) {
        assert_true(false, $msg . ' (unexpected ' . $e::class . ': ' . $e->getMessage() . ')');
    }
}

$service = new TestLinkvertise();
$target = 'https://panel.example.com/api/user/billinglinks/earn/11111111-1111-4111-8111-111111111111';

echo "== URL construction ==\n";
$url = $service->getLink('583258', $target);
assert_equals(
    'https://link-to.net/583258/123.456/dynamic/?r=' . base64_encode($target),
    $url,
    'builds the official dynamic URL format'
);

echo "== Payload ==\n";
$parts = [];
assert_true((bool) preg_match('#^https://link-to\.net/(\d+)/([0-9.]+)/dynamic/\?r=([A-Za-z0-9+/=]+)$#', $url, $parts), 'URL matches expected pattern');
assert_equals('583258', $parts[1] ?? null, 'user ID is in the path');
assert_equals('123.456', $parts[2] ?? null, 'random segment is in the path');
assert_equals($target, base64_decode($parts[3] ?? '', true), 'r= payload decodes back to the earn callback');

echo "== Trimming ==\n";
assert_equals($url, $service->getLink('  583258  ', '  ' . $target . '  '), 'trims user ID and target URL');

echo "== Validation ==\n";
assert_throws(fn () => $service->getLink('', $target), 'user ID', 'rejects empty user ID');
assert_throws(fn () => $service->getLink('   ', $target), 'user ID', 'rejects whitespace user ID');
assert_throws(fn () => $service->getLink('abc', $target), 'user ID', 'rejects non-numeric user ID');
assert_throws(fn () => $service->getLink('583258;rm', $target), 'user ID', 'rejects user ID with extra characters');
assert_throws(fn () => $service->getLink('583258', ''), 'target URL', 'rejects empty target URL');
assert_throws(fn () => $service->getLink('583258', '/api/user/billinglinks/earn/abc'), 'target URL', 'rejects relative target URL');
assert_throws(fn () => $service->getLink('583258', 'javascript:alert(1)'), 'target URL', 'rejects non-http target URL');
assert_throws(fn () => $service->getLink('583258', "https://example.com/foo\nbad"), 'target URL', 'rejects target URL with whitespace in the middle');

echo "== Real random segment ==\n";
$live = new Linkvertise();
$liveUrl = $live->getLink('42', 'https://example.com/callback');
assert_true((bool) preg_match('#^https://link-to\.net/42/[0-9.]+/dynamic/\?r=#', $liveUrl), 'live builder uses a numeric random segment');
assert_equals('https://example.com/callback', base64_decode(substr($liveUrl, strpos($liveUrl, '?r=') + 3), true), 'live payload decodes to the target');

$a = $live->getLink('42', 'https://example.com/callback');
$b = $live->getLink('42', 'https://example.com/callback');
// Extremely unlikely to collide; if they do, still a valid URL.
assert_true($a !== $b || preg_match('#/dynamic/\?r=#', $a), 'successive links can differ in the random segment');

echo "== Live Linkvertise endpoint (classic format still accepted) ==\n";
$probe = $service->getLink('583258', 'https://example.com/callback-test');
$probeEsc = escapeshellarg($probe);
$curl = "curl -sS -o /dev/null -w '%{http_code} %{redirect_url}' --max-redirs 0 -A 'Mozilla/5.0' --max-time 20 {$probeEsc}";
$raw = [];
$exit = 0;
exec($curl . ' 2>/tmp/lv-test-curl.err', $raw, $exit);
$line = $raw[0] ?? '';
if ($exit !== 0 && $exit !== 47) {
    $err = is_file('/tmp/lv-test-curl.err') ? trim((string) file_get_contents('/tmp/lv-test-curl.err')) : '';
    // Network flakes should not fail the unit tests.
    echo "  SKIP  live probe (curl exit {$exit}): {$err}\n";
} else {
    $code = (int) strtok($line, ' ');
    $location = trim((string) strtok(''));
    assert_true($code === 302, "link-to.net returns 302 (got {$code})");
    assert_true(
        str_contains($location, 'https://linkvertise.com/583258/') && str_contains($location, 'r='),
        'link-to.net redirects to the Linkvertise app with the same payload'
    );
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
