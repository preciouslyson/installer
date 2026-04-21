<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateJobsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('jobs', [
            'id' => $query->id()->primaryKey(),
            'queue' => $query->string('queue', 255)->notNull()->default('default'),
            'payload' => $query->string('payload')->notNull(),
            'attempts' => $query->integer('attempts')->notNull()->default(0),
            'reserved_at' => $query->integer('reserved_at')->nullable(),
            'available_at' => $query->integer('available_at')->notNull(),
            'created_at' => $query->integer('created_at')->notNull(),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes for better query performance
        DatabaseConnection::executeQuery("CREATE INDEX idx_jobs_queue ON jobs(queue)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_jobs_reserved_at ON jobs(reserved_at)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_jobs_available_at ON jobs(available_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('jobs')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
