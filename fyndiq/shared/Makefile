SRC_DIR = ./src
TRANSLATIONS_DIR = ./translations
TESTS_DIR = ./tests
COVERAGE_DIR = ./coverage
DOCS_DIR = ./docs
BIN_DIR = ./vendor/bin

lite: test scss-lint php-lint

all: phpmd jshint

test:
	$(BIN_DIR)/phpunit

scss-lint:
	scss-lint ./frontend/css/*.scss

jshint:
	jshint ./frontend/js/*.js

php-lint:
	find $(SRC_DIR) -name "*.php" -print0 | xargs -0 -n1 -P8 php -l
	find $(TRANSLATIONS_DIR) -name "*.php" -print0 | xargs -0 -n1 -P8 php -l

phpmd:
	./vendor/bin/phpmd $(SRC_DIR) text cleancode,codesize,controversial,design,naming,unusedcode

coverage: clear_coverage
	$(BIN_DIR)/phpunit --coverage-html $(COVERAGE_DIR)

clear_coverage:
	rm -rf $(COVERAGE_DIR)

docs: clear_docs
	$(BIN_DIR)/phpdoc -d $(SRC_DIR) -t $(DOCS_DIR)

clear_docs:
	rm -rf $(DOCS_DIR)

compatinfo:
	$(BIN_DIR)/phpcompatinfo analyser:run $(SRC_DIR)

translations_push:
	tx push -s

translations_pull:
	tx pull -a

sniff:
	$(BIN_DIR)/phpcs --standard=PSR2 --extensions=php --colors $(SRC_DIR)

sniff-fix:
	$(BIN_DIR)/phpcbf --standard=PSR2 --extensions=php $(SRC_DIR)
	$(BIN_DIR)/phpcbf --standard=PSR2 --extensions=php $(TESTS_DIR)
	$(BIN_DIR)/phpcbf --standard=PSR2 --extensions=php $(TRANSLATIONS_DIR)

