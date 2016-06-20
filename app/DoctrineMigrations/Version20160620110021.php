<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160620110021 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            DROP TABLE IF EXISTS `visit_log`;
            CREATE TABLE `visit_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
              `targertId` int(11) NOT NULL COMMENT '模块ID',
              `targertType` varchar(64) NOT NULL COMMENT '模块类型',
              `sourceUrl`  varchar(255) DEFAULT '' COMMENT '访问来源Url',
              `sourceHost` varchar(80)  DEFAULT '' COMMENT '访问来源HOST',
              `sourceName` varchar(64)  DEFAULT '' COMMENT '访问来源站点名称',
              `orderCount` int(10) unsigned  DEFAULT '0'  COMMENT '促成订单数',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0'  COMMENT '访问时间',
              `createdUserId` int(10) unsigned NOT NULL DEFAULT '0'  COMMENT '访问者',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='访问日志';

            DROP TABLE IF EXISTS `order_source_log`;
            CREATE TABLE `order_source_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
              `visitLogId` int(11) NOT NULL COMMENT '促成订单的访问日志ID',
              `orderId` int(10) unsigned  DEFAULT '0'  COMMENT '订单ID',
              `targetType` varchar(64) NOT NULL DEFAULT '' COMMENT '订单的对象类型',
              `targetId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单的对象ID',
              `createdTime` int(10) unsigned NOT NULL DEFAULT '0'  COMMENT '订单支付时间',
              `createdUser` int(10) unsigned NOT NULL DEFAULT '0'  COMMENT '订单支付者',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单促成日志';
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}