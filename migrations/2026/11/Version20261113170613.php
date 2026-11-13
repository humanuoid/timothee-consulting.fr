<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113170613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (file_name VARCHAR(255) NOT NULL, alt_text VARCHAR(255) NOT NULL, path VARCHAR(190) NOT NULL, mime_type VARCHAR(255) NOT NULL, size INT NOT NULL, created_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_6A2CA10CB548B0F (path), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, model_id INT NOT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_140AB6207975B7E7 (model_id), INDEX IDX_140AB620B03A8386 (created_by_id), INDEX IDX_140AB620896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_content (locale VARCHAR(2) NOT NULL, slug VARCHAR(190) NOT NULL, title VARCHAR(255) NOT NULL, meta_title VARCHAR(255) NOT NULL, meta_description VARCHAR(255) NOT NULL, draft JSON NOT NULL, published JSON DEFAULT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, UNIQUE INDEX uniq_page_content_locale_slug (locale, slug), UNIQUE INDEX uniq_page_content_page_locale (page_id, locale), INDEX IDX_4A5DB3CC4663E4 (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_model (name VARCHAR(255) NOT NULL, model_key VARCHAR(190) NOT NULL, slots JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_B1148D0FDF53C156 (model_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, full_name VARCHAR(255) NOT NULL, locale VARCHAR(2) NOT NULL, totp_secret VARCHAR(255) DEFAULT NULL, recovery_codes JSON DEFAULT NULL, must_change_password TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6207975B7E7 FOREIGN KEY (model_id) REFERENCES page_model (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE page_content ADD CONSTRAINT FK_4A5DB3CC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6207975B7E7');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620B03A8386');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620896DBBDE');
        $this->addSql('ALTER TABLE page_content DROP FOREIGN KEY FK_4A5DB3CC4663E4');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE page_content');
        $this->addSql('DROP TABLE page_model');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
