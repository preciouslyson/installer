<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;

class CreateQueueConnectionsTable
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->createTable('queue_connections', [
            'id' => $query->id()->primaryKey(),
            'name' => $query->string('name', 255)->notNull()->unique(),
            'driver' => $query->string('driver', 100)->notNull(),
            'host' => $query->string('host', 255)->nullable(),
            'port' => $query->integer('port')->nullable(),
            'database' => $query->string('database', 255)->nullable(),
            'username' => $query->string('username', 255)->nullable(),
            'password' => $query->text('password')->nullable(),
            'prefix' => $query->string('prefix', 50)->nullable(),
            'options' => $query->string('options')->nullable(),
            'is_active' => $query->boolean('is_active')->notNull()->default(true),
            'last_connected_at' => $query->integer('last_connected_at')->nullable(),
            'created_at' => $query->timestamp('created_at')->default('CURRENT_TIMESTAMP'),
            'updated_at' => $query->timestamp('updated_at')->default('CURRENT_TIMESTAMP'),
        ])->compileCreateTable();
        
        DatabaseConnection::executeQuery($sql);
        
        // Add indexes
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_connections_driver ON queue_connections(driver)");
        DatabaseConnection::executeQuery("CREATE INDEX idx_queue_connections_is_active ON queue_connections(is_active)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $query = new QueryBuilder('');
        $sql = $query->dropTable('queue_connections')->compileDropTable();
        DatabaseConnection::executeQuery($sql);
    }
}
