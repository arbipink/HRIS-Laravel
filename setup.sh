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
docker compose exec php php artisan key:generate

sleep 10

echo "🗄️ Running database migrations and seeders..."
docker compose exec php php artisan migrate:fresh --seed

echo "🎨 Compiling frontend assets..."
docker compose run --rm node sh -c "npm install && npm audit fix && npm run build && npm audit fix"

echo "✨ Setup complete! your application is ready."
echo "Open the site here http://localhost/"
echo "Manage the database here http://localhost:8080/"
echo "Default Email: admin@company.com"
echo "Default Password: password"