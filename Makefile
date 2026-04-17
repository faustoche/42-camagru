DOCKER ?= docker
COMPOSE = $(DOCKER) compose -f ./docker-compose.yml

all: up

up: build
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

stop:
	$(COMPOSE) stop

start:
	$(COMPOSE) start

build:
	$(COMPOSE) build

clean:
	@$(DOCKER) stop $$( $(DOCKER) ps -aq ) || true
	@$(DOCKER) rm $$( $(DOCKER) ps -aq ) || true
	@$(DOCKER) rmi -f $$( $(DOCKER) images -aq ) || true
	@$(DOCKER) volume rm $$( $(DOCKER) volume ls -q ) || true
	@$(DOCKER) network rm $$( $(DOCKER) network ls -q ) || true

re: clean up

fclean: clean
	@$(DOCKER) system prune -a --volumes -f