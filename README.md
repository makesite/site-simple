makesite simple site
====================

NOTE: UNTIL FURTHER NOTICE, THIS REPO IS *NOT* USABLE AS-IS.
YOU WOULD HAVE TO EDIT SOME FILES TO MAKE IT WORK. THIS IS
A WORK IN PROGRESS AND IS A SUBJECT TO CHANGE.

Usage
-----

```
git clone https://github.com/makesite/site-simple.git
cd site-simple
make init
```

If you wish to create your layout using symlinks, use

```
make layout-dev
```

For hard-copying the files instead of using symlinks, use

```
make layout-dist
```

Finally, to prepare a stand-alone .tar.gz suitable for git-less
distribution, run:

```
make dist
```

Layout
------

TODO: Fill it.

Getting out of the webroot
--------------------------

Is definitly possible, but you would have to re-arrange some files
and adjust the APP_DIR and CORE_DIR constants in `config.php`.

Such a layout would be a subject for a different repository in the
future.
