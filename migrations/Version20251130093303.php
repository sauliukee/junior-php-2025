<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130093303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blacklisted_ip ADD ip_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blacklisted_ip ADD CONSTRAINT FK_250B62485F23F921 FOREIGN KEY (ip_address_id) REFERENCES ip_address (id)');
        $this->addSql('CREATE INDEX IDX_250B62485F23F921 ON blacklisted_ip (ip_address_id)');
        $this->addSql('ALTER TABLE ip_address ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blacklisted_ip DROP FOREIGN KEY FK_250B62485F23F921');
        $this->addSql('DROP INDEX IDX_250B62485F23F921 ON blacklisted_ip');
        $this->addSql('ALTER TABLE blacklisted_ip DROP ip_address_id');
        $this->addSql('ALTER TABLE ip_address DROP longitude');
    }
}
