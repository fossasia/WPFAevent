# Deploying WPFAevent to EC2 with GitHub Actions

The [`Deploy Plugin to EC2`](../.github/workflows/deploy-ec2.yml) workflow updates a
git checkout on an EC2 instance after a commit reaches `main`. It can also be run
manually. The deployment job is skipped until a repository administrator opts in
by setting `EC2_DEPLOY_ENABLED` to `true`.

Only repository administrators can complete the GitHub configuration. Secrets in
a fork are not available to the upstream repository.

## Prepare the EC2 instance

Install Git, PHP, Composer, and WP-CLI if the site uses it. Clone this repository
at the WordPress plugin path, for example:

```bash
cd /var/www/html/wp-content/plugins
git clone https://github.com/fossasia/WPFAevent.git event-plugin
cd event-plugin
composer install --no-dev --prefer-dist --optimize-autoloader
```

The SSH user must be able to update this checkout. Keep production-only files and
configuration outside the checkout because deployment stops when tracked or
untracked files are present.

## Configure SSH access

Create a dedicated SSH key for deployment. Add its public key to the deployment
user's `~/.ssh/authorized_keys` file on EC2. Store the complete private key as the
`EC2_SSH_KEY` Actions secret in the repository's `production` environment.

Retrieve the server's SHA256 host-key fingerprint from a trusted administrative
connection and compare it with the fingerprint reported by EC2 before saving it.
Do not obtain and trust the fingerprint through the deployment connection itself.

## Enable deployment and configure the production environment

In **Settings → Secrets and variables → Actions**, create the repository variable
`EC2_DEPLOY_ENABLED` with the value `true`. Keeping this variable unset prevents
the upstream workflow from attempting a deployment before an environment is
ready.

Next, in **Settings → Environments**, create an environment named `production`.
Add deployment protection rules and required reviewers when appropriate, then
create the following environment variables:

| Variable | Required | Example |
| --- | --- | --- |
| `EC2_HOST` | Yes | `ec2-host.example.com` |
| `EC2_USER` | Yes | `deploy` |
| `EC2_PORT` | No | `22` |
| `EC2_PLUGIN_PATH` | Yes | `/var/www/html/wp-content/plugins/event-plugin` |
| `EC2_DEPLOY_BRANCH` | No | `main` |
| `EC2_HOST_FINGERPRINT` | Yes | `SHA256:...` |

Environment-level configuration limits the credentials to the production job and
allows GitHub's deployment approvals to protect access.

## Verify the deployment

1. Open **Actions → Deploy Plugin to EC2**.
2. Select **Run workflow** and run it from `main`.
3. Confirm that SSH host verification succeeds, the checkout fast-forwards, and
   Composer completes.
4. Confirm the deployed commit on EC2 with `git rev-parse HEAD`.

Subsequent pushes to `main` run the same deployment automatically. The workflow
uses `git pull --ff-only` and refuses to deploy over local changes so a divergent
or manually modified production checkout fails safely.
