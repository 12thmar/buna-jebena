#!/usr/bin/env bash
set -euo pipefail

# 1. Generate today's release name
TODAY=$(date +%Y-%m-%d)
BRANCH="release/$TODAY"

echo "📦 Creating release: $BRANCH"

# 2. Fetch all remote branches
git fetch origin --prune

# 3. Checkout main and pull latest changes
git checkout main
git pull origin main

# 4. Create or reset today's release branch
git checkout -B "$BRANCH"

# 5. Push today's release branch
echo "⬆️  Pushing $BRANCH to origin..."
git push origin "$BRANCH"

# 6. Push today's release branch to prod
echo "🚀 Deploying $BRANCH → prod..."
git push origin "$BRANCH:prod"

# 7. Cleanup old release branches (local + remote)
echo "🧹 Cleaning old release branches..."

# List all release branches except today's,
# delete local and remote versions.
for OLD in $(git branch -r | grep 'origin/release/' | grep -v "$BRANCH"); do
    CLEAN=${OLD#origin/}
    echo "   🔥 Deleting $CLEAN ..."

    # Delete remote branch
    git push origin --delete "$CLEAN" || true

    # Delete local branch if exists
    git branch -D "$CLEAN" 2>/dev/null || true
done

echo "✨ Done. Only $BRANCH kept.."
