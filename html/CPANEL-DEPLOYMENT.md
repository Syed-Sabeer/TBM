# Deploying the download deterrent

1. In cPanel > Domains, locate the document root for `tbm.deveoninc.com`.
2. In File Manager, enable Settings > Show Hidden Files (dotfiles).
3. Upload `.htaccess` into that document root, beside `index.html`. If the server
   already has an `.htaccess`, back it up and merge these rules, retaining any
   cPanel-managed PHP, HTTPS and password-protection settings.
4. Open the homepage, shop and product pages and check that styles and images load.
5. From a terminal, verify the responses:

   ```sh
   curl -I -A "Mozilla/5.0" https://tbm.deveoninc.com/
   curl -I -A "Wget/1.21.4" https://tbm.deveoninc.com/
   curl -I -A "Mozilla/4.5 (compatible; HTTrack 3.0x)" https://tbm.deveoninc.com/assets/js/app.js
   ```

   The browser request should succeed; the two downloader requests should return
   HTTP 403. If a CDN caches the site, purge its cache and verify there as well.
   If the host returns HTTP 500, restore the backup and ask hosting support to
   enable `mod_rewrite` and permit `Options` and `FileInfo` overrides for this folder.

## Limits and stronger protection

These rules reject identified mirroring tools and disable directory listings.
They cannot stop a downloader pretending to be a browser, browser Save Page,
developer tools, or screenshots. Changing HTML to PHP would still send copyable
rendered HTML to the browser.

For a private preview, use cPanel > Directory Privacy on the domain's document
root and create authorized users. This blocks unauthenticated access to pages
and assets; authorized visitors can still copy what they receive.
See https://docs.cpanel.net/cpanel/files/directory-privacy/.

The current login is a JavaScript/localStorage demo. It does not protect the
catalog prices or account data shipped in JavaScript. Real private data requires
server-side authentication and authorization before returning that data.

Keep repository files, local screenshots, backups and deployment documentation
outside the public document root when uploading the site.
