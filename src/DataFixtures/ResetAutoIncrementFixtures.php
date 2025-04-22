<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;

class ResetAutoIncrementFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $tables = ['user', 'profile', 'group', 'post', 'tag'];
            foreach ($tables as $table) {
                $sequenceName = $connection->executeQuery("SELECT pg_get_serial_sequence('\"$table\"', 'id')")->fetchOne();
                if ($sequenceName) {
                    $connection->executeStatement("ALTER SEQUENCE $sequenceName RESTART WITH 1");
                }
            }
        }

        if ($platform instanceof MySQLPlatform) {
            $tables = ['user', 'profile', 'group', 'post', 'tag', 'taggable', 'group_profile'];
            foreach ($tables as $table) {
                $connection->executeStatement("ALTER TABLE `$table` AUTO_INCREMENT = 1");
            }
        }
    }
}