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

class NoauthDatabase extends Controller{
   public function connectRemoteDatabaseNoAuth(Request $request){
        Log::info('Connecting to remote database without auth');
        $data = json_decode($request->body, true);
        Log::info($request->all());
        Log::info($data['host']);
        try {
            $config=[
                'driver'    => 'mysql',
                'host'      => $data['host'],
                'database'  => $data['database'],
                'username'  => $data['username'],
                'password'  => $data['password'],
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ];
            config(['database.connections.remote_connection' => $config]);
            $connection = DB::connection('remote_connection');
            // Test the connection
            $connection->getPdo();
            $gettables = $connection->select('SHOW TABLES');
            $tableNames = collect($gettables)->map(function ($table) {
    return array_values((array)$table)[0];
});

            Log::info('Remote DB Connection Successful',$tableNames->toArray());
            // $connection = DB::connection([
            //     'driver' => 'mysql',
            //     'host' => $request->host,
            //     'port' => 3306,
            //     'database' => $request->database,
            //     'username' => $request->username,
            //     'password' => $request->password,
            // ]);

            // Test the connection
            // $connection->getPdo();
            // $gettables = $connection->getDoctrineSchemaManager()->listTableNames();
            // Log::info($gettables);
        
            return response()->json(['message' => 'Connection successful','tables'=>$tableNames->toArray()], 200);
        } catch (\Exception $e) {
            Log::error('Remote DB Connection Error: '.$e->getMessage());
            return response()->json(['message' => 'Connection failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRemoteTableDataNoAuth(Request $request){
        // $request->validate([
        //     'host' => 'required|string',
        //     'database' => 'required|string',
        //     'username' => 'required|string',
        //     'password' => 'required|string',
        //     'table' => 'required|string',
        // ]);
        Log::info('Getting remote table data without auth');
        Log::info($request->all());
        $data = json_decode($request->body, true);
        try {
            $config=[
                'driver'    => 'mysql',
                'host'      => $data['host'],
                'database'  => $data['database'],
                'username'  => $data['username'],
                'password'  => $data['password'],
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ];
                        config(['database.connections.remote_connection' => $config]);
            $connection = DB::connection('remote_connection');
            // Test the connection
            $connection->getPdo();
            $tablename=$data['table'];
            $data = $connection->table($data['table'])->get();
            $columns = collect($connection->select("SHOW COLUMNS FROM ".$tablename))
            ->pluck('Field');
            Log::info('Remote Table Data Retrieved Successfully',['columns' => $columns,'data' => $data]);

            // $connection = DB::connection([
            //     'driver' => 'mysql',
            //     'host' => $request->host,
            //     'port' => 3306,
            //     'database' => $request->database,
            //     'username' => $request->username,
            //     'password' => $request->password,
            // ]);

            // $data = $connection->table($request->table)->get();
            // Log::info('Remote Table Data Retrieved Successfully','columns' => $columns,'data' => $data);
            return response()->json(['data' => $data,'columns' => $columns], 200);
        } catch (\Exception $e) {
            Log::error('Get Remote Table Data Error: '.$e->getMessage());
            return response()->json(['message' => 'Failed to retrieve data', 'error' => $e->getMessage()], 500);
        }
    }
}