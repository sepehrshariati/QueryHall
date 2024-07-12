.PHONY: test

test:
	vendor/bin/phpunit --verbose --testdox
format:
	 tools/php-cs-fixer/vendor/bin/php-cs-fixer fix .
analyze:
	vendor/bin/phpstan analyse -l 8 src


