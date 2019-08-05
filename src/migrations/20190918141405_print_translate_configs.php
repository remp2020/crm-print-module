<?php

use Phinx\Migration\AbstractMigration;

class PrintTranslateConfigs extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            update configs set display_name = 'print.config.print_export_issue.name' where name = 'print_export_issue';
            update configs set description = 'print.config.print_export_issue.description' where name = 'print_export_issue';

            update config_categories set name = 'print.config.category' where name = 'Print';
            update config_categories set icon = 'fas fa-newspaper' where name = 'print.config.category';
        ");
    }

    public function down()
    {

    }
}
