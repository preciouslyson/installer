<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateJobBatchesTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('job_batches', [
            'id' => $query->string('id', 255)->primary()->notNull(),
            'name' => $query->string('name', 255)->notNull(),
            'total_jobs' => $query->integer('total_jobs')->notNull(),
            'pending_jobs' => $query->integer('pending_jobs')->notNull(),
            'failed_jobs' => $query->integer('failed_jobs')->notNull()->default(0),
            'failed_job_ids' => $query->string('failed_job_ids')->nullable(),
            'options' => $query->string('options')->nullable(),
            'cancelled_at' => $query->integer('cancelled_at')->nullable(),
            'created_at' => $query->integer('created_at')->notNull(),
            'finished_at' => $query->integer('finished_at')->nullable(),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_batches_name ON job_batches(name)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_batches_finished_at ON job_batches(finished_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('job_batches')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
