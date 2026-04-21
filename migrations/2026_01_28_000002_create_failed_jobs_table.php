<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateFailedJobsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('failed_jobs', [
            'id' => $query->id()->primaryKey(),
            'uuid' => $query->string('uuid', 255)->notNull()->unique(),
            'connection' => $query->text('connection')->notNull(),
            'queue' => $query->text('queue')->notNull(),
            'payload' => $query->string('payload')->notNull(),
            'exception' => $query->string('exception')->notNull(),
            'failed_at' => $query->timestamp('failed_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_failed_jobs_uuid ON failed_jobs(uuid)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_failed_jobs_failed_at ON failed_jobs(failed_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('failed_jobs')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
