<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateJobAttemptsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('job_attempts', [
            'id' => $query->id()->primaryKey(),
            'job_id' => $query->integer('job_id')->notNull(),
            'attempt_number' => $query->integer('attempt_number')->notNull(),
            'status' => $query->string('status', 50)->notNull()->default('pending'),
            'started_at' => $query->integer('started_at')->nullable(),
            'completed_at' => $query->integer('completed_at')->nullable(),
            'duration' => $query->integer('duration')->nullable(),
            'error_message' => $query->string('error_message')->nullable(),
            'exception_trace' => $query->string('exception_trace')->nullable(),
            'worker_name' => $query->string('worker_name', 255)->nullable(),
            'created_at' => $query->timestamp('created_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_attempts_job_id ON job_attempts(job_id)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_attempts_status ON job_attempts(status)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_attempts_started_at ON job_attempts(started_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('job_attempts')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
