<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateJobLogsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('job_logs', [
            'id' => $query->id()->primaryKey(),
            'job_id' => $query->integer('job_id')->notNull(),
            'level' => $query->string('level', 50)->notNull()->default('info'),
            'message' => $query->string('message')->notNull(),
            'context' => $query->string('context')->nullable(),
            'extra' => $query->string('extra')->nullable(),
            'created_at' => $query->timestamp('created_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_logs_job_id ON job_logs(job_id)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_logs_level ON job_logs(level)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_job_logs_created_at ON job_logs(created_at)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('job_logs')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
