dev:
	composer install
	$(MAKE) env

env:
	cp ./root/.env.example ./root/.env
