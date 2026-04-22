# Database Setup Guide

This project uses **Doctrine ORM** with **MySQL/MariaDB** for data persistence.

## Quick Setup

### 1. Create Database

```bash
# Using MySQL CLI
mysql -u root -p -e "CREATE DATABASE devel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or using your database client (e.g., phpMyAdmin, DBeaver)
```

### 2. Configure Environment

```bash
cp .env.example .env

# Edit .env and set database credentials:
DB_DRIVER=pdo_mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=devel_db
DB_USER=root
DB_PASS=your_password
```

### 3. Create Schema

```bash
# Generate database schema from entities
vendor/bin/doctrine orm:schema-tool:create

# Or if schema exists, update it
vendor/bin/doctrine orm:schema-tool:update --force
```

### 4. Verify

```bash
# Check the schema was created
mysql -u root devel_db -e "SHOW TABLES;"

# Should show:
# +------------------+
# | Tables_in_devel_db |
# +------------------+
# | conversations    |
# | messages         |
# | users            |
# +------------------+
```

## Entity Structure

### Users Table
```sql
CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  active BOOLEAN DEFAULT true,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### Conversations Table
```sql
CREATE TABLE conversations (
  id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT,
  ai_model VARCHAR(100) DEFAULT 'gpt-4',
  active BOOLEAN DEFAULT true,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Messages Table
```sql
CREATE TABLE messages (
  id VARCHAR(36) PRIMARY KEY,
  conversation_id VARCHAR(36) NOT NULL,
  role VARCHAR(10) DEFAULT 'user',
  content LONGTEXT NOT NULL,
  metadata LONGTEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id)
);
```

## Common Doctrine Commands

```bash
# View the schema that will be created
vendor/bin/doctrine orm:schema-tool:create --dump-sql

# Create tables
vendor/bin/doctrine orm:schema-tool:create

# Update schema (if entities change)
vendor/bin/doctrine orm:schema-tool:update --force

# Drop and recreate (careful!)
vendor/bin/doctrine orm:schema-tool:drop --force
vendor/bin/doctrine orm:schema-tool:create

# Clear query cache
vendor/bin/doctrine orm:clear-cache:query
```

## Using Doctrine Migrations (Optional)

For production, use migrations to manage schema versions:

```bash
# Create migrations directory
mkdir -p migrations

# Generate a new migration
vendor/bin/doctrine-migrations generate

# Run migrations
vendor/bin/doctrine-migrations migrate

# Check migration status
vendor/bin/doctrine-migrations status
```

## Accessing the Database

### MySQL CLI
```bash
mysql -u root -p devel_db

# Common queries:
SELECT * FROM users;
SELECT * FROM conversations WHERE user_id = 'uuid';
SELECT * FROM messages WHERE conversation_id = 'uuid' ORDER BY created_at ASC;
```

### Using Doctrine
```php
$entityManager = require 'config/doctrine.php';

// Find a user
$user = $entityManager->find(User::class, $userId);

// Get all conversations for a user
$repository = $entityManager->getRepository(Conversation::class);
$conversations = $repository->findBy(['user' => $user]);
```

## Development Workflow

When you modify entities:

1. Update the entity class in `src/Entity/`
2. Run: `vendor/bin/doctrine orm:schema-tool:update --force`
3. Test the changes
4. If using migrations, run: `vendor/bin/doctrine-migrations generate`

## Debugging

Enable SQL logging in development:

```php
// In config/doctrine.php
$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
```

This will echo all SQL queries executed.

## Testing with Database

For integration tests that need a database:

1. Use a test database: `devel_db_test`
2. Set up test fixtures in your test bootstrap
3. Roll back transactions after each test to keep state clean

See `tests/Integration/` for examples.
