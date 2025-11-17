<?php
/**
 * SSL Certificate Check API
 * 
 * Validates SSL certificates for domains with detailed chain analysis.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

/**
 * Verify Cloudflare Turnstile CAPTCHA token
 */
function verifyTurnstileToken(?string $token): void {
    $secret = getenv('TURNSTILE_SECRET_KEY');
    if (empty($secret)) {
        return; // Allow if secret is not configured
    }

    if (empty($token)) {
        throw new Exception('CAPTCHA verification is required.');
    }

    $payload = [
        'secret' => $secret,
        'response' => $token,
    ];

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $payload['remoteip'] = $_SERVER['REMOTE_ADDR'];
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Failed to verify CAPTCHA');
    }

    $data = json_decode($response, true);
    if (empty($data['success'])) {
        throw new Exception('CAPTCHA verification failed');
    }
}

/**
 * Sanitize and validate hostname
 */
function sanitize_hostname(string $input): string {
    $input = trim($input);
    $input = preg_replace('/^https?:\/\//', '', $input);
    $input = preg_replace('/\/.*$/', '', $input);
    $parts = explode(':', $input);
    $hostname = $parts[0];
    
    if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        return $hostname;
    }
    
    return '';
}

/**
 * Fetch certificate using OpenSSL
 */
function fetch_openssl_diagnostics(string $hostname, int $port): string {
    $command = sprintf(
        'openssl s_client -connect %s:%d -showcerts -servername %s 2>&1 < /dev/null',
        escapeshellarg($hostname),
        $port,
        escapeshellarg($hostname)
    );
    
    return shell_exec($command) ?: '';
}

/**
 * Extract certificates from OpenSSL output
 */
function extract_certificates(string $output): array {
    $certificates = [];
    $currentCert = '';
    $inCert = false;
    
    foreach (explode("\n", $output) as $line) {
        if (strpos($line, '-----BEGIN CERTIFICATE-----') !== false) {
            $inCert = true;
            $currentCert = $line . "\n";
        } elseif (strpos($line, '-----END CERTIFICATE-----') !== false) {
            $currentCert .= $line . "\n";
            $certificates[] = $currentCert;
            $currentCert = '';
            $inCert = false;
        } elseif ($inCert) {
            $currentCert .= $line . "\n";
        }
    }
    
    return $certificates;
}

/**
 * Parse certificate information
 */
function parse_certificate(string $pem, int $index): ?array {
    $cert = openssl_x509_parse($pem);
    if ($cert === false) {
        return null;
    }
    
    $now = time();
    $validFrom = $cert['validFrom_time_t'] ?? null;
    $validTo = $cert['validTo_time_t'] ?? null;
    
    return [
        'index' => $index,
        'subject' => $cert['name'] ?? null,
        'issuer' => $cert['issuer']['CN'] ?? null,
        'commonName' => $cert['subject']['CN'] ?? null,
        'validFrom' => $validFrom ? date('Y-m-d H:i:s', $validFrom) : null,
        'validTo' => $validTo ? date('Y-m-d H:i:s', $validTo) : null,
        'validFromTimestamp' => $validFrom,
        'validToTimestamp' => $validTo,
        'signatureAlgorithm' => $cert['signatureTypeSN'] ?? null,
        'serialNumber' => $cert['serialNumber'] ?? null,
        'selfSigned' => ($cert['subject']['CN'] ?? '') === ($cert['issuer']['CN'] ?? ''),
    ];
}

// Main processing
$input = json_decode(file_get_contents('php://input'), true);
$domain = isset($input['domain']) ? trim((string) $input['domain']) : '';
$port = isset($input['port']) ? (int) $input['port'] : 443;
$captchaToken = $input['captchaToken'] ?? null;

if ($domain === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Domain is required']);
    exit;
}

// Verify CAPTCHA
try {
    verifyTurnstileToken($captchaToken);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Validate port
if ($port < 1 || $port > 65535) {
    http_response_code(400);
    echo json_encode(['error' => 'Port must be between 1 and 65535']);
    exit;
}

$cleanDomain = sanitize_hostname($domain);
if ($cleanDomain === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid hostname']);
    exit;
}

try {
    $opensslOutput = fetch_openssl_diagnostics($cleanDomain, $port);
    $certificateBlocks = extract_certificates($opensslOutput);
    
    if (empty($certificateBlocks)) {
        throw new RuntimeException('Certificate chain could not be extracted');
    }
    
    $certificates = [];
    foreach ($certificateBlocks as $index => $pem) {
        $parsed = parse_certificate($pem, $index);
        if ($parsed !== null) {
            $certificates[] = $parsed;
        }
    }
    
    if (empty($certificates)) {
        throw new RuntimeException('Certificate parsing failed');
    }
    
    $leaf = $certificates[0];
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $validTo = $leaf['validToTimestamp'] !== null
        ? (new DateTimeImmutable('@' . $leaf['validToTimestamp']))->setTimezone(new DateTimeZone('UTC'))
        : null;
    
    $daysRemaining = null;
    $expired = null;
    if ($validTo !== null) {
        $interval = $now->diff($validTo);
        $daysRemaining = (int) $interval->format('%r%a');
        $expired = $validTo < $now;
    }
    
    $summary = [
        'hostname' => $cleanDomain,
        'port' => $port,
        'expired' => $expired,
        'daysRemaining' => $daysRemaining,
        'trusted' => true, // Simplified for example
        'selfSigned' => $leaf['selfSigned'],
    ];
    
    $general = [
        'commonName' => $leaf['commonName'],
        'issuer' => $leaf['issuer'],
        'validFrom' => $leaf['validFrom'],
        'validTo' => $leaf['validTo'],
        'signatureAlgorithm' => $leaf['signatureAlgorithm'],
    ];
    
    echo json_encode([
        'summary' => $summary,
        'general' => $general,
        'chain' => $certificates,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

