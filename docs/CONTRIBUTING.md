# Testing

## 1. Make a RC Release including a `wp_launch_check.phar`
* Make your changes to the `wp_launch_check` source code.
* Determine the version number of the release by running `git tag --list`. The RC version will be one more than the latest tag.
* Create a new branch off of the branch that your changes were made to and name it something like `wplc-v1.2.3-rc1` (based on the latest tag incremented on the patch version). This branch will just be for testing the release and should be different from the branch you are creating for the changes in the PR.
* Temporarily update the logic so the `wp_launch_check.phar` is created on all branches. In `.github/workflows/validate.yml`, replace the two branch names with one line:
```
- '**'
```
* Create a Release Candidate tag based on your RC branch.
```
git tag -a "v1.2.3-RC1" -m "Version 1.2.3-RC1"
git push origin v1.2.3-RC1
```
* When the tag is pushed, GitHub Actions will automatically create a Release including a `wp_launch_check.phar` file.
* Monitor https://github.com/pantheon-systems/wp_launch_check/actions.  Watch for your tag’s workflow to complete.  If there are any failing tests, satisfy the failing test or rerun the job.
* Review https://github.com/pantheon-systems/wp_launch_check/releases and ensure a `wp_launch_check.phar` file was created.

## 2. Generate a Quay tag for cos-wpcli based on the RC above

To perform the next steps, you will need a local copy of the [`pantheon-systems/cos-framework-clis`](https://github.com/pantheon-systems/cos-framework-clis) repository.

```
git clone git@github.com:pantheon-systems/cos-framework-clis.git
```

* Checkout a new branch called `wplc-1.2.3-RC1`
* In `wpcli/Dockerfile`, bump the version of WP Launch Check.
* In `.circleci/config.yml`, do the following so the non-default branch will push a tag to Quay.
  1. Remove the `if`/`fi` lines around  `make push-wpcli-tags`
  2. Remove two spaces of indentation prior to `make push-wpcli-tags`
* Push the branch to GitHub.  **Do not make a PR.**
* Browse to the [branches page in GitHub](https://github.com/pantheon-systems/cos-framework-clis/branches) and click into your branch.
* Above the listing of files in the branch, look next to the commit hash for an orange circle, green checkmark, or a red x.  Click the colored icon to see the jobs running for this branch
* Click Details next to the `ci/circleci: build-wpcli` job.
* In the `push image to quay.io` step, look for the line that begins with `docker push` and note the tag number after `cos-wpcli:`
  * You can confirm this tag in Quay by browsing to https://quay.io/getpantheon/cos-wpcli

## 3. Test your code

* Use [tagged deploys](https://github.com/pantheon-systems/infrastructure/blob/master/docs/tagged-deploys.md) for the `wpcli` container on an `appserver` associated with a test site in production or on your sandbox.
* Test that your code `wp_launch_check` code is working as expected.
* Take screenshots to be used in the PRs during the Deploying steps below, if applicable.

## 4. Cleanup

* Delete the RC tag in `wp_launch_check` on your local and in GitHub:
```
git tag -d v1.2.3-RC1
git push origin :refs/tags/v1.2.3-RC1
```
* Delete the RC branch on the `wp_launch_check` Branches page in GitHub (if you pushed the branch to the repository).
* Delete the RC release on the `wp_launch_check` Releases page in GitHub.
* Delete the RC branch on the `cos-framework-clis` Branches page in GitHub.

# 5. Deploying

## Make a Release including a `wp_launch_check.phar`

* On your local environment, switch to the branch that you will use to create your PR (make sure that any changes you temporarily made above to `.github/workflows/validate.yml` have been removed).
* Open a PR, get approval, and merge your source branch into the `main` branch.
* Create a Release tag based on the main branch.  Use the same tag number used during testing, but omit the -RC# part.
```
git checkout main
git pull
git tag -a "v1.2.3" -m "Version 1.2.3"
git push origin v1.2.3
```
* Monitor https://github.com/pantheon-systems/wp_launch_check/actions.  Watch for your tag’s workflow to complete.  If there are any failing tests, satisfy the failing test or rerun the job.
  * **Note:** Sometimes, the WP Launch Check tests will fail because the expected output in the Behat tests is different than the actual output. This is not uncommon and frequently can be resolved by running the tests again.
* Review https://github.com/pantheon-systems/wp_launch_check/releases and ensure a `wp_launch_check.phar` file was created.

## Bump the version of Launch Check

* In your clone of https://github.com/pantheon-systems/cos-framework-clis, checkout a new branch based off `master` called `wplc-1.2.3`. Delete your old, `-RC1` branch to avoid confusion.
* In `wpcli/Dockerfile`, bump the version of `wp_launch_check` by incrementing the `wp_launch_version` `ARG` to the release that that you just pushed.
* Push the branch to GitHub.
* Open a PR referencing the above PR, get approval, and merge your source branch into the `master` branch.
