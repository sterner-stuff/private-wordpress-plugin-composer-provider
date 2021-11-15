# Private WordPress Plugin Composer Provider
This library is a starter template for creating a repository that automatically mirrors a private WordPress plugin and tags releases. That repository can then be used as [a Composer VCS repository](https://getcomposer.org/doc/05-repositories.md#vcs) and the plugin can be required in your projects, just like you may already do with public plugins. See [Roots's Bedrock](https://github.com/roots/bedrock) for more information on Composer-managed WordPress.

## What problem does this solve
Most existing solutions for using private plugins (without vendor Composer support) via Composer include [configuring the package data directly in your WordPress install's `composer.json`](https://getcomposer.org/doc/05-repositories.md#package-2). See:
- https://roots.io/guides/acf-pro-as-a-composer-dependency-with-encrypted-license-key/
- https://github.com/PhilippBaschke/acf-pro-installer

The problem with these solutions is that they require defining the plugin version in your `composer.json`'s `repositories` block. If a new release comes along, you have to bump this number manually. Fine for one site, but a massive pain for many. It also means ensuring correct env vars are set up anywhere you deploy or develop.

A good Composer setup should allow you to define a version contraint for the plugin and simply run `composer update` to get the latest version compatible with your constraints.

This library allows you to do that.

## How does it work
This repository includes a GitHub action that, on a regular schedule, will:
1. Fetch a plugin zip from a remote URL (see the next section for how authentication works)
2. Read the plugin version from the zip
3. Compare that to the latest tag
4. If the version is the same, quits early
5. Overwrites the repository's contents with the zip's contents
6. Commits
7. Tags
8. Pushes

The included `composer.json` then provides other necessary information to turn this repository into [a valid Composer VCS repository](https://getcomposer.org/doc/05-repositories.md#vcs).

## Getting Started
This repository is just a starting point. You'll need to configure a few things to get your plugin working. This makes the repository very flexible.

### Start from this template
Get started with the "Use This Template" button on this repository.

You'll also want to create a first tag so the action has something to compare to:

```
git tag 0.0.0
git push --tags
```

### Determine the download URL for your plugin
You can download the latest version of most private plugins from a fixed URL, so long as you provide authentication of some kind. For example, Advanced Custom Fields Pro can be downloaded from:

```
https://connect.advancedcustomfields.com/index.php?a=download&p=pro&k={{your_acf_license}}
```

### Set up license keys as GitHub secrets
You shouldn't be committing any license keys or API keys to this repository. Instead, save any sensitive information needed to authenticate and download your plugin as [GitHub Secrets](https://docs.github.com/en/actions/reference/encrypted-secrets).

### Update `.github/workflows/update.yml`
Next, you need to update the GitHub action configuration in `.github/workflows/update.yml`.

Find the "Fetch" step and update the URL with your plugin's endpoint, substituting in the secret you configured in the previous step for any sensitive information. For example, ACF Pro might end up looking like this:

```
# Fetch latest version
- name: Fetch
run: wget 'https://connect.advancedcustomfields.com/index.php?a=download&p=pro&k=${{secrets.ACF_PRO_LICENSE}}' -O package.zip
```

Depending on the contents of the zip getting pulled down from the plugin's author, some moving around may be required. For example, if you download ACF Pro, the zip contains a directory that contains the plugin files. This is a common pattern, and the plugin files need to be moved up to the root directory.

That particular pattern is enabled by default - simply configure the plugin slug (which should correspond to the nested directory's name) in the `env` section of the action:

```
env:
  PACKAGE_SLUG: advanced-custom-fields-pro
```

If this behavior _isn't_ desirable, comment out the "Move" step entirely. Tweak according to your own needs.

### Update `composer.json`
Finally, update the included `composer.json` with an appropriate package name and, optionally, author credits. For example:

```
{
    "name": "elliotcondon/advanced-custom-fields-pro",
    "type": "wordpress-plugin",
    "authors": [
        {
            "name": "Elliot Condon",
            "email": "e@elliotcondon.com"
        },
        {
            "name": "Ethan Clevenger",
            "email": "ethan@sternerstuff.dev"
        }
    ],
    "require": {},
    "require-dev": {
        "tutv95/wp-package-parser": "^1.0"
    }
}

```

The only parts you shouldn't change are the package type and the `tutv95/wp-package-parser` dependency.

### Push and test
Commit and push to GitHub. You should now see a new "Actions" tab across the top of your repository. Click into it, find the "Updater" workflow, and run it (main branch, no `yml` required). The action should pull and tag the latest available version of the plugin.

### Schedule
Confident that everything is in working order, you should now uncomment the schedule configuration of the `on` section in `update.yml`. By default, the package will auto-check for updates twice a day. Configure to your liking.

### Require
You can now require this library in your WordPress install. In your WordPress's `composer.json`, add the GitHub repo as [a VCS repository](https://getcomposer.org/doc/05-repositories.md#vcs):

```
{
	"name": "roots/bedrock",
	"type": "project",
	...
	"repositories": [
		{
			"type": "composer",
			"url": "https://wpackagist.org"
		},
		{
			"type": "vcs",
			"url": "git@github.com:sterner-stuff/advanced-custom-fields-pro.git"
		}
	],
	"require": {
		...
	}
}

```
And finally, run `composer require {{ package-name }}` according to what you configured. Given the previous example:

```
composer require elliotcondon/advanced-custom-fields-pro
```

## Etiquette
The WordPress license, and how it impacts paid plugins, will probably always be a point of contention. Ignoring that, we believe that plugin authors that choose to charge for their work should get paid, considering the cost savings gained by using their work. Therefore, we strongly discourage using this workflow to make self-updating public mirrors of private WordPress plugins and themes. Please create a private repository for use in-line with the author's terms of use.

## Problems?
You may need to make tweaks to the updater workflow unique to the plugin you're trying to make work. Feel free to open a PR for more general improvements.

## Known Incompatibilities
- **Easy Digital Downloads**: EDD seem to use some kind of nonce system to create download links, so there is no fixed download link for items sold via EDD to my knowledge. Happy to be proven wrong. Impacts FacetWP, for example.

## FAQ
### [Plugin X] authenticates a different way
See the `wget` documentation for a variety of options that should allow you to authenticate in whatever way required. Tinker with the "Fetch" step in `.github/workflows/update.yml` appropriately.
### Can I use this for a theme?
Totally, but you'll want to update `composer.json` to be use the package type `wordpress-theme`. The existing metadata parser should support WordPress themes.
### The zip contents from my plugin download are unexpected (deeply nested, zip within a zip, etc)
This will require your own tinkering with the GitHub action to move directories and files around appropriately.

## Opportunities for Improvement
This starter template could probably be tweaked to act as a standalone GitHub Action, accepting args for the package name and full download URL. PRs welcome.

## See also
- [Bedrock](https://github.com/roots/bedrock) for an introduction to Composer-managed WordPress
- [SatisPress](https://github.com/cedaro/satispress), for using a WordPress install as a Composer repository and exposing its installed plugins/themes as packages
- [Package Peak](https://packagepeak.app), a SaaS that provides all your Envato purchases as Composer-compatible packages
