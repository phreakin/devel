# Configuration Guide

## Environment Setup

Copy `.env.example` to `.env` and configure for your environment:

```bash
cp .env.example .env
```

Then edit `.env` with your specific settings.

## Database Configuration (When Added)

When you add database support, configure in `.env`:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=devel
DB_USER=app
DB_PASS=secret
```

## Secrets and API Keys

Never commit `.env` or secrets. Use `.env.example` as a template for team members.

For CI/CD environments, configure secrets in GitHub Actions using the `copilot` environment (see copilot-instructions.md).
