APPROOT_DESTDIR="simplesite"
ROOT_DESTDIR="simplesite/admin"
CORE_DESTDIR="simplesite/admin"
MODELS_DESTDIR="simplesite/admin/models"

init:
	git submodule init
	git submodule update
	cd submodules/jtype
	git submodule init
	git submodule update
	make sizzle

clean:
	-@rm $(CORE_DESTDIR)/domtempl.php
	-@rm $(CORE_DESTDIR)/qry5.php
	-@rm $(CORE_DESTDIR)/db.php
	-@rm $(CORE_DESTDIR)/db.orm.php
	-@rm $(CORE_DESTDIR)/common.php
	-@rm $(CORE_DESTDIR)/form.php
	-@rm $(CORE_DESTDIR)/dispatch.php
	-@rm $(ROOT_DESTDIR)/install.php
	-@rm $(MODELS_DESTDIR)/settings.php 
	-@rm $(MODELS_DESTDIR)/files.php
	-@rm $(MODELS_DESTDIR)/deeppage.php
	-@rm $(MODELS_DESTDIR)/pagepicture.php
	-@rm $(ROOT_DESTDIR)/admin/design/jtype-sizzle-0.0.3.js
	-@rm $(ROOT_DESTDIR)/admin/design/domtempl.js

layout-generic:
	-@mkdir $(APPROOT_DESTDIR)/files
	-@chmod 0777 $(APPROOT_DESTDIR)/files
	-@mkdir $(ROOT_DESTDIR)/backups
	-@chmod 0777 $(ROOT_DESTDIR)/backups

layout-dev: init layout-generic
	ln -s ../../submodules/domtempl/domtempl.php $(CORE_DESTDIR)
	ln -s ../../submodules/qry/qry5.php $(CORE_DESTDIR)
	ln -s ../../submodules/pdb/db.php $(CORE_DESTDIR)
	ln -s ../../submodules/pdb/db.orm.php $(CORE_DESTDIR)
	ln -s ../../submodules/varcore/common.php $(CORE_DESTDIR)
	ln -s ../../submodules/varcore/form.php $(CORE_DESTDIR)
	ln -s ../../submodules/varcore/dispatch.php $(CORE_DESTDIR)
	ln -s ../../submodules/varcore/install.php $(ROOT_DESTDIR)
	ln -s ../../../submodules/pdb/models/settings.php $(MODELS_DESTDIR)
	ln -s ../../../submodules/pdb/models/files.php $(MODELS_DESTDIR)
	ln -s ../../../submodules/pdb/models/deeppage.php $(MODELS_DESTDIR)
	ln -s ../../../submodules/pdb/models/pagepicture.php $(MODELS_DESTDIR)
	ln -s ../../../submodules/pdb/models/pagepicture.php $(ROOT_DESTDIR)/admin/design/.
	cp submodules/jtype/jtype-sizzle-0.0.3.js $(ROOT_DESTDIR)/admin/design/.
	ln -s ../../../submodules/domtempl/domtempl.js $(ROOT_DESTDIR)/admin/design/.

layout-dist: init layout-generic
	cp submodules/domtempl/domtempl.php $(CORE_DESTDIR)
	cp submodules/qry/qry5.php $(CORE_DESTDIR)
	cp submodules/pdb/db.php $(CORE_DESTDIR)
	cp submodules/pdb/db.orm.php $(CORE_DESTDIR)
	cp submodules/varcore/common.php $(CORE_DESTDIR)
	cp submodules/varcore/form.php $(CORE_DESTDIR)
	cp submodules/varcore/dispatch.php $(CORE_DESTDIR)
	cp submodules/varcore/install.php $(ROOT_DESTDIR)
	cp submodules/pdb/models/settings.php $(MODELS_DESTDIR)
	cp submodules/pdb/models/files.php $(MODELS_DESTDIR)
	cp submodules/pdb/models/deeppage.php $(MODELS_DESTDIR)
	cp submodules/pdb/models/pagepicture.php $(MODELS_DESTDIR)
	cp submodules/jtype/jtype-sizzle-0.0.3.js $(ROOT_DESTDIR)/admin/design/.
	cp submodules/domtempl/domtempl.js $(ROOT_DESTDIR)/admin/design/.

dist:
	tar -chf simplesite.tar simplesite/.htaccess simplesite/design/* simplesite/admin/.htaccess simplesite/**/*.php simplesite/*.php