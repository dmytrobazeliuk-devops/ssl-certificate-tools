# GitHub Repository Setup Instructions

## Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com) and sign in
2. Click the "+" icon in the top right corner
3. Select "New repository"
4. Repository name: `ssl-certificate-tools`
5. Description: `Comprehensive online SSL certificate management suite - Check, Generate, Convert, Match certificates and Generate CSR`
6. Set to **Public**
7. **DO NOT** initialize with README, .gitignore, or license (we already have these)
8. Click "Create repository"

## Step 2: Set Repository Website URL

1. Go to your repository settings
2. Scroll down to "About" section
3. In the "Website" field, enter: `https://devsecops.cv/tools/ssl`
4. Click "Save changes"

## Step 3: Upload Files to GitHub

### Option A: Using GitHub Web Interface

1. Go to your repository
2. Click "Add file" → "Upload files"
3. Upload all files from the `github-repo/ssl-certificate-tools/` directory:
   - `README.md`
   - `package.json`
   - `.gitignore`
   - `src/app/tools/ssl/page-example.tsx`
   - `api/tools/ssl/check.php`
   - `api/tools/ssl/generate-csr.php`
   - All files from `screenshots/` directory

### Option B: Using Git Command Line

```bash
cd /var/www/vhosts/devsecops.cv/httpdocs/github-repo/ssl-certificate-tools

# Initialize git repository
git init
git add .
git commit -m "Initial commit: SSL Certificate Tools"

# Add remote (replace YOUR_USERNAME with your GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/ssl-certificate-tools.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 4: Verify Repository

1. Check that all files are uploaded
2. Verify README.md displays correctly with screenshots
3. Confirm the website URL is set in "About" section
4. Test that screenshots are visible

## Important Notes

- The code in this repository is a **simplified example** showing the main structure
- Full implementation includes additional features and complete error handling
- Environment variables (like `TURNSTILE_SECRET_KEY`) should be configured in your deployment
- Do not commit `.env` files or sensitive keys to the repository

