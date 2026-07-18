#!/usr/bin/env node
/**
 * Builds a WordPress-uploadable plugin zip.
 *
 * `wp-scripts plugin-zip` (bundled with @wordpress/scripts v26) writes a *flat*
 * archive with files at the root. WordPress's "Upload Plugin" expects a single
 * top-level folder, so this script reuses the same file-discovery (npm-packlist,
 * honoring the `files` field in package.json) and archiver (adm-zip) but nests
 * everything under `<plugin-name>/` so the result installs cleanly.
 */
const AdmZip = require( 'adm-zip' );
const { sync: packlist } = require( 'npm-packlist' );
const { dirname } = require( 'path' );

const name = require( '../package.json' ).name;

const zip = new AdmZip();
const files = packlist();

files.forEach( ( file ) => {
	const dir = dirname( file );
	const zipDir = dir !== '.' ? `${ name }/${ dir }` : name;
	zip.addLocalFile( file, zipDir );
} );

zip.writeZip( `./${ name }.zip` );
process.stdout.write(
	`Done. \`${ name }.zip\` (wrapped in \`${ name }/\`) is ready to upload. 🎉\n`
);
