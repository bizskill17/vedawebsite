# Hostinger FTPS deployment

Open repository Settings > Secrets and variables > Actions > New repository secret. Add:

| Secret | Value |
|---|---|
| FTP_SERVER | Hostinger FTP hostname or IP, without ftp:// |
| FTP_USERNAME | Main-domain FTP account username |
| FTP_PASSWORD | That account's FTP password |

Use the account shown for theveda.co.in, whose upload folder is public_html. Port 21 and public_html are configured in the YAML. SSH keys are unnecessary. The workflow uses explicit FTPS, connects to the VEDA FTP endpoint 82.180.165.180 and validates the server certificate against hstgr.io, a SAN read from that endpoint's certificate on 05/11/2026. FTP_SERVER is retained for compatibility with the existing three secrets; connection address and TLS identity are currently pinned in YAML. If Hostinger migrates the hosting server, update both after checking the new endpoint and certificate. It will not fall back to unencrypted FTP.

Back up the main website before the first live upload. In Actions > Deploy VEDA to Hostinger > Run workflow, leave dry_run checked first. It connects and lists planned uploads without writing files. Then run again with dry_run unchecked. Future pushes changing dist/ on main deploy automatically.

Only dist/ contents are uploaded. Remote files are never deleted, and subdomain directories are not traversed. Existing files with matching names are overwritten; this FTP version does not make automatic backups. If index.php remains in public_html, the workflow stops for review of the old WordPress entry point. Existing redirects/.htaccess can also affect the displayed website. Removed source assets remain on the server until manually cleaned up.

Do not share the FTP password in chat or commit it. No successful live deployment is claimed until a run completes with the secrets configured. The ChatGPT Site still uses its own source repository.
