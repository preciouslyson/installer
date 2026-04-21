<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateQueueWorkersTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('queue_workers', [
            'id' => $query->id()->primaryKey(),
            'name' => $query->string('name', 255)->notNull()->unique(),
            'queue' => $query->string('queue', 255)->notNull()->default('default'),
            'status' => $query->string('status', 50)->notNull()->default('idle'),
            'process_id' => $query->integer('process_id')->nullable(),
            'jobs_processed' => $query->integer('jobs_processed')->notNull()->default(0),
            'jobs_failed' => $query->integer('jobs_failed')->notNull()->default(0),
            'memory_usage' => $query->integer('memory_usage')->nullable(),
            'last_heartbeat' => $query->integer('last_heartbeat')->nullable(),
            'started_at' => $query->integer('started_at')->notNull(),
            'stopped_at' => $query->integer('stopped_at')->nullable(),
            'created_at' => $query->timestamp('created_at')->default('CURRENT_TIMESTAMP'),
            'updated_at' => $query->timestamp('updated_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_workers_status ON queue_workers(status)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_workers_queue ON queue_workers(queue)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_workers_last_heartbeat ON queue_workers(last_heartbeat)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('queue_workers')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
