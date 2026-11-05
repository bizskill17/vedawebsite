# VEDA website editing panel

After the GitHub deployment succeeds, open https://theveda.co.in/admin/.

## First administrator
1. Open /admin/ once. This creates private storage.
2. In Hostinger File Manager go one level above public_html and open veda-cms-data/setup-token.txt.
3. Copy the token into the setup screen, choose your own username and a password of at least 12 characters.
4. Sign in. There are no default credentials. The token is deleted after successful setup.

Only someone with access to that private hosting file can create the first administrator. If storage cannot be created, give the website's PHP process permission to create veda-cms-data beside public_html. PHP 8.1 or newer is required; PHP 8.3 is tested.

## Editing
Website content lists 26 groups, covering 222 fields: navigation, hero, introduction, services and categories, approach, FAQs, contact details, social links, hours, map, footer, page title and description. Use plain text; HTML is escaped. Link fields support HTTPS/HTTP, telephone links, email links and internal anchors. The map field takes a Google Maps embed URL.
Each existing service has its own group. Text segments separated by emphasis or line breaks appear as separate fields, preserving the existing design. Image URL fields accept HTTPS or local paths; upload JPG/PNG/WebP (5 MB maximum, 8000 pixels maximum). A new image replaces only its selected field, e.g. header and footer logos can be managed separately.
Save draft keeps the public website unchanged. Preview draft shows all saved drafts in a new tab. Publish section publishes only the current group. Hide supported sections or individual services using the checkbox and Publish section.
This version edits all existing content and hides existing sections/services; it does not create new layout sections or add new service/FAQ cards.

## Users
Administrators can manage all content and users. Add Editors and select exactly which groups each can edit and publish. Disable an account to revoke its sessions; password/role/permission updates also invalidate existing sessions. Each user can change their own password. Keep at least one active administrator.
For an administrator who loses access, hosting-level recovery is required; there is no email password-reset service.

## History and storage
Administrators can restore the last ten published content snapshots and inspect the latest 200 activity entries. Restoring rolls back the whole website and its drafts. Images are retained rather than deleted.
Private users/password hashes, content, drafts, history and activity are stored in veda-cms-data/state.json outside public_html. Uploaded images are in public_html/uploads. Back up BOTH paths. No database is required.
GitHub deployments upload application code only, never the runtime state or uploaded images. Existing FTP files are not deleted. Use Hostinger backups before deploying. The public site is rendered by index.php; .htaccess gives it priority over the retained static index.html fallback.
This PHP backend runs on Hostinger. The separately hosted ChatGPT Site remains the earlier static version; it does not share these runtime edits or accounts.
