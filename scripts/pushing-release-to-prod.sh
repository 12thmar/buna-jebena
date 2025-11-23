#git branch -D release/2025-08-18
#git push origin --delete release/2025-08-18
# make sure everything is committed & pushed locally first
git fetch origin --prune
git checkout release/2025-08-17
# deploy your release branch instead
git push origin release/2025-08-19:prod