# Deploying WPFAevent To EC2 With GitHub Actions

This repo includes [`.github/workflows/deploy-ec2.yml`](../.github/workflows/deploy-ec2.yml), which deploys the plugin to an EC2 server whenever a commit reaches `main` or when you run the workflow manually.

## 1. Prepare the EC2 server

Install the tools the workflow expects:

```bash
sudo apt update
sudo apt install -y git unzip
php -v
composer --version
```

If Composer is missing, install it:

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
rm composer-setup.php
```

## 2. Put the plugin on the server as a git checkout

Find your live WordPress plugin directory, then clone the repo there. A common path is `/var/www/html/wp-content/plugins/event-plugin`.

```bash
cd /var/www/html/wp-content/plugins
git clone https://github.com/fossasia/WPFAevent.git event-plugin
cd event-plugin
composer install --no-dev --prefer-dist --optimize-autoloader
```

If the plugin directory already exists and was uploaded manually instead of cloned from git, back it up first and replace it with a git clone before using the workflow.

## 3. Configure SSH access

Use a dedicated deployment key rather than a personal EC2 key. Generate it on a
trusted computer:

```bash
ssh-keygen -t ed25519 -C "github-actions-wpfaevent" -f wpfaevent-deploy-key
```

Add the contents of `wpfaevent-deploy-key.pub` as a new line in
`~/.ssh/authorized_keys` for the EC2 deployment user. Test that the private key
can connect before adding it to GitHub.

On the EC2 instance, obtain the SSH host fingerprint:

```bash
ssh-keygen -l -f /etc/ssh/ssh_host_ed25519_key.pub | awk '{print $2}'
```

Do not commit either private key or any real server address to this repository.

## 4. Create the GitHub production environment

You need repository administrator access. In GitHub, open
`Settings -> Environments`, create an environment named `production`, and limit
its deployment branches to `main`. A required reviewer is also recommended for
production.

Add this environment secret:

- `EC2_SSH_KEY`: the complete contents of the private
  `wpfaevent-deploy-key` file, including its BEGIN and END lines

Add these environment variables:

- `EC2_HOST`
- `EC2_USER`
- `EC2_PORT`
- `EC2_PLUGIN_PATH`
- `EC2_DEPLOY_BRANCH`
- `EC2_HOST_FINGERPRINT`

Typical values:

- `EC2_HOST=your-instance.example.com`
- `EC2_USER=ubuntu`
- `EC2_PORT=22`
- `EC2_PLUGIN_PATH=/var/www/html/wp-content/plugins/event-plugin`
- `EC2_DEPLOY_BRANCH=main`
- `EC2_HOST_FINGERPRINT=SHA256:...` (the value printed in step 3)

## 5. Allow the GitHub runner to reach SSH

The EC2 security group must allow the runner to reach the configured SSH port.
GitHub-hosted runner IP ranges are broad and change over time, so do not assume
that allowing only your home IP will work. For production, prefer either AWS
Systems Manager with GitHub OIDC or a private runner/network path instead of
exposing SSH broadly. If SSH is exposed, use key-only authentication, disable
password and root login, and restrict the network source as tightly as your
runner setup permits.

## 6. Make sure the SSH user can update the plugin

The SSH user needs write access to the plugin checkout:

```bash
sudo chown -R ubuntu:www-data /var/www/html/wp-content/plugins/event-plugin
sudo find /var/www/html/wp-content/plugins/event-plugin -type d -exec chmod 775 {} \;
sudo find /var/www/html/wp-content/plugins/event-plugin -type f -exec chmod 664 {} \;
```

Adjust the path and user if your site uses a different setup.

## 7. Commit the workflow through a pull request

Commit `.github/workflows/deploy-ec2.yml` and this guide on a feature branch,
then open a pull request. Do not commit directly to `main`.

## 8. Run the first deployment

After the secret and variables are saved:

1. Open the `Actions` tab in GitHub.
2. Open `Deploy Plugin to EC2`.
3. Select `Run workflow` once manually.
4. Confirm the job can SSH, pull the repo, and run Composer.

After that, every merge to `main` will trigger the same deployment automatically.

## Notes

- The workflow intentionally stops if the EC2 plugin checkout has local uncommitted changes.
- The workflow flushes the WordPress cache with `wp cache flush` when WP-CLI is available.
- If your repo is private, the EC2 server also needs GitHub access for `git pull`, usually via a deploy key or GitHub App token.
