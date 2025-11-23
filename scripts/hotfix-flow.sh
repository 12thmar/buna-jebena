git checkout prod
git pull origin prod
git checkout -b hotfix/logo-path
# make the fix, commit
git push -u origin hotfix/logo-path
# PR -> base: prod, compare: hotfix/logo-path
# merge after CI passes → deploys
# then back-merge prod -> main to keep histories aligned
git checkout main && git pull && git merge --no-ff prod && git push

