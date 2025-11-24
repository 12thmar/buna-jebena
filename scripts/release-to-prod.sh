#!/usr/bin/env bash
set -euo pipefail

TODAY=$(date +%Y-%m-%d)
BRANCH="release/$TODAY"

echo "Creating / updating $BRANCH and pushing to prod…"

# make sure we're up to date
git fetch origin --prune
git checkout main
git pull origin main

# create or reset today's release branch from main
git checkout -B "$BRANCH"

# push the release branch itself (optional but nice)
git push origin "$BRANCH"

# push today's release to prod branch
git push origin "$BRANCH:prod"

echo "Done. Deployed $BRANCH to prod."
