PHP_VERSION ?= 8.0
export PHP_VERSION

DOCKER_COMPOSE = cd docker && docker-compose

.PHONY: up down php bash container

ifneq ($(filter container,$(MAKECMDGOALS)),)
up down php bash:
	@:

%:
	@:
else
up:
	cd docker && docker-compose down --remove-orphans && \
	docker-compose pull && \
	docker-compose up -d

down:
	$(DOCKER_COMPOSE) down --remove-orphans

php:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "php $(ARGS)"

bash:
	@$(if $(strip $(ARGS)),$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "$(ARGS)",$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash)
endif

container:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle $(if $(strip $(ARGS)),$(ARGS),$(if $(filter-out container,$(MAKECMDGOALS)),$(filter-out container,$(MAKECMDGOALS)),bash))
