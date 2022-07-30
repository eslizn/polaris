all:
	composer update -v --no-dev --ignore-platform-reqs

dev:
	composer update -vvv --ignore-platform-reqs

serve:
	php -S 127.0.0.1:3000 -t public/

test:
	vendor/bin/phpunit --testdox tests
