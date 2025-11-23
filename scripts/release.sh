#!/usr/bin/env bash
set -euo pipefail

# ---------- config (can override via env) ----------
BRANCH="${BRANCH:-prod}"     # target branch to deploy from
TAG_OPT="${TAG_OPT:-auto}"   # auto | none | <custom-tag>
FRONTEND_DIR="${FRONTEND_DIR:-frontend}"
# ---------------------------------------------------

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

need() { command -v "$1" >/dev/null || { echo "Missing: $1"; exit 1; }; }
need git; need node; need npm

# Node 20+ for Vite 7
NODE_MAJOR="$(node -p "process.versions.node.split('.')[0]")"
if (( NODE_MAJOR < 20 )); then
  echo "Node >= 20 required (found $(node -v))."; exit 1
fi

# Clean working tree
if [[ -n "$(git status --porcelain)" ]]; then
  echo "Uncommitted changes found. Commit or stash first."; git status --porcelain; exit 1
fi

# Gentle check: workflow present
if [[ ! -f ".github/workflows/deploy.yml" ]]; then
  echo "WARN: .github/workflows/deploy.yml not found. The push won't deploy until it's added."
fi

# Build frontend
echo "==> Building $FRONTEND_DIR"
pushd "$FRONTEND_DIR" >/dev/null
npm ci --include=dev
npm run build

# Tailwind sanity: ensure non-empty CSS emitted
CSS_FILE="$(ls -1 dist/assets/*.css 2>/dev/null | head -n1 || true)"
if [[ -z "${CSS_FILE:-}" ]]; then
  echo "ERROR: No CSS emitted (dist/assets/*.css not found)."; exit 1
fi
if [[ ! -s "$CSS_FILE" ]]; then
  echo "ERROR: CSS emitted but empty: $CSS_FILE"; exit 1
fi
echo "OK: CSS emitted -> $(basename "$CSS_FILE") ($(stat -c%s "$CSS_FILE" 2>/dev/null || wc -c <"$CSS_FILE") bytes)"
popd >/dev/null

# Push current HEAD to prod
SRC_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
echo "==> Pushing '$SRC_BRANCH' -> '$BRANCH'"
git fetch origin
git push origin HEAD:"$BRANCH"

# Optional tag
case "$TAG_OPT" in
  none) echo "Skipping tag." ;;
  auto) TAG="rel-$(date +%Y%m%d-%H%M%S)-$(git rev-parse --short HEAD)"; git tag "$TAG" || true; git push origin "$TAG" || true; echo "Tagged $TAG";;
  *)    TAG="$TAG_OPT"; git tag "$TAG" || true; git push origin "$TAG" || true; echo "Tagged $TAG";;
esac

echo "✅ Done. Watch GitHub → Actions for the deploy on branch '$BRANCH'."

