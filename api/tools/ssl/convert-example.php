<?php
/**
 * Convert Certificate API - Example
 * 
 * Converts certificates between different formats (PEM, PFX, DER).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$conversionType = $input['conversionType'] ?? '';
$certificate = $input['certificate'] ?? '';
$privateKey = $input['privateKey'] ?? '';
$password = $input['password'] ?? '';

switch ($conversionType) {
    case 'pem_to_pfx':
        // Convert PEM to PKCS#12
        $cert = openssl_x509_read($certificate);
        $key = openssl_pkey_get_private($privateKey);
        
        if ($cert === false || $key === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid certificate or key']);
            exit;
        }
        
        openssl_pkcs12_export($cert, $pfx, $key, $password);
        
        echo json_encode([
            'pfx' => base64_encode($pfx),
            'filename' => 'certificate.pfx'
        ]);
        break;
    
    case 'pfx_to_pem':
        // Extract PEM from PKCS#12
        $pfxData = base64_decode($certificate);
        
        if (openssl_pkcs12_read($pfxData, $certs, $password)) {
            echo json_encode([
                'certificate' => $certs['cert'],
                'privateKey' => $certs['key']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to read PFX file. Check password.']);
        }
        break;
    
    case 'pem_to_der_cert':
        // Convert PEM certificate to DER
        $cert = openssl_x509_read($certificate);
        if ($cert === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid certificate']);
            exit;
        }
        
        openssl_x509_export($cert, $pem, false);
        $der = base64_decode(preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/', '', $pem));
        
        echo json_encode([
            'der' => base64_encode($der),
            'filename' => 'certificate.der'
        ]);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported conversion type']);
}

