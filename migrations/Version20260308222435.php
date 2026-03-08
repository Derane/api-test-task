<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308222435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(8) NOT NULL, phone VARCHAR(8) NOT NULL, password VARCHAR(255) NOT NULL, api_token VARCHAR(64) NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_1483A5E9AA08CB10 (login), UNIQUE INDEX UNIQ_1483A5E97BA2F5EB (api_token), UNIQUE INDEX uq_login_password (login, password), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE users');
    }
}
