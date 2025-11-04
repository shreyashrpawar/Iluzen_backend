<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Server;
use App\Models\Request as RequestModel;
use App\Models\UserDatabase;
use App\Models\RemoteDatabase;

class DatabaseController extends Controller{
    public function createDatabase(Request $request){
        $user = auth()->user();
        DB::statement('CREATE DATABASE ' . $request->database_name);
        DB::statement("CREATE USER IF NOT EXISTS'" . $user->name . "'@'%' IDENTIFIED BY '" . $request->password . "'");
        $authorize=DB::statement("GRANT ALL PRIVILEGES ON " . $request->database_name . ".* TO '" . $user->name . "'@'%'");
        UserDatabase::create([
            'user_id' => auth()->id(),
            'database_name' => $request->database_name,
        ]);

        return response()->json([
            'message' => 'Database created successfully.',
        ]);
    }
    public function getDatabases(Request $request){
        $user = auth()->user();
        $databases=DB::select("SELECT DISTINCT DB as DATABASE_NAME FROM mysql.db WHERE USER='" . $user->name."'");
        // $servers = Server::where('user_id', $user->id)->get();
        return response()->json([
            'databases' => $databases,
        ]);
    }
public function getTable(Request $request, $database)
{
    $user = auth()->user();
    $database = trim(strtolower($database));

    // Check if user has access to the database
    $hasAccess = UserDatabase::where('user_id', $user->id)
        ->where('database_name', $database)
        ->exists();

    if ($hasAccess) {
        // Switch to that DB
        DB::statement("USE `$database`");

        // Fetch all tables
        $tables = DB::select("SHOW TABLES FROM `$database`");

        // Clean up output
        $tableList = collect($tables)->map(function ($table) {
            return array_values((array) $table)[0];
        });

        return response()->json([
            'tables' => $tableList,
        ]);
    }

    return response()->json([
        'message' => 'Database not found or access denied.',
    ], 404);
}


public function createTable(Request $request, $database)
{
    try {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
            'columns.*.type' => 'required|string',
            'columns.*.length' => 'nullable|integer|min:1',
            'columns.*.nullable' => 'nullable|boolean',
            'columns.*.default' => 'nullable',
        ]);

        $tableName = $validated['name'];
        $columns = $validated['columns'];

        DB::statement('USE `' . str_replace('`', '``', $database) . '`');

        $queryParts = [];
        $queryParts[] = "`id` INT AUTO_INCREMENT PRIMARY KEY";

        foreach ($columns as $col) {
            $name = "`" . str_replace('`', '``', $col['name']) . "`";
            $type = strtoupper($col['type']);
            $length = isset($col['length']) ? "({$col['length']})" : "";

            $definition = "{$name} {$type}{$length}";

            if (isset($col['nullable']) && $col['nullable'] === false) {
                $definition .= " NOT NULL";
            }

            if (isset($col['default']) && $col['default'] !== null && $col['default'] !== '') {
                $safeDefault = addslashes($col['default']);
                $definition .= " DEFAULT '{$safeDefault}'";
            }

            $queryParts[] = $definition;
        }

$createTableQuery = "CREATE TABLE `{$database}`.`{$tableName}` (" 
    . implode(', ', $queryParts) 
    . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


        Log::info("Executing query: " . $createTableQuery);

        DB::statement($createTableQuery);

