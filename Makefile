PHP_VERSION ?= 8.0
export PHP_VERSION

DOCKER_COMPOSE = cd docker && docker-compose

.PHONY: up down php bash container

up:
	cd docker && docker-compose down --remove-orphans && \
	docker-compose pull && \
	docker-compose up -d

down:
	$(DOCKER_COMPOSE) down --remove-orphans

php:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "php $(ARGS)"

bash:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "$(ARGS)"

container:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle $(ARGS)
