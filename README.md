# SSL Certificate Tools

A comprehensive online SSL certificate management suite providing five essential tools for certificate operations.

🌐 **Live Demo:** [https://devsecops.cv/tools/ssl](https://devsecops.cv/tools/ssl)

> ⚠️ **Important:** When setting up this repository on GitHub, make sure to add the website URL `https://devsecops.cv/tools/ssl` in the repository "About" section!

## Features

### 🔍 Check Certificate Website
Real-time SSL certificate validation with detailed chain analysis, DNS resolution, and security assessment.

### 🔐 Generate Certificate
Self-signed certificate generation with customizable parameters (RSA, DSA, ECDSA key types, validity periods, SAN support).

### 🔄 Convert Certificate
Multi-format certificate conversion (PEM ↔ PFX, PEM ↔ DER, key format conversions).

### 🔗 Match Certificate
Certificate and private key matching verification with fingerprint comparison.

### 📝 Generate CSR
Certificate Signing Request generation with manual country/state/city input and ISO 3166-1 alpha-2 country code validation.

## Screenshots

### Check Certificate Website
![Check Certificate Website](screenshots/ssl-tools-check.png)

### Generate Certificate
![Generate Certificate](screenshots/ssl-tools-generate.png)

### Convert Certificate
![Convert Certificate](screenshots/ssl-tools-convert.png)

### Match Certificate
![Match Certificate](screenshots/ssl-tools-match.png)

### Generate CSR
![Generate CSR](screenshots/ssl-tools-csr.png)

## Tech Stack

- **Frontend:** Next.js 16, React 19, TypeScript, TailwindCSS, shadcn/ui
- **Backend:** PHP 8.3, OpenSSL
- **Security:** Cloudflare Turnstile CAPTCHA
- **Deployment:** Nginx, PHP-FPM

## Project Structure

```
ssl-certificate-tools/
├── src/
│   └── app/
│       └── tools/
│           └── ssl/
│               └── page.tsx          # Main frontend component
├── api/
│   └── tools/
│       └── ssl/
│           ├── check.php             # Certificate validation API
│           ├── generate.php          # Certificate generation API
│           ├── convert.php           # Certificate conversion API
│           ├── match.php             # Certificate matching API
│           └── generate-csr.php      # CSR generation API
└── screenshots/
    ├── ssl-tools-check.png
    ├── ssl-tools-generate.png
    ├── ssl-tools-convert.png
    ├── ssl-tools-match.png
    └── ssl-tools-csr.png
```

## Key Features

### Security
- Cloudflare Turnstile CAPTCHA on all certificate generation operations
- Server-side validation of all inputs
- ISO 3166-1 alpha-2 country code validation for CSR generation
- No certificate or key data stored on server
- HTTPS-only API endpoints

### Certificate Operations
- Real-time SSL certificate validation
- Multiple key types: RSA (2048, 4096 bits), DSA, ECDSA (P-256, P-384, P-521)
- Configurable validity periods
- Subject Alternative Name (SAN) support
- Multi-format conversion (PEM, PFX, DER)
- Certificate and key matching verification

## Installation

### Prerequisites
- Node.js 18+ and npm
- PHP 8.3+ with OpenSSL extension
- Nginx with PHP-FPM

### Frontend Setup

```bash
npm install
npm run build
```

### Backend Setup

1. Ensure PHP OpenSSL extension is enabled:
```bash
php -m | grep openssl
```

2. Configure environment variables:
```bash
cp .env.example .env
# Edit .env and add your Cloudflare Turnstile keys
```

3. Configure Nginx to serve the application:
```nginx
location /api/tools/ssl/ {
    root /path/to/project;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/api/tools/ssl/$1.php;
    include fastcgi_params;
}
```

## Usage Examples

### Check Certificate
```typescript
const response = await fetch('/api/tools/ssl/check', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    hostname: 'example.com',
    port: 443,
    captchaToken: 'turnstile-token'
  })
});
```

### Generate Certificate
```typescript
const response = await fetch('/api/tools/ssl/generate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    keyType: 'rsa',
    keyBits: 2048,
    validityDays: 365,
    commonName: 'example.com',
    captchaToken: 'turnstile-token'
  })
});
```

### Generate CSR
```typescript
const response = await fetch('/api/tools/ssl/generate-csr', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    country: 'US',
    state: 'California',
    city: 'San Francisco',
    organization: 'Example Inc',
    commonName: 'example.com',
    keyBits: 2048,
    captchaToken: 'turnstile-token'
  })
});
```

## API Endpoints

### POST `/api/tools/ssl/check`
Check SSL certificate for a domain.

**Request:**
```json
{
  "hostname": "example.com",
  "port": 443,
  "captchaToken": "turnstile-token"
}
```

**Response:**
```json
{
  "summary": {
    "hostname": "example.com",
    "port": 443,
    "expired": false,
    "daysRemaining": 30,
    "trusted": true
  },
  "general": {
    "commonName": "example.com",
    "issuer": "Let's Encrypt",
    "validFrom": "2024-01-01",
    "validTo": "2024-12-31"
  }
}
```

### POST `/api/tools/ssl/generate`
Generate a self-signed certificate.

**Request:**
```json
{
  "keyType": "rsa",
  "keyBits": 2048,
  "validityDays": 365,
  "commonName": "example.com",
  "captchaToken": "turnstile-token"
}
```

**Response:**
```json
{
  "certificate": "-----BEGIN CERTIFICATE-----\n...",
  "privateKey": "-----BEGIN PRIVATE KEY-----\n..."
}
```

### POST `/api/tools/ssl/generate-csr`
Generate a Certificate Signing Request.

**Request:**
```json
{
  "country": "US",
  "state": "California",
  "city": "San Francisco",
  "organization": "Example Inc",
  "commonName": "example.com",
  "keyBits": 2048,
  "captchaToken": "turnstile-token"
}
```

**Response:**
```json
{
  "csr": "-----BEGIN CERTIFICATE REQUEST-----\n...",
  "privateKey": "-----BEGIN PRIVATE KEY-----\n..."
}
```

## Security Considerations

- All certificate generation operations require Cloudflare Turnstile CAPTCHA verification
- Country codes are validated against ISO 3166-1 alpha-2 standard
- No certificate or key data is stored on the server
- All API endpoints use HTTPS only
- Input validation and sanitization on all endpoints

## License

This project is part of the DevSecOps portfolio. See the main repository for license information.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Author

**Dmytro B.**

- Website: [https://devsecops.cv](https://devsecops.cv)
- GitHub: [@dmytrobazeliuk-devops](https://github.com/dmytrobazeliuk-devops)

---

⭐ If you find this project useful, please consider giving it a star!

