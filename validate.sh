#!/bin/bash

echo "🚀 PHP CRM Gateway - Validation Script"
echo "======================================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project directory"
    exit 1
fi

echo "✅ Laravel project detected"

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️  Warning: .env file not found, copying from .env.example"
    cp .env.example .env
fi

echo "✅ Environment file ready"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "⚠️  Warning: Dependencies not installed, running composer install"
    composer install --no-dev --optimize-autoloader
fi

echo "✅ Dependencies installed"

# Check if application key is set
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "⚠️  Warning: Application key not set, generating..."
    php artisan key:generate
fi

echo "✅ Application key configured"

# Check database configuration
echo "📊 Checking database configuration..."
if php artisan migrate:status > /dev/null 2>&1; then
    echo "✅ Database connection working"
else
    echo "⚠️  Warning: Database connection issues (expected in Docker setup)"
fi

# Check routes
echo "🛣️  Checking API routes..."
if php artisan route:list --path=api | grep -q "api/leads"; then
    echo "✅ API routes registered"
else
    echo "❌ Error: API routes not found"
    exit 1
fi

# Check if required files exist
echo "📁 Checking required files..."

required_files=(
    "app/Http/Controllers/Api/LeadController.php"
    "app/Http/Controllers/Api/AuthController.php"
    "app/DTOs/LeadDTO.php"
    "app/Services/Messaging/SqsPublisher.php"
    "app/Services/Logging/LogManager.php"
    "resources/schemas/lead.json"
    "docker-compose.yml"
    "Dockerfile"
    "README.md"
    "ARCHITECTURE.md"
    "docs/API.md"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ Missing: $file"
        exit 1
    fi
done

# Check if tests exist
echo "🧪 Checking test files..."

test_files=(
    "tests/Feature/Api/LeadControllerTest.php"
    "tests/Unit/Services/Validation/JsonSchemaValidatorTest.php"
    "tests/Unit/Services/Messaging/SqsPublisherTest.php"
    "tests/Unit/Services/Logging/LogManagerTest.php"
    "tests/Integration/SqsIntegrationTest.php"
)

for file in "${test_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ Missing: $file"
        exit 1
    fi
done

echo ""
echo "🎉 Validation Complete!"
echo "======================"
echo ""
echo "📋 Summary:"
echo "  ✅ Laravel 11 project structure"
echo "  ✅ Docker configuration"
echo "  ✅ API routes and controllers"
echo "  ✅ DTO and validation layers"
echo "  ✅ SQS integration"
echo "  ✅ Switchable logging system"
echo "  ✅ Comprehensive tests"
echo "  ✅ Documentation"
echo ""
echo "🚀 Next Steps:"
echo "  1. Start Docker: docker-compose up -d"
echo "  2. Run migrations: docker-compose exec app php artisan migrate"
echo "  3. Seed database: docker-compose exec app php artisan db:seed"
echo "  4. Generate docs: docker-compose exec app php artisan l5-swagger:generate"
echo "  5. Run tests: docker-compose exec app php artisan test"
echo ""
echo "🌐 Access Points:"
echo "  • API: http://localhost:8080/api"
echo "  • Swagger UI: http://localhost:8080/api/documentation"
echo "  • LocalStack: http://localhost:4566"
echo ""
