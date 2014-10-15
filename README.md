#fido [![Build Status](https://travis-ci.org/watoki/fido.png?branch=master)](https://travis-ci.org/watoki/fido)

A simple [composer] plugin fetches assets from URLs or git repositories.

[composer]: https://getcomposer.org

## Installation ##

Just add it to you composer requirements

```js
"require": {
    "watoki/fido":"*"
},
"minimum-stability": "dev"
```

## Usage ##

The asset requirements go into the `extra` part of the `composer.json`

```js
"extra": {
    "require-assets": {
        "base-dir": "web/assets/vendor", // defaults to "assets/vendor"

        // Short definitions
        "https://code.jquery.com/jquery-2.1.1.js":"jquery.js", // Short form for files <source>:<target>
        "https://github.com/jonkemp/qunit-phantomjs-runner.git":"v1.1.0" // Short form for git <source>:<tag>,

        // Full definitions
        "jquery": {
            "source": "https://code.jquery.com/jquery-2.1.1.js",
            "type": "file", // optional, inferred from source
            "target": "jquery.js" // Optional
        },
        "phantom.js runner": {
            "source": "https://github.com/jonkemp/qunit-phantomjs-runner.git",
            "type": "git", // optional, inferred from source
            "target": "phantomjs-runner", // Optional
            "tag": "v1.2.0" // optional, defaults to "master"
        }
    }
}
```

## Documentation ##

For further documentation, check out *fido*'s [executable documentation][doc]

[dox]: http://dox.rtens.org/projects/watoki-fido