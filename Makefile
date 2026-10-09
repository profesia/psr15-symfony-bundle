PHP_VERSION ?= 8.0
export PHP_VERSION

DOCKER_COMPOSE = cd docker && docker-compose

.PHONY: up down php bash container

up:
	$(if $(filter container,$(MAKECMDGOALS)),true,cd docker && docker-compose down --remove-orphans && \
	docker-compose pull && \
	docker-compose up -d)

down:
	$(if $(filter container,$(MAKECMDGOALS)),true,$(DOCKER_COMPOSE) down --remove-orphans)

php:
	$(if $(filter container,$(MAKECMDGOALS)),true,$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "php $(ARGS)")

bash:
	$(if $(filter container,$(MAKECMDGOALS)),true,$(if $(strip $(ARGS)),$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash -c "$(ARGS)",$(DOCKER_COMPOSE) exec psr15_symfony_bundle bash))

container:
	$(DOCKER_COMPOSE) exec psr15_symfony_bundle $(if $(strip $(ARGS)),$(ARGS),$(if $(filter-out container,$(MAKECMDGOALS)),$(filter-out container,$(MAKECMDGOALS)),bash))

ifneq ($(filter container,$(MAKECMDGOALS)),)
%:
	@:
endif
