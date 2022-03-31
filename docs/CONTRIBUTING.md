#Testing

## Make a RC Release including a wp_launch_check.phar
Make your changes to the `wp_launch_check` source code.

Temporarily update the logic so the `wp_launch_check.phar` is created on all branches. In `.github/workflows/validate.yml`, replace the two branch names with one line:
```
- *
```

Create a Release Candidate tag based on your source branch.  The RC number should be one more, incremented on the patch version, than the most recent non-RC tag.

```
cd wp_launch_check
git fetch origin
git tag
git checkout <source-branch>
git pull
git tag -a "v1.2.3-RC1" -m "Version 1.2.3-RC1"
git push origin v1.2.3-RC1
```
When the tag is pushed, GitHub Actions will automatically create a Release including a `wp_launch_check.phar` file.

Monitor https://github.com/pantheon-systems/wp_launch_check/actions.  Watch for your tag’s workflow to complete.  If there are any failing tests, satisfy the failing test or rerun the job.

Review https://github.com/pantheon-systems/wp_launch_check/releases and ensure a `wp_launch_check.phar` file was created.

## Generate a Quay tag for cos-wpcli based on the RC above

If necessary, clone https://github.com/pantheon-systems/cos-framework-clis locally.

Checkout a new branch called `wplc-1.2.3-RC1`

In `wpcli/Dockerfile`, bump the version of WP-CLI.

In `.circleci/config.yml`, do the following so the non-default branch will push a tag to Quay.
1. Remove the if/fi lines around  `make push-wpcli-tags`
2. Remove two spaces of indentation prior to `make push-wpcli-tags`

Push the branch to GitHub.  Do not make a PR.

Browse to the [branches page in GitHub](https://github.com/pantheon-systems/cos-framework-clis/branches) and click into your branch.

Above the listing of files in the branch, look next to the commit hash for an orange circle, green checkmark, or a red x.  Click the colored icon to see the jobs running for this branch

Click Details next to the `ci/circleci: build-wpcli` job.

In the `push image to quay.io` step, look for the line that begins with `docker push` and note the tag number after `cos-wpcli:`

You can confirm this tag in Quay by browsing to https://quay.io/getpantheon/cos-wpcli

## Test your code

Use [tagged deploys](https://github.com/pantheon-systems/infrastructure/blob/master/docs/tagged-deploys.md) for the `wpcli` container on an `appserver` associated with a test site in production or on your sandbox.

Test that your code wp_launch_check code is working as expected.

Take screenshots to be used in the PRs during the Deploying steps below.

## Cleanup

Delete the RC tag in wp_launch_check on your local and in GitHub:
```
git tag -d v1.2.3-RC1
git push origin :refs/tags/v1.2.3-RC1
```

Delete the RC release on the wp_launch_check Releases page in GitHub.

Delete the RC branch on the cos-framework-clis Branches page in GitHub.

# Deploying

## Make a Release including a wp_launch_check.phar

Undo any changes you temporarily made above to `.github/workflows/validate.yml`.

Open a PR, get approval, and merge your source branch into the main branch.

Create a Release tag based on the main branch.  Use the same tag number used during testing, but omit the -RC# part.
```
cd wp_launch_check
git fetch origin
git tag
git checkout main
git pull
git tag -a "v1.2.3" -m "Version 1.2.3"
git push origin v1.2.3
```
Monitor https://github.com/pantheon-systems/wp_launch_check/actions.  Watch for your tag’s workflow to complete.  If there are any failing tests, satisfy the failing test or rerun the job.

Review https://github.com/pantheon-systems/wp_launch_check/releases and ensure a `wp_launch_check.phar` file was created.

## Bump the version of Launch Check

In your clone of https://github.com/pantheon-systems/cos-framework-clis, checkout a new branch based off master called `wplc-1.2.3`.

In `wpcli/Dockerfile`, bump the version of wp_launch_check

Push the branch to GitHub.

Open a PR referencing the above PR, get approval, and merge your source branch into the master branch.
