# Copilot Instructions for devel

This is a PHP 8.5 project. Below are conventions and setup instructions to help maintain consistency across Copilot sessions.

## Project Setup

**PHP Version:** 8.5 (configured in `.idea/php.xml`)

### Initial Installation
```bash
composer install
```

This installs all dependencies defined in `composer.json`:
- PSR standards (logging, HTTP messages)
- `nyholm/psr7` - Modern HTTP message implementation
- `nikic/fast-route` - High-performance routing
- `vlucas/phpdotenv` - Environment variable management
- `phpunit/phpunit` - Testing framework
- `phpstan/phpstan` - Static code analysis

### Setup Environment Variables
```bash
cp .env.example .env
# Edit .env with your local settings
```

## Build, Test, and Lint Commands

### Installation
```bash
composer install
```

### Running Tests
```bash
composer test          # Run all tests
vendor/bin/phpunit tests/Unit           # Unit tests only
vendor/bin/phpunit tests/Unit/Entity/   # Entity tests only
vendor/bin/phpunit tests/Unit/Http/     # HTTP/API tests only
vendor/bin/phpunit tests/Unit/ApplicationTest.php  # Single test file
```

### Static Analysis
```bash
vendor/bin/phpstan analyse src/
```

### Database Commands

**Setup Database:**
```bash
# Create database and update environment
# 1. Create MySQL database:
mysql -u root -e "CREATE DATABASE devel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Generate database schema from entities:
vendor/bin/doctrine orm:schema-tool:create

# Or drop and recreate:
vendor/bin/doctrine orm:schema-tool:drop --force
vendor/bin/doctrine orm:schema-tool:create
```

**Run Migrations:**
```bash
# Create new migration
vendor/bin/doctrine-migrations generate

# Run migrations
vendor/bin/doctrine-migrations migrate

# Status
vendor/bin/doctrine-migrations status
```

**Database Access:**
```bash
# Connect to database
mysql -u root devel_db

# Query examples:
SELECT * FROM users;
SELECT * FROM conversations WHERE user_id = 'uuid';
SELECT * FROM messages WHERE conversation_id = 'uuid';
```

### Code Quality (when tools are added)
```bash
composer lint         # Run PHP linter (when configured)
composer phpcs        # Code style check (when configured)
composer phpcs:fix    # Auto-fix code style issues (when configured)
```

### Running the Application
Development server:
```bash
php -S localhost:8000 -t public/
```

**API endpoint:**
```bash
# Health check
curl http://localhost:8000/api/health

# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123","name":"John"}'
```

See `docs/API_REFERENCE.md` for complete API documentation.

## Directory Structure

```
├── src/                     # Application source code (PSR-4: App\)
│   ├── Entity/              # Doctrine ORM entities
│   │   ├── User.php         # User model (id, email, name, active)
│   │   ├── Conversation.php # AI conversation model
│   │   └── Message.php      # Individual message model
│   └── Application.php      # Main application class
├── tests/                   # Test files
│   ├── Unit/
│   │   ├── Entity/          # Entity unit tests (21 tests ✓)
│   │   │   ├── UserTest.php
│   │   │   ├── ConversationTest.php
│   │   │   └── MessageTest.php
│   │   └── ApplicationTest.php
│   ├── Integration/         # Integration tests
│   └── bootstrap.php        # Test bootstrap
├── config/                  # Configuration
│   ├── doctrine.php         # Doctrine ORM configuration
│   └── README.md            # Configuration documentation
├── public/                  # Web root
│   └── index.php            # Application entry point
├── docs/                    # Project documentation
├── composer.json            # Composer configuration
├── phpunit.xml              # PHPUnit configuration
├── phpstan.neon.json        # PHPStan configuration
├── .env.example             # Environment variables template
├── .gitignore               # Git ignore rules
└── vendor/                  # Composer dependencies (git-ignored)
```

### PSR-4 Autoloading
- `App\` namespace → `src/` directory
- `Tests\` namespace → `tests/` directory

### Entity Relationships

```
User (1) ──────── (Many) Conversation
            ↑
          owns

Conversation (1) ──────── (Many) Message
           ↑
        contains
