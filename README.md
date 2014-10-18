
#fido [![Build Status](https://travis-ci.org/watoki/fido.png?branch=master)](https://travis-ci.org/watoki/fido)

A simple [composer] plugin that simplifies installing packages without dependency management support (e.g. assets
from URLs or git repositories).

[composer]: https://getcomposer.org

## Installation ##

You'll need [composer] and [git]. Then just add *fido* to you composer requirements.

```js
"require": {
    "watoki/fido":"*"
},
"minimum-stability": "dev"
```

[git]: http://git-scm.com/

## Usage ##

The requirement definitions go into the `extra` object using the ke `fido-fetch`. Here is an example with two packages.

```js
"extra": {
    "fido-fetch": {
        "base-dir": "web/assets/vendor", // defaults to "assets/vendor"
        "jquery": {
            "source": "https://code.jquery.com/jquery-2.1.1.js",
            "type": "file", // optional, inferred from source
            "target": "jquery.js" // Optional, defaults to file name
        },
        "phantom.js runner": {
            "source": "https://github.com/jonkemp/qunit-phantomjs-runner.git",
            "type": "git", // optional, inferred from source
            "target": "phantomjs-runner", // Optional, defaults to repository name
            "reference": "v1.2.0" // optional, defaults to "*"
        }
    }
}
```

This can be shortened in several steps. First, by leaving out the optional definitions.

```js
"extra": {
    "fido-fetch": {
        "jquery": {
            "source": "https://code.jquery.com/jquery-2.1.1.js",
        },
        "phantom.js runner": {
            "source": "https://github.com/jonkemp/qunit-phantomjs-runner.git",
        }
    }
}
```

To shorten it even more, the value of `source` can be used as package names.

```js
"extra": {
    "fido-fetch": {
        "https://code.jquery.com/jquery-2.1.1.js": {},
        "https://github.com/jonkemp/qunit-phantomjs-runner.git": {}
    }
}
```

Finally, these definitions can also be put into the `require` object (including versions for git repositories)
by prefixing them with `fido-fetch:`.

```js
"require": {
    "fido-fetch:https://code.jquery.com/jquery-2.1.1.js": "*",
    "fido-fetch:https://github.com/jonkemp/qunit-phantomjs-runner.git": "v1.2.0"
}
```

**Notice:** I you want use the last option, you need to first install *fido* globally

    composer global require watoki/fido

## Dev Mode ##

You can also specify packages for dev-mode only using the `fetch-fido-dev` and `require-dev` objects.

## Documentation ##

For a complete documentation, check out *fido*'s [executable documentation][dox]

[dox]: http://dox.rtens.org/projects/watoki-fido

## But.. why? ##

Convenience, mostly.

There are of course several ways to get [bootstrap], [jquery] and their likes into your project without bundling them with it.
You could use [bower] or [npm] for example or, if you don't like javascript, you can get the the excellent
[bower/npm composer plugin][asset-plugin].

But some packages, like the [qunit-phantomjs-runner], don't support *any* dependency management system. For these cases exists
composer's [`package` type repository][package-type] (thanks Igor for [pointing that out][tweet]).

Now if you would like to have these packages in another directory than `vendor`, you can either symlink to them or
use the [composer-custom-directory-installer] plugin which lets you decide where certain packages should be installed.

This is how the `composer.json` looks like with if you would require [bootstrap] and the [qunit-phantomjs-runner] with this
approach:

```js
{
    "require": {
        "mnsami/composer-custom-directory-installer": "1.0.*",
        "jquery":"2.1.1",
        "jonkemp/qunit-phantomjs-runner":"1.1.0"
    },
    "extra": {
        "installer-paths":{
            "web/assets/jquery/jquery.js": ["jquery"],
            "web/assets/phantomjs-runner": ["jonkemp/qunit-phantomjs-runner"]
        }
    },
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "jquery",
                "version": "2.1.1",
                "dist": {
                    "url": "https://code.jquery.com/jquery-2.1.1.js",
                    "type": "file"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "jonkemp/qunit-phantomjs-runner",
                "version": "1.1.0",
                "source": {
                    "url": "https://github.com/jonkemp/qunit-phantomjs-runner.git",
                    "type": "git",
                    "reference": "tags/v1.1.0"
                }
            }
        }
    ],
    "minimum-stability": "dev"
}
```

And this is actually (more or less) what *fido* compiles to, simply with a shorter, simpler syntax.

[bootstrap]: http://getbootstrap.com/
[jquery]: http://jquery.com/
[bower]: http://bower.io/
[npm]: http://nodejs.org/
[asset-plugin]: https://github.com/francoispluchino/composer-asset-plugin
[tweet]: https://twitter.com/igorwhiletrue/status/522406046930071552
[package-type]: https://getcomposer.org/doc/05-repositories.md#package-2
[qunit-phantomjs-runner]: https://github.com/jonkemp/qunit-phantomjs-runner
[composer-custom-directory-installer]: https://github.com/mnsami/composer-custom-directory-installer