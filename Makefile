.PHONY: help install up down restart build test lint format clear-cache clear-logs

help: ## Mostra esta ajuda
	@awk 'BEGIN {FS = ":.*##"; printf "\nUso:\n  make \033[36m<comando>\033[0m\n\nComandos:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

install: ## Instala as dependências do projeto
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan storage:link

up: ## Inicia os containers
	docker-compose up -d

dev: ## Inicia os containers em modo desenvolvimento
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

down: ## Para os containers
	docker-compose down

restart: down up ## Reinicia os containers

build: ## Reconstrói os containers
	docker-compose build --no-cache

test: ## Executa os testes
	docker-compose exec app php artisan test

test-coverage: ## Executa os testes com cobertura
	docker-compose exec app php artisan test --coverage-html coverage

lint: ## Executa o linter
	docker-compose exec app ./vendor/bin/pint --test

format: ## Formata o código
	docker-compose exec app ./vendor/bin/pint

clear-cache: ## Limpa os caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

clear-logs: ## Limpa os logs
	docker-compose exec app rm -f storage/logs/*.log

logs: ## Mostra os logs dos containers
	docker-compose logs -f

shell: ## Acessa o shell do container da aplicação
	docker-compose exec app bash

queue: ## Inicia o worker das filas
	docker-compose exec app php artisan queue:work

migrate: ## Executa as migrações
	docker-compose exec app php artisan migrate

rollback: ## Reverte a última migração
	docker-compose exec app php artisan migrate:rollback

seed: ## Executa os seeders
	docker-compose exec app php artisan db:seed

fresh: ## Recria o banco de dados
	docker-compose exec app php artisan migrate:fresh --seed 