#> ========== CONFIG ==========
ENV_FILE=./.env
COMPOSE=docker compose
COMPOSE_DEV_FILES=-f ../infrastructure/compose/dev/docker-compose.yaml -f ../infrastructure/compose/dev/docker-compose.override.yaml

PHP_SERVICE=php
NEXTJS_SERVICE=nextjs
#< ========== CONFIG ==========


#> ========== AIDE ==========
help: ## Affiche la liste des commandes disponibles
	@echo "Commandes disponibles :"
	@grep -E '^[a-zA-Z_-]+:.*?##' Makefile | awk 'BEGIN {FS = ":.*?##"}; {printf "  \033[36m%-25s\033[0m %s\n", $$1, $$2}'
#< ========== AIDE ==========


#> ========== DOCKER COMPOSE ==========
build: ## Build les containers sans cache
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) build --no-cache

start: ## Démarre les services existants
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) start

stop: ## Stoppe les services
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) stop

up: ## Démarre les containers en détache
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) up -d

down: ## Stoppe et supprime les containers
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) down

restart: ## Redémarre tous les services
	$(MAKE) down
	$(MAKE) up

docker-ps: ## Liste les containers actifs
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) ps

images: ## Liste les images Docker
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) images

logs: ## Affiche les logs
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) logs -f
#< ========== DOCKER COMPOSE ==========


#> ========== ACCESS CONTAINERS ==========
php-bash: ## Accède au shell du container PHP
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) bash

nextjs-bash: ## Accède au shell du container Next.js
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) bash
#< ========== ACCESS CONTAINERS ==========


#> ========== COMPOSER ==========
composer-install: ## Lance composer install dans PHP
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) composer install

composer-require: ## Installe un package via composer require
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) composer require $(package)
    ## make composer-require package="**nom du package**"

composer-update: ## Mettre à jour les dépendances Composer
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) composer update

composer-dumpautoload: ## Régénérer le fichier autoload
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) composer dump-autoload
#< ========== COMPOSER ==========


#> ========== SYMFONY ==========
cache-clear: ## Vide le cache Symfony
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console cache:clear

debug-router: ## Affiche les routes Symfony
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console debug:router

debug-env: ## Afficher les variables d'environnement Symfony
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console debug:dotenv

debug-autowiring: ## Lister les services disponibles pour l'autowiring
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console debug:autowiring
#< ========== SYMFONY ==========


#> ========== DOCTRINE ==========
create-database: ## Créer la base de données si elle n'existe pas
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:database:create --if-not-exists

drop-database: ## Supprimer la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:database:drop --force

create-schema: ## Générer le schéma de la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:schema:create

update-schema: ## Mettre à jour le schéma de la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:schema:update --force

validate-schema: ## Valider le schéma de la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:schema:validate

fixtures: ## Charger les fixtures dans la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:fixtures:load --no-interaction

make-migration: ## Générer un fichier de migration
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console make:migration

migrate: ## Appliquer les migrations dans la base de données
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction

migrations-list: ## affiche la list des migrations
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:list

migrations-status: ## Vérifier le statut des migrations
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:status

migrations-diff: ## Générer une migration basée sur les changements d'entités
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:diff

migrations-rollback: ## Annuler la dernière migration exécutée
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:execute --down $(MIGRATION_ID)

migrations-execute: ## Exécuter une migration spécifique
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) php bin/console doctrine:migrations:execute $(MIGRATION_ID) --up
#< ========== DOCTRINE ==========


#> ========== PHPSTAN ==========
phpstan: ## Lancer PHPStan pour analyser le code source
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpstan analyse --memory-limit=512M
#< ========== PHPSTAN ==========


#> ========== PHP-ECS ==========
ecs-check: ## Vérifier le respect des standards de code avec ECS
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/ecs check src

ecs-fix: ## Corriger automatiquement les erreurs de formatage avec ECS
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/ecs check src --fix
#< ========== PHP-ECS ==========


#> ========== PHPUNIT TESTS ==========
phpunit: ## Exécuter tous les tests
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit --testdox

phpunit-file: ## Exécuter les tests sur un fichier spécifique
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit $(file)

phpunit-filter: ## Exécuter un test précis via un filtre
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit --filter $(filter)

phpunit-coverage: ## Générer un rapport de couverture
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit --coverage-html tests/coverage

phpunit-debug: ## Lancer les tests en mode verbose pour débug
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit --debug

phpunit-group: ## Exécuter des tests basés sur un groupe spécifique
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(PHP_SERVICE) vendor/bin/phpunit --group $(group)
#< ========== PHPUNIT TESTS ==========


#> ========== NEXTJS ==========
npm-install: ## Installe les dépendances npm
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm install

npm-update: ## Mettre à jour les dépendances npm
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm update

run-dev: ## Démarrer le serveur de développement
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run dev

build-next: ## Générer une version de production du projet
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run build

start-next: ## Démarrer le serveur en mode production
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm start

lint: ## Vérifier le code avec ESLint
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run lint

test: ## Lancer les tests unitaires
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run test

test-watch: ## Lancer les tests en mode "watch"
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run test:watch

format: ## Formater le code avec Prettier
	$(COMPOSE) --env-file $(ENV_FILE) $(COMPOSE_DEV_FILES) exec $(NEXTJS_SERVICE) npm run format
#< ========== NEXTJS ==========
