<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160926154110 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE  `orders` ADD  `updatedTime` INT(10) NOT NULL AFTER  `createdTime`");
        $this->addSql("UPDATE `orders` SET `updatedTime` = (select if(max(createdTime),max(createdTime),0) from `order_log` where order_log.orderId = orders.id)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