        return response()->json(['message' => 'Table created successfully.'], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Input validation errors
        return response()->json(['error' => $e->errors()], 422);

    } catch (\Illuminate\Database\QueryException $e) {
        // SQL or DB errors
        Log::error('Create table failed: ' . $e->getMessage());
        return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);

    } catch (\Exception $e) {
        // Other exceptions
        Log::error('Unexpected error: ' . $e->getMessage());
        return response()->json(['error' => 'Unexpected server error.'], 500);
    }
}
    public function getTableColumns(Request $request, $database, $table)
    {
        try {
            $user = auth()->user();
            $database = trim(strtolower($database));
            $table = trim($table);

            // Check access
            $hasAccess = UserDatabase::where('user_id', $user->id)
                ->where('database_name', $database)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['message' => 'Access denied.'], 403);
            }

            DB::statement("USE `$database`");

            // Get column information
            $columns = DB::select("DESCRIBE `$table`");

            // Format column data
            $formattedColumns = collect($columns)->map(function ($col) {
                return [
                    'name' => $col->Field,
                    'type' => $col->Type,
                    'nullable' => $col->Null === 'YES',
                    'key' => $col->Key,
                    'default' => $col->Default,
                    'extra' => $col->Extra
                ];
            });

            return response()->json([
                'columns' => $formattedColumns,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get table columns failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch columns.'], 500);
        }
    }
    public function deleteTable(Request $request, $database)
    {
        try {
            $user = auth()->user();
            $database = trim(strtolower($database));

            // Check access
            $hasAccess = UserDatabase::where('user_id', $user->id)
                ->where('database_name', $database)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['message' => 'Access denied.'], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|regex:/^[A-Za-z0-9_]+$/',
            ]);

            $tableName = str_replace('`', '``', $validated['name']);

            DB::statement("USE `$database`");
            DB::statement("DROP TABLE IF EXISTS `$tableName`");

            return response()->json(['message' => 'Table deleted successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Delete table failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete table.'], 500);
        }
    }
    public function insertData(Request $request, $database, $table)
    {
        try {
            $user = auth()->user();
            $database = trim(strtolower($database));
            $table = trim($table);

            // Check access
            $hasAccess = UserDatabase::where('user_id', $user->id)
                ->where('database_name', $database)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['message' => 'Access denied.'], 403);
            }

            $validated = $request->validate([
                'data' => 'required|array',
            ]);

            $data = $validated['data'];

            // Remove empty values and 'id' if present
            $data = array_filter($data, function($value, $key) {
                return $key !== 'id' && $value !== '' && $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            if (empty($data)) {
                return response()->json(['error' => 'No valid data to insert.'], 422);
            }

            DB::statement("USE `$database`");

            // Prepare column names and values
            $columns = array_keys($data);
            $values = array_values($data);

            $columnList = implode('`, `', array_map(function($col) {
                return str_replace('`', '``', $col);
            }, $columns));

            $placeholders = implode(', ', array_fill(0, count($values), '?'));

            $query = "INSERT INTO `$table` (`$columnList`) VALUES ($placeholders)";

            Log::info("Insert query: " . $query, ['values' => $values]);

            DB::insert($query, $values);

            return response()->json(['message' => 'Data inserted successfully.'], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Insert data failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to insert data: ' . $e->getMessage()], 500);
        }
    }

    public function getTableData(Request $request, $database, $table)
    {
        try {
            $user = auth()->user();
            $database = trim(strtolower($database));
            $table = trim($table);

            // Check access
            $hasAccess = UserDatabase::where('user_id', $user->id)
                ->where('database_name', $database)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['message' => 'Access denied.'], 403);
            }

            DB::statement("USE `$database`");

            // Get all data from table
            $data = DB::select("SELECT * FROM `$table`");

            // Get column information
            $columns = DB::select("DESCRIBE `$table`");

            return response()->json([
                'data' => $data,
                'columns' => collect($columns)->map(function ($col) {
                    return [
                        'name' => $col->Field,
                        'type' => $col->Type,
                    ];
                }),
                'total' => count($data),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get table data failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch data.'], 500);
        }
    }

    public function deleteData(Request $request, $database, $table)
    {
        try {
            $user = auth()->user();
            $database = trim(strtolower($database));
            $table = trim($table);

            // Check access
            $hasAccess = UserDatabase::where('user_id', $user->id)
                ->where('database_name', $database)
                ->exists();

            if (!$hasAccess) {
                return response()->json(['message' => 'Access denied.'], 403);
            }

            $validated = $request->validate([
                'id' => 'required|integer',
            ]);

            DB::statement("USE `$database`");

            $id = $validated['id'];
            DB::delete("DELETE FROM `$table` WHERE `id` = ?", [$id]);

            return response()->json(['message' => 'Data deleted successfully.'], 200);

        } catch (\Exception $e) {
            Log::error('Delete data failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete data.'], 500);
        }
    }

public function connectRemoteDatabase(Request $request){
    $user = auth()->user();
    RemoteDatabase::create([
        'user_id' => auth()->id(),
        'database_host' => $request->host,
        'database_name' => $request->database_name,
        'user_name' => $request->username,
        'user_password' => $request->password,
    ]);
    $config=[
        'driver'    => 'mysql',
        'host'      => $request->host,
        'database'  => $request->database_name,
        'username'  => $request->username,
        'password'  => $request->password,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ];
    config(['database.connections.remote_connection' => $config]);
    try{
        $pdo=DB::connection('remote_connection')->getPdo();
    }catch(\Exception $e){
        return response()->json([
            'message' => 'Connection to remote database failed: '.$e->getMessage(),
        ],500); 
    }
    return response()->json([
        'message' => 'Connected to remote database successfully.',
    ]);
}

//         public function deleteRequests(Request $request,$subdomain){
//                 // $user = auth()->user();
//                 $server_id=Server::where('subdomain', $subdomain)->first()->id;
//                 $delete=\App\Models\Request::where([
//     ['server_id', $server_id],
//     ['id', $request->id]
// ])->delete();
//                 return response()->json([
//                     'message' => 'Requests deleted successfully.',
//                 ]);
//     }


//     public function deleteServer(Request $request){
//         $server = Server::find($request->id);
//         $requests = RequestModel::where('server_id', $server->id)->delete();
//         if (!$server || $server->user_id !== auth()->id()) {
//             return response()->json(['message' => 'Server not found or unauthorized.'], 404);
//         }
//         $server->delete();
//         return response()->json(['message' => 'Server deleted successfully.']);
//     }
}