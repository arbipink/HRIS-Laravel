#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "🚀 Starting environment setup..."

if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "💡 Please create a .env file with your database credentials before running this script."
    exit 1
fi

echo "📦 Spinning up Docker containers..."
docker compose up -d --build

echo "📥 Installing Composer dependencies inside PHP container..."
docker compose exec php composer install

echo "🔑 Generating application key..."
docker compose exec php php artisan key:generate --skip-if-set || true

sleep 10

echo "🗄️ Running database migrations and seeders..."
docker compose exec php php artisan migrate --seed

echo "🎨 Compiling frontend assets..."
if [ -x "$(command -v npm)" ]; then
    echo "Using local npm installation..."
    npm install
    npm audit fix
    npm run build
else
    echo "⚠️ npm not found locally. Using a temporary Docker Node container instead..."
    docker run --rm -v "$(pwd)":/app -w /app node:alpine sh -c "npm install && npm audit fix && npm run build"
fi

echo "✨ Setup complete! your application is ready."