```

## Code Conventions

### PHP Standards
- **PHP Version:** 8.5 features and syntax
- **Type Hints:** Always use strict type declarations where possible
  ```php
  declare(strict_types=1);
  ```
- **Namespace:** Organize code in logical namespaces matching directory structure
- **PSR-12:** Follow PSR-12 coding standard for consistency
- **Visibility:** Prefer explicit visibility modifiers (public, private, protected)

### Class and Method Naming
- Classes: PascalCase (e.g., `UserService`, `DatabaseConnection`)
- Methods/Functions: camelCase (e.g., `getUserData`, `processRequest`)
- Constants: UPPER_SNAKE_CASE (e.g., `MAX_RETRIES`, `DEFAULT_TIMEOUT`)
- Properties: camelCase (e.g., `$userId`, `$isActive`)

### Documentation
- Use PHPDoc blocks for public classes and methods
- Document parameters, return types, and exceptions
- Keep comments focused on "why", not "what" (code should be self-documenting)

### Error Handling
- Use typed exceptions where possible
- Create custom exceptions extending `\Exception` or appropriate parent class
- Handle exceptions explicitly; avoid silent failures

## Architecture Notes

### Core Patterns

**API Layer**
- `App\Http\Router` - FastRoute-based request dispatcher
- `App\Http\Request` - Standardized request object with JSON/form parsing
- `App\Http\ApiResponse` - Standardized JSON response builder with status codes
- `App\ApiApplication` - Main API application with route handlers

**Database & ORM**
- Uses Doctrine ORM for database abstraction
- Entities use PHP 8 Attributes for mapping
- UUID identifiers for all entities (privacy-preserving)
- Automatic timestamps (createdAt/updatedAt) on all entities
- Relationships:
  - `User` → owns many `Conversation`s
  - `Conversation` → contains many `Message`s
  - Cascade delete: removing Conversation removes Messages

**Entity Structure**
- **User**: Manages authentication, tracks conversations
- **Conversation**: Session/thread for AI interactions, can use different AI models
- **Message**: Individual message in conversation (user or assistant role)

**Repository Pattern**
- `UserRepository` - Query users by ID, email, active status
- `ConversationRepository` - Query conversations by user, active status

**Application Bootstrap**
- Entry point: `public/index.php` - loads Composer autoloader and environment
- Uses `vlucas/phpdotenv` to load `.env` configuration
- Doctrine configured in `config/doctrine.php`
- Handles CORS headers and API error responses

**Testing**
- Unit tests: `tests/Unit/` - test individual classes, entities, API response/request (40 passing ✓)
- Entity tests verify relationships, timestamps, and accessors
- HTTP tests verify API response formats and request parsing
- Integration tests: `tests/Integration/` - test multiple components together
- Run with PHPUnit via Composer or direct invocation

**Static Analysis**
- PHPStan runs at level 5 (strict checking) on `src/` directory
- Catches type errors and common bugs before testing

**API Design**
- RESTful endpoints following standard patterns
- Standardized JSON responses with success/error format
- Status codes: 200 (OK), 201 (Created), 204 (No Content), 4xx (errors), 5xx (server errors)
- Request validation on all endpoints
- CORS headers for browser-based clients
- TODO: JWT authentication, password hashing, input sanitization

As the project grows, document:
- JWT token generation and validation
- Password hashing (bcrypt)
- Authorization middleware (role-based access)
- AI service integration
- WebSocket for real-time messages

## Development Workflow

1. **Before making changes:** Check for existing documentation in `docs/` directory
2. **Testing:** Write tests for new functionality before or alongside implementation
3. **Code review:** Changes should be tested and follow conventions above
4. **Git commits:** Use clear, descriptive commit messages

## Tools and Configuration

- **IDE:** PhpStorm (detected from `.idea` directory)
- **Dependency Management:** Composer
- **Language:** PHP 8.5

### MCP Servers Available

This project is configured with MCP servers to enhance Copilot's capabilities:

#### PHP Language Server
Provides code intelligence including:
- Go-to-definition
- Hover information
- Symbol completion
- Diagnostics and errors

**Requirements:** PHP CLI installed and in PATH

#### Playwright Browser Automation
Supports browser testing and web automation:
- Inspect and interact with web pages
- Test web-based features
- Screenshot capture
- Network request analysis

**Requirements:** Node.js + npm (Playwright installed via npm)

### Adding New Tools
When adding linters, testing frameworks, or other tools:
1. Update `composer.json` with new dependencies
2. Document the new command(s) in this file
3. Add any required configuration files to the repository

## Browser Testing with Playwright

When building web-based features, Playwright can be used for automated browser testing:

```bash
# Run Playwright tests
npx playwright test

# Run tests in headed mode (with visible browser)
npx playwright test --headed

# Debug tests interactively
npx playwright test --debug

# Run tests for specific browser
npx playwright test --project=chromium
```

See `tests/` directory for test files when Playwright tests are added.

## Useful References

- [PHP 8.5 Documentation](https://www.php.net/docs.php)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [Composer Documentation](https://getcomposer.org/doc/)
- [Playwright Documentation](https://playwright.dev/)
