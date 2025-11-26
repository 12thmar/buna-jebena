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
git push origin "$BRANCH" --force

# (Optional) clean up old release branches except today's
echo "🧹 Cleaning old release branches..."
for OLD in $(git branch -r | grep 'origin/release/' | grep -v "$BRANCH"); do
  CLEAN=${OLD#origin/}
  echo "   🔥 Deleting $CLEAN ..."
  git push origin --delete "$CLEAN" || true
  git branch -D "$CLEAN" 2>/dev/null || true
done

# Push prod to trigger deploy
echo "🚀 Deploying prod..."
git checkout prod
git push origin prod

echo "✨ Done. Prod deployed with CURRENT approved features."
