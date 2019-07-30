<?php

use Phinx\Migration\AbstractMigration;

class PrintModuleInitMigration extends AbstractMigration
{
    public function up()
    {
        $sql = <<<SQL
SET NAMES utf8mb4;
SET time_zone = '+00:00';


CREATE TABLE IF NOT EXISTS `print_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `exported_at` datetime NOT NULL,
  `export_date` datetime NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'new',
  `institution_name` varchar(255) DEFAULT NULL,
  `meta` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `address_id` (`address_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `exported_at_export_date_status` (`exported_at`,`export_date`,`status`),
  KEY `type` (`type`),
  KEY `export_date` (`export_date`),
  CONSTRAINT `print_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `print_subscriptions_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `print_subscriptions_ibfk_3` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $this->execute($sql);
    }

    public function down()
    {
        // TODO: [refactoring] add down migrations for module init migrations (needs confirmation dialog)
        $this->output->writeln('Down migration is not available.');
    }
}
