'use client';

/**
 * SSL Certificate Tools - Main Component
 * 
 * This is a simplified example showing the main structure.
 * Full implementation includes all 5 tabs with complete functionality.
 */

import { useEffect, useRef, useState } from 'react';
import { Shield, CheckCircle, XCircle, Download } from "lucide-react";

const TURNSTILE_SITE_KEY = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY || '';

export default function SSLToolsPage() {
// Tab management
const [activeTab, setActiveTab] = useState<'check' | 'generate' | 'convert' | 'match' | 'csr'>('check');

// Check Certificate state
const [checkDomain, setCheckDomain] = useState('');
const [checkPort, setCheckPort] = useState('443');
const [checkResult, setCheckResult] = useState<any>(null);
const [checkCaptchaToken, setCheckCaptchaToken] = useState<string | null>(null);
const [isChecking, setIsChecking] = useState(false);
const [checkError, setCheckError] = useState<string | null>(null);

// Generate Certificate state
const [certType, setCertType] = useState<'RSA' | 'DSA' | 'ECDSA'>('RSA');
const [certKeySize, setCertKeySize] = useState('2048');
const [certDomain, setCertDomain] = useState('');
const [certDays, setCertDays] = useState('3650');
const [generateResult, setGenerateResult] = useState<any>(null);
const [generateCaptchaToken, setGenerateCaptchaToken] = useState<string | null>(null);
const [isGenerating, setIsGenerating] = useState(false);
const [generateError, setGenerateError] = useState<string | null>(null);

// CSR Generation state
const [csrCountry, setCSRCountry] = useState('');
const [csrState, setCSRState] = useState('');
const [csrCity, setCSRCity] = useState('');
const [csrOrganization, setCSROrganization] = useState('');
const [csrDomain, setCSRDomain] = useState('');
const [csrResult, setCSRResult] = useState<any>(null);
const [csrCaptchaToken, setCSRCaptchaToken] = useState<string | null>(null);
const [isGeneratingCSR, setIsGeneratingCSR] = useState(false);
const [csrError, setCSRError] = useState<string | null>(null);

// Load Turnstile script
useEffect(() => {
  const script = document.createElement('script');
  script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
  script.async = true;
  script.defer = true;
  document.body.appendChild(script);
  
  return () => {
    const existing = document.querySelector('script[src*="turnstile"]');
    if (existing) existing.remove();
  };
}, []);

// Handle Check Certificate
const handleCheckSSL = async () => {
  if (TURNSTILE_SITE_KEY && !checkCaptchaToken) {
    alert('Please complete the CAPTCHA');
    return;
  }

  setIsChecking(true);
  try {
    const response = await fetch('/api/tools/ssl/check', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        domain: checkDomain,
        port: parseInt(checkPort),
        captchaToken: checkCaptchaToken || undefined
      })
    });

    const data = await response.json();
    if (data.error) {
      setCheckError(data.error);
    } else {
      setCheckResult(data);
    }
  } catch (error) {
    setCheckError('Failed to check certificate');
  } finally {
    setIsChecking(false);
  }
};

// Handle Generate Certificate
const handleGenerateCertificate = async () => {
  if (TURNSTILE_SITE_KEY && !generateCaptchaToken) {
    alert('Please complete the CAPTCHA');
    return;
  }

  setIsGenerating(true);
  try {
    const response = await fetch('/api/tools/ssl/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        keyType: certType.toLowerCase(),
        keyBits: parseInt(certKeySize),
        validityDays: parseInt(certDays),
        commonName: certDomain,
        captchaToken: generateCaptchaToken || undefined
      })
    });

    const data = await response.json();
    if (data.error) {
      setGenerateError(data.error);
    } else {
      setGenerateResult(data);
    }
  } catch (error) {
    setGenerateError('Failed to generate certificate');
  } finally {
    setIsGenerating(false);
  }
};

