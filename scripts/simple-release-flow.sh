#!/usr/bin/env bash
set -euo pipefail

# --- Config you can override via env ---
BASE_BRANCH="${BASE_BRANCH:-prod}"          # PR target (deployment branch)
SRC_BRANCH="${SRC_BRANCH:-$(git rev-parse --abbrev-ref HEAD)}"   # branch you’re releasing from
REL_PREFIX="${REL_PREFIX:-release}"         # release branch prefix
# ---------------------------------------

need() { command -v "$1" >/dev/null || { echo "Missing command: $1"; exit 1; }; }
need git

# Ensure clean tree
if [[ -n "$(git status --porcelain)" ]]; then
  echo "❌ Uncommitted changes. Commit/stash first."; git status --porcelain; exit 1
fi

# Make sure we have the latest
git fetch origin

# Create release branch
DATE_TAG="$(date +%Y-%m-%d)"
REL_BRANCH="${REL_PREFIX}/${DATE_TAG}"
echo "==> Creating ${REL_BRANCH} from ${SRC_BRANCH}"
git switch -c "${REL_BRANCH}" "${SRC_BRANCH}" 2>/dev/null || git checkout -b "${REL_BRANCH}" "${SRC_BRANCH}"

# Optional: add version bump / changelog here if you want
# git commit -am "chore: bump version for ${DATE_TAG}" || true

# Push release branch
git push -u origin "${REL_BRANCH}"

# Try to open a PR (uses GitHub CLI if available)
if command -v gh >/dev/null; then
  echo "==> Creating PR to ${BASE_BRANCH} with gh"
  gh pr create --base "${BASE_BRANCH}" --head "${REL_BRANCH}" \
    --title "Release ${DATE_TAG}" \
    --body "Automated release PR from \`${SRC_BRANCH}\` → \`${BASE_BRANCH}\` on ${DATE_TAG}."
else
  # Derive repo slug for PR URL
  ORIGIN_URL="$(git remote get-url origin)"
  if [[ "${ORIGIN_URL}" =~ github.com[:/](.*)/(.*)(\.git)?$ ]]; then
    OWNER="${BASH_REMATCH[1]}"; REPO="${BASH_REMATCH[2]}"
    PR_URL="https://github.com/${OWNER}/${REPO}/compare/${BASE_BRANCH}...${REL_BRANCH}?expand=1"
    echo "==> Open this URL to create the PR:"
    echo "${PR_URL}"
  else
    echo "⚠️ Could not detect GitHub repo URL. Install GitHub CLI (gh) for auto-PR."
  fi
fi

echo "✅ Release branch ready: ${REL_BRANCH}. Merge the PR into ${BASE_BRANCH} to deploy."
