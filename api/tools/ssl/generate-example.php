<?php
/**
 * Generate Certificate API - Example
 * 
 * Generates self-signed SSL certificates with customizable parameters.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

/**
 * Verify Cloudflare Turnstile CAPTCHA
 */
function verifyTurnstileToken(?string $token): bool {
    $secret = getenv('TURNSTILE_SECRET_KEY');
    if (empty($secret) || empty($token)) {
        return false;
    }

    $data = [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['success'] ?? false;
}

$input = json_decode(file_get_contents('php://input'), true);
$keyType = $input['keyType'] ?? 'rsa';
$keyBits = intval($input['keyBits'] ?? 2048);
$validityDays = intval($input['validityDays'] ?? 365);
$commonName = $input['commonName'] ?? '';
$san = $input['san'] ?? '';
$captchaToken = $input['captchaToken'] ?? null;

// Verify CAPTCHA
if (!verifyTurnstileToken($captchaToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'CAPTCHA verification failed']);
    exit;
}

// Validate inputs
if (empty($commonName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Common Name is required']);
    exit;
}

// Generate private key
$config = [
    'private_key_bits' => $keyBits,
    'private_key_type' => $keyType === 'rsa' ? OPENSSL_KEYTYPE_RSA : 
                         ($keyType === 'dsa' ? OPENSSL_KEYTYPE_DSA : OPENSSL_KEYTYPE_EC)
];

$privateKey = openssl_pkey_new($config);
if ($privateKey === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate private key']);
    exit;
}

// Create certificate signing request
$dn = [
    'countryName' => 'US',
    'commonName' => $commonName
];

if ($san) {
    $sanArray = array_map('trim', explode(',', $san));
    $config['req_extensions'] = 'v3_req';
    $config['x509_extensions'] = 'v3_req';
}

$csr = openssl_csr_new($dn, $privateKey, $config);
$cert = openssl_csr_sign($csr, null, $privateKey, $validityDays, $config);

openssl_x509_export($cert, $certPem);
openssl_pkey_export($privateKey, $keyPem);

echo json_encode([
    'certificate' => $certPem,
    'privateKey' => $keyPem
]);

