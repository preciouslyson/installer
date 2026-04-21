<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateQueueEventsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('queue_events', [
            'id' => $query->id()->primaryKey(),
            'event_type' => $query->string('event_type', 100)->notNull(),
            'job_id' => $query->integer('job_id')->nullable(),
            'worker_name' => $query->string('worker_name', 255)->nullable(),
            'queue_name' => $query->string('queue_name', 255)->nullable(),
            'payload' => $query->string('payload')->nullable(),
            'metadata' => $query->string('metadata')->nullable(),
            'created_at' => $query->timestamp('created_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_events_event_type ON queue_events(event_type)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_events_job_id ON queue_events(job_id)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_events_worker_name ON queue_events(worker_name)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_events_created_at ON queue_events(created_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('queue_events')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
