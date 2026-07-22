<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Database 
{
    public static function dbCachePrefetchTemplate() { return <<<'PHP'
<?php

use Mlangeni\Machinjiri\Core\Database\Builders\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\Caching\PrefetchManager;

return [
    /**
     * An example warmer to warm the users table as it will be 
     * frequently used by application
     */
    /*"users" => function (PrefetchManager $manager) {
        // Register a custom warmer that caches the most used user query
        $manager->registerWarmer('users:active', function ($cache) {
            // Build your query (example using QueryBuilder)
            $result = (new QueryBuilder("users"))
                      ->select(['*'])->get();
            $key = 'dbq:' . md5('SELECT * FROM users WHERE active = ?') . ':' . md5('1');
            $cache->tags(['users'])->set($key, $result, 4);
        });
        $manager->warm('users:active');
    },*/
];
PHP;
  }
}