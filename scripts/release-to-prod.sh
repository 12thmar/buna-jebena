#!/usr/bin/env bash
set -euo pipefail

TODAY=$(date +%Y-%m-%d)
BRANCH="release/$TODAY"

echo "📦 Creating release from current prod: $BRANCH"

git fetch origin --prune

# Checkout prod and pull latest
git checkout prod
git pull origin prod

# Create today's release branch from prod
git checkout -B "$BRANCH"

echo "⬆️  Pushing $BRANCH to origin..."
git push origin "$BRANCH"

# Deploy prod by pushing it to itself (trigger CI)
echo "🚀 Deploying prod..."
git push origin prod

echo "✨ Done. Prod deployed with CURRENT approved features."
