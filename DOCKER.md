# Docker Deployment & Local Development

This directory contains a complete Docker-based development environment to run WordPress and test/develop the `event-plugin` plugin without needing to install PHP, MySQL, or web servers locally.

## Prerequisite
- [Docker](https://www.docker.com/products/docker-desktop/) installed and running.
- [Docker Compose](https://docs.docker.com/compose/install/) (included in Docker Desktop).

---

## Quick Start

### 1. Copy Environment Settings
Create your local environment file by copying the template:
```bash
cp .env.example .env
```
*(Optional)* You can open `.env` and change `WP_PORT` if `8080` is already in use on your system.

### 2. Start the Containers
Run Docker Compose in detached mode to start the services in the background:
```bash
docker compose up -d
```
This command starts three containers:
- **`wpfaevent-db`**: MySQL database container.
- **`wpfaevent-wordpress`**: The main WordPress container running Apache and PHP.
- **`wpfaevent-wp-cli`**: A CLI container for running WordPress commands.

### 3. Complete WordPress Installation
1. Open your browser and navigate to: [http://localhost:8080](http://localhost:8080) (or the custom port you set in `.env`).
2. Complete the standard WordPress installation wizard (Site Title, Username, Password, Email).
3. Log in to the Admin Dashboard.

---

## Managing the Plugin with WP-CLI

You can use the WP-CLI container to manage your WordPress installation and run the plugin CLI commands without needing local installations.

### Activate the Plugin
Run the following command to activate `event-plugin`:
```bash
docker compose run --rm wp-cli wp plugin activate event-plugin
```

### Run the Eventyay Importer
To run the importer command provided by this plugin to sync events:
```bash
docker compose run --rm wp-cli wp wpfa import
```

### Run Other WP-CLI Commands
Any standard WP-CLI commands can be run using the container. For example:
- **List installed plugins**:
  ```bash
  docker compose run --rm wp-cli wp plugin list
  ```
- **Check active theme**:
  ```bash
  docker compose run --rm wp-cli wp theme list
  ```

---

## Stopping and Resetting the Environment

### Stop the Environment
To stop the running containers without losing database records or configurations:
```bash
docker compose down
```

### Reset the Environment
To completely wipe the database, uploads, and start fresh:
```bash
docker compose down -v
```
*(This destroys the `db_data` and `wordpress_data` named volumes, letting you start from a clean WordPress setup).*

---

## How it Works
The `docker-compose.yml` mounts the plugin root folder into the WordPress container at `/var/www/html/wp-content/plugins/event-plugin` using a host bind-mount volume:
```yaml
volumes:
  - .:/var/www/html/wp-content/plugins/event-plugin
```
- Any changes you make to the source code on your local system will immediately take effect inside the Docker environment.
- Any media uploads or core WordPress core changes are persisted in the named volume `wordpress_data`, so they won't be lost when restarting the containers.
