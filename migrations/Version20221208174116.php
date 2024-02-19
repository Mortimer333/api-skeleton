<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221208174116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE `user` (
                id INT AUTO_INCREMENT NOT NULL, 
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL, 
                password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_880E0D76E7927C74 (email), 
                firstname VARCHAR(255) DEFAULT NULL, 
                surname VARCHAR(255) DEFAULT NULL,
                is_super TINYINT(1) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `user`');
    }
}
