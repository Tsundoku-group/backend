# Nom des services Docker Compose
COMPOSE=docker compose                   # Commande pour exécuter Docker Compose
PHP_SERVICE=php                          # Service Docker pour PHP
COMPOSER_SERVICE=composer_sf             # Service Docker pour Composer

# Fichier d'environnement
ENV_FILE=.env                            # Fichier contenant les variables d'environnement

# Commandes Docker Compose
build:                                   # Construire les images Docker des services
	$(COMPOSE) build

start:                                   # Démarrer les conteneurs sans détachement
	$(COMPOSE) start

stop:                                    # Arrêter les conteneurs en cours d'exécution
	$(COMPOSE) stop

up:                                      # Démarrer les conteneurs en arrière-plan (mode détaché)
	$(COMPOSE) up -d

down:                                    # Arrêter et supprimer les conteneurs, réseaux et volumes associés
	$(COMPOSE) down

restart:                                 # Redémarrer les conteneurs
	$(COMPOSE) restart

docker-ps:                               # Afficher l'état des conteneurs Docker
	$(COMPOSE) ps

images:                                  # Afficher les images Docker utilisées
	$(COMPOSE) images

logs:                                    # Afficher les logs des conteneurs en temps réel
	$(COMPOSE) logs -f

# Accès aux conteneurs
php-bash:                                # Ouvrir un terminal dans le conteneur PHP
	$(COMPOSE) exec $(PHP_SERVICE) bash

composer-bash:                           # Ouvrir un terminal dans le conteneur Composer
	$(COMPOSE) exec $(COMPOSER_SERVICE) bash

# Commandes Composer
composer-install:                        # Installer les dépendances avec Composer
	$(COMPOSE) exec $(COMPOSER_SERVICE) composer install

composer-require:                        # Ajouter une nouvelle dépendance Composer
	$(COMPOSE) exec $(COMPOSER_SERVICE) composer require $(package)
# make composer-require package="**nom du package**"

composer-update:                         # Mettre à jour les dépendances Composer
	$(COMPOSE) exec $(COMPOSER_SERVICE) composer update

composer-dumpautoload:                   # Régénérer le fichier autoload
	$(COMPOSE) exec $(COMPOSER_SERVICE) composer dump-autoload

# Commandes Symfony
make-entity:                             # Créer une nouvelle entité Symfony
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console make:entity $(entity)

cache-clear:                             # Vider le cache Symfony
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console cache:clear

debug-router:                            # Lister toutes les routes définies dans Symfony
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console debug:router

debug-env:                               # Afficher les variables d'environnement Symfony
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console debug:dotenv

debug-autowiring:                        # Lister les services disponibles pour l'autowiring
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console debug:autowiring

# Commandes Doctrine (base de données et migrations)
create-database:                         # Créer la base de données si elle n'existe pas
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:database:create --if-not-exists

drop-database:                           # Supprimer la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:database:drop --force

create-schema:                           # Générer le schéma de la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:schema:create

update-schema:                           # Mettre à jour le schéma de la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:schema:update --force

validate-schema:                         # Valider le schéma de la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:schema:validate

fixtures:                                # Charger les fixtures dans la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:fixtures:load --no-interaction

make-migration:                          # Générer un fichier de migration
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console make:migration

migrate:                                 # Appliquer les migrations dans la base de données
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:migrate --no-interaction

migrations-list:						 # affiche la list des migrations
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:list

migrations-status:                       # Vérifier le statut des migrations
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:status

migrations-diff:                         # Générer une migration basée sur les changements d'entités
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:diff

migrations-rollback:                     # Annuler la dernière migration exécutée
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:execute --down $(MIGRATION_ID)

migrations-execute:                      # Exécuter une migration spécifique
	$(COMPOSE) exec $(COMPOSER_SERVICE) php bin/console doctrine:migrations:execute $(MIGRATION_ID) --up

# PHPStan
phpstan:								 # Lancer PHPStan pour analyser le code source
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpstan analyse --memory-limit=512M

# PHP-ECS
ecs-check:								 # Vérifier le respect des standards de code avec ECS
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/ecs check src

ecs-fix:								 # Corriger automatiquement les erreurs de formatage avec ECS
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/ecs check src --fix

# PHPUnit Tests
phpunit:                                 # Exécuter tous les tests
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit --testdox

phpunit-file:                            # Exécuter les tests sur un fichier spécifique
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit $(file)

phpunit-filter:                          # Exécuter un test précis via un filtre
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit --filter $(filter)

phpunit-coverage:                        # Générer un rapport de couverture
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit --coverage-html tests/coverage

phpunit-debug:                           # Lancer les tests en mode verbose pour débug
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit --debug

phpunit-group:                           # Exécuter des tests basés sur un groupe spécifique
	$(COMPOSE) exec $(COMPOSER_SERVICE) vendor/bin/phpunit --group $(group)