// Handle Generate CSR
const handleGenerateCSR = async () => {
  // Validate country code (ISO 3166-1 alpha-2)
  if (csrCountry && csrCountry.length !== 2) {
    alert('Country code must be exactly 2 characters (e.g., US, UA, GB)');
    return;
  }

  if (TURNSTILE_SITE_KEY && !csrCaptchaToken) {
    alert('Please complete the CAPTCHA');
    return;
  }

  setIsGeneratingCSR(true);
  try {
    const response = await fetch('/api/tools/ssl/generate-csr', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        country: csrCountry.toUpperCase(),
        state: csrState,
        city: csrCity,
        organization: csrOrganization,
        commonName: csrDomain,
        keyBits: 2048,
        captchaToken: csrCaptchaToken || undefined
      })
    });

    const data = await response.json();
    if (data.error) {
      setCSRError(data.error);
    } else {
      setCSRResult(data);
    }
  } catch (error) {
    setCSRError('Failed to generate CSR');
  } finally {
    setIsGeneratingCSR(false);
  }
};

// Main render
return (
  <div className="container mx-auto px-4 py-8">
    <div className="flex gap-2 border-b">
      <button onClick={() => setActiveTab('check')}>
        Check Certificate Website
      </button>
      <button onClick={() => setActiveTab('generate')}>
        Generate Certificate
      </button>
      <button onClick={() => setActiveTab('convert')}>
        Convert Certificate
      </button>
      <button onClick={() => setActiveTab('match')}>
        Match Certificate
      </button>
      <button onClick={() => setActiveTab('csr')}>
        Generate CSR
      </button>
    </div>

    {activeTab === 'check' && (
      <div className="mt-6">
        <input
          type="text"
          value={checkDomain}
          onChange={(e) => setCheckDomain(e.target.value)}
          placeholder="example.com"
        />
        <input
          type="number"
          value={checkPort}
          onChange={(e) => setCheckPort(e.target.value)}
          placeholder="443"
        />
        <button onClick={handleCheckSSL}>Check</button>
        {checkResult && (
          <div>
            <p>Valid: {checkResult.summary?.trusted ? 'Yes' : 'No'}</p>
            <p>Expires: {checkResult.general?.validTo}</p>
          </div>
        )}
      </div>
    )}

    {activeTab === 'generate' && (
      <div className="mt-6">
        <input
          type="text"
          value={certDomain}
          onChange={(e) => setCertDomain(e.target.value)}
          placeholder="example.com"
        />
        <select value={certType} onChange={(e) => setCertType(e.target.value as any)}>
          <option value="RSA">RSA</option>
          <option value="DSA">DSA</option>
          <option value="ECDSA">ECDSA</option>
        </select>
        <button onClick={handleGenerateCertificate}>Generate</button>
        {generateResult && (
          <div>
            <textarea value={generateResult.certificate} readOnly />
            <textarea value={generateResult.privateKey} readOnly />
          </div>
        )}
      </div>
    )}

    {activeTab === 'csr' && (
      <div className="mt-6">
        <input
          type="text"
          value={csrCountry}
          onChange={(e) => {
            const value = e.target.value.toUpperCase().replace(/[^A-Z]/g, '').slice(0, 2);
            setCSRCountry(value);
          }}
          placeholder="US, UA, GB (2-letter code)"
          maxLength={2}
        />
        <input
          type="text"
          value={csrState}
          onChange={(e) => setCSRState(e.target.value)}
          placeholder="State / Province"
        />
        <input
          type="text"
          value={csrCity}
          onChange={(e) => setCSRCity(e.target.value)}
          placeholder="City"
        />
        <input
          type="text"
          value={csrOrganization}
          onChange={(e) => setCSROrganization(e.target.value)}
          placeholder="Organization"
        />
        <input
          type="text"
          value={csrDomain}
          onChange={(e) => setCSRDomain(e.target.value)}
          placeholder="Common Name (domain)"
        />
        <button onClick={handleGenerateCSR}>Generate CSR</button>
        {csrResult && (
          <div>
            <textarea value={csrResult.csr} readOnly />
            <textarea value={csrResult.privateKey} readOnly />
          </div>
        )}
      </div>
    )}
  </div>
  );
}
