<?php
/**
 * Generate CSR (Certificate Signing Request) API
 * 
 * Generates Certificate Signing Requests with ISO 3166-1 alpha-2 country code validation.
 */

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
function verifyTurnstileToken(?string $token): bool {
    $secret = getenv('TURNSTILE_SECRET_KEY');
    if (empty($secret)) {
        return true; // Allow if secret is not configured
    }

    if (empty($token)) {
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

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$country = isset($input['country']) ? strtoupper(trim($input['country'])) : '';
$state = isset($input['state']) ? trim($input['state']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$organization = isset($input['organization']) ? trim($input['organization']) : '';
$organizationalUnit = isset($input['organizationalUnit']) ? trim($input['organizationalUnit']) : '';
$commonName = isset($input['commonName']) ? trim($input['commonName']) : '';
$keyBits = isset($input['keyBits']) ? intval($input['keyBits']) : 2048;
$captchaToken = $input['captchaToken'] ?? null;

// Validate country code (ISO 3166-1 alpha-2)
if (!empty($country)) {
    if (strlen($country) !== 2 || !preg_match('/^[A-Z]{2}$/', $country)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Country code must be exactly 2 uppercase letters (ISO 3166-1 alpha-2 format, e.g., US, UA, GB)'
        ]);
        exit;
    }
}

// Verify CAPTCHA
if (!verifyTurnstileToken($captchaToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'CAPTCHA verification failed']);
    exit;
}

// Validate required fields
if (empty($commonName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Common Name (domain) is required']);
    exit;
}

// Build Distinguished Name
$dn = [];
if (!empty($country)) {
    $dn['countryName'] = $country;
}
if (!empty($state)) {
    $dn['stateOrProvinceName'] = $state;
}
if (!empty($city)) {
    $dn['localityName'] = $city;
}
if (!empty($organization)) {
    $dn['organizationName'] = $organization;
}
if (!empty($organizationalUnit)) {
    $dn['organizationalUnitName'] = $organizationalUnit;
}
$dn['commonName'] = $commonName;

// Generate private key
$config = [
    'private_key_bits' => $keyBits,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$privateKey = openssl_pkey_new($config);
if ($privateKey === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate private key']);
    exit;
}

// Generate CSR
$csr = openssl_csr_new($dn, $privateKey, $config);
if ($csr === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate CSR']);
    exit;
}

// Export CSR and private key
openssl_csr_export($csr, $csrPem);
openssl_pkey_export($privateKey, $keyPem);

echo json_encode([
    'csr' => $csrPem,
    'privateKey' => $keyPem,
    'dn' => $dn
]);






