# Hostinger deployment setup

The workflow at .github/workflows/deploy-hostinger.yml uploads the contents of dist/ to the main domain using SSH and rsync. It does not connect the ChatGPT Site to GitHub or publish changes to Sites.

## 1. Hostinger SSH
Open the dashboard for theveda.co.in, search SSH Access, and enable it. Note the host/IP, username and port (commonly 65002; use the value shown). Confirm the main domain document root using File Manager or SSH. Expected format:
 /home/u123456789/domains/theveda.co.in/public_html
Do not use the system.theveda.co.in directory. The workflow rejects other destination patterns.

## 2. Create a dedicated key on your own computer
Run:
 ssh-keygen -t ed25519 -C "github-veda-deploy" -f veda_deploy
For unattended deployment, leave this dedicated key's passphrase empty. Upload only veda_deploy.pub (the public key) in Hostinger's SSH Keys section. Keep veda_deploy (the private key) private and add it only to the GitHub secret below. Never commit either credential or paste the private key into chat.

## 3. Verify the server host key
Run, substituting the actual SSH port and host:
 ssh-keyscan -p 65002 YOUR_HOST > hostinger_known_hosts
 ssh-keygen -lf hostinger_known_hosts
Compare fingerprints with Hostinger's SSH Access card through hPanel before trusting the file. Use only the verified matching lines in the secret below.

## 4. GitHub secrets
Repository Settings > Environments > New environment: hostinger-production.
Inside that environment add these secrets:
- HOSTINGER_HOST: SSH IP address or hostname
- HOSTINGER_PORT: SSH port
- HOSTINGER_USER: SSH username
- HOSTINGER_SSH_KEY: complete contents of the private veda_deploy file, including BEGIN/END lines
- HOSTINGER_KNOWN_HOSTS: verified contents of hostinger_known_hosts
- HOSTINGER_PATH: absolute main domain document root, e.g. /home/u123456789/domains/theveda.co.in/public_html

## 5. First run
Back up the current main domain files/database before replacing WordPress. Confirm any subdomain folders and retain them. If main-domain index.php remains, the workflow stops; review it and move the old WordPress entry point outside public_html after backup. Existing .htaccess redirects or WordPress rules may also need review if the HTML page does not display.
Open Actions > Deploy VEDA to Hostinger > Run workflow. Leave dry_run checked for the first run, then inspect logs. Run again with dry_run unchecked to publish.
Future pushes changing dist/ on main deploy automatically.

The workflow never deletes remote files. Replaced files are backed up in a veda-deploy-backups directory next to public_html, with one folder per run/attempt. Removed source files remain on the server and require manual cleanup. Retain or prune backups through Hostinger as needed. This workflow does not remove the WordPress database, configuration or plugin files.
No live deployment has been verified until the required secrets are configured and a deployment run succeeds.
