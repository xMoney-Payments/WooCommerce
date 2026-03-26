# xMoney Payments Plugin Tests

This directory contains the test suite for the xMoney Payments WooCommerce plugin.

## Requirements

- PHP 7.4 or higher
- PHPUnit 9.5 or higher
- WordPress test library
- WooCommerce plugin
- MySQL/MariaDB database for testing

## Installation

### 1. Install Composer Dependencies

```bash
composer install
```

### 2. Set Up WordPress Test Environment

Run the installation script to set up the WordPress test suite:

```bash
./tests/bin/install-wp-tests.sh wordpress_test root 'root' localhost latest
```

Replace the database credentials as needed:
- `wordpress_test` - Database name for tests
- `root` - Database user
- `root` - Database password
- `localhost` - Database host
- `latest` - WordPress version (or specific version like `6.4`)

## Running Tests

### Run All Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

### Run Unit Tests Only

```bash
composer test:unit
# or
./vendor/bin/phpunit --testsuite unit
```

### Run Integration Tests Only

```bash
composer test:integration
# or
./vendor/bin/phpunit --testsuite integration
```

### Run Tests with Coverage Report

```bash
composer test:coverage
# or
./vendor/bin/phpunit --coverage-html tests/coverage
```

The coverage report will be generated in `tests/coverage/` directory.

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/unit/test-gateway.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit --filter test_gateway_initialization
```

## Test Structure

```
tests/
├── bootstrap.php                    # Test bootstrap file
├── class-xmoney-payments-test-case.php  # Base test case class
├── unit/                            # Unit tests
│   ├── test-plugin-initialization.php
│   ├── test-gateway.php
│   ├── test-helper-notify.php
│   ├── test-helper-response.php
│   ├── test-helper-processor.php
│   ├── test-status-updater.php
│   ├── test-logger.php
│   ├── test-server-to-server.php
│   ├── test-ajax-handlers.php
│   ├── test-rest-api.php
│   └── test-install.php
├── integration/                     # Integration tests
│   ├── test-checkout-flow.php
│   └── test-database.php
└── bin/
    └── install-wp-tests.sh          # WP test suite installer
```

## Test Categories

### Unit Tests

Unit tests focus on testing individual components in isolation:

- **Plugin Initialization** - Tests plugin loading and constants
- **Gateway** - Tests payment gateway configuration and methods
- **Helper Notify** - Tests request encoding and checksum generation
- **Helper Response** - Tests response decryption and validation
- **Helper Processor** - Tests configuration retrieval and utility methods
- **Status Updater** - Tests order status updates based on payment status
- **Logger** - Tests logging functionality and database operations
- **Server to Server** - Tests IPN/webhook handling
- **AJAX Handlers** - Tests AJAX action registration and handlers
- **REST API** - Tests REST API endpoints
- **Install** - Tests plugin installation and database setup

### Integration Tests

Integration tests verify multiple components working together:

- **Checkout Flow** - Tests complete payment flows
- **Database** - Tests database operations and integrity

## Writing New Tests

1. Create a new test file in the appropriate directory (`unit/` or `integration/`)
2. Name the file with `test-` prefix (e.g., `test-my-feature.php`)
3. Extend `Xmoney_Payments_Test_Case` for access to helper methods
4. Use `@dataProvider` for parameterized tests
5. Use `@runInSeparateProcess` for tests that need isolation

Example:

```php
<?php
class Test_My_Feature extends Xmoney_Payments_Test_Case {
    
    public function test_something() {
        // Create test data
        $order = $this->create_test_order();
        
        // Perform action
        $result = my_function( $order->get_id() );
        
        // Assert
        $this->assertEquals( 'expected', $result );
    }
}
```

## Test Helpers

The base test case provides several helper methods:

- `create_simple_product()` - Creates a WooCommerce product
- `create_test_order()` - Creates a WooCommerce order
- `create_test_configuration()` - Sets up plugin configuration
- `generate_encrypted_response()` - Creates mock encrypted responses
- `assert_gateway_initialized()` - Verifies gateway is ready

## Coding Standards

Run PHP CodeSniffer to check coding standards:

```bash
composer phpcs
```

Auto-fix coding standard issues:

```bash
composer phpcbf
```

## Continuous Integration

For CI environments, you can set the following environment variables:

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
export WP_CORE_DIR=/path/to/wordpress
```

## Troubleshooting

### Tests fail with "Could not find wp-tests-config.php"

Run the install script to set up the test environment:

```bash
./tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### WooCommerce not found

Ensure WooCommerce is installed in the test WordPress instance or update the bootstrap path.

### Database connection errors

Verify database credentials in the install script match your local setup.

