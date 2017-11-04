
deps:
	wget -q https://getcomposer.org/composer.phar -O ./composer.phar
	chmod +x composer.phar
	php composer.phar install

docker-mongo:
	docker run --name mapmon-mongo -d mongo

test-cover:
	bin/phunit --coverage-html=report

test:
	bin/phpunit
