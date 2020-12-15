<?php
namespace Rodrixcornell\ApiGenerate;

use Rodrixcornell\ApiGenerate\DbSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ApiGenerate extends Command
{
	protected $dbSettings;
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'generate:api  {--con=0}  {--table=0}  {--route=0}  {--module=0}  {--relation=0}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate api resource';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$table  = $this->option('table');
		$route  = $this->option('route');
		$module = $this->option('module');
		$conn   = $this->option('con');
		$hasRelation = ($this->option('relation') != '0' );

		if($conn=='0'){
			$this->makeModule($table, $route, $module, false, $hasRelation);
		} else {
			$this->dbSettings = new DbSettings;
			$this->con = $conn;
			$tables = $this->dbSettings->getTables();
			if (!empty($tables)) {
				foreach ($tables as $tableName) {
					$this->makeModule($tableName, $tableName, $module, $conn, $hasRelation);
				}
			} else {
				$this->info('Empty connection '.$conn.'!');
			}
		}
	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	}

	private function makeModule($table, $route='0', $module='0', $con=false, $hasRelation = false)
	{
		$module = ($module=='0')? 'Api' : $module;
		$route  = ($route=='0')? $table : $route;

		// $singular = Str::of($table)->singular();
		// php 7.0
		$singular = str_singular($table);
		$package = $this->setPackage($singular);
		$packageUcWords = $this->setPackage($table);
		$packageLower = strtolower($package);

		$root = app_path().DIRECTORY_SEPARATOR;
		$app = $root.'Modules'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR;
		// $app = $root.'Modules'.DIRECTORY_SEPARATOR.$packageUcWords.DIRECTORY_SEPARATOR;

		if(@mkdir($root.'Modules',0755,true)){
			mkdir($root.'Modules'.DIRECTORY_SEPARATOR.'Api',0755,true);
		}

		if (empty($table)) {
			$this->info("Table name not found! use --table=table_name");
			die;
		}

		if (empty($route)) {
			$this->info("Route name not found! use --route=route-name");
			die;
		}
		if ($module) {
			$module = 'Modules'.DIRECTORY_SEPARATOR.$module;
		}else {
			$module = 'Modules';
		}

		$controller = '<?php
namespace App\\'.$module.'\\'.$packageUcWords.'\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\\'.$module.'\\'.$packageUcWords.'\Services\\'.$package.'Service;

class '.$package.'Controller extends Controller
{
	private $'.$packageLower.'Service;

	function __construct('.$package.'Service $'.$packageLower.'Service ){
		$this->'.$packageLower.'Service = $'.$packageLower.'Service;
	}

	public function index(Request $request)
	{
		try {
			$data =  $this->'.$packageLower.'Service->index($request);
			return response()->json($data, 200);
		} catch(\Exception $e){
			$data = [
				"message"=> "Error, try again!",
				"text"=> $e->getMessage()
			];
			return response()->json($data, 401);
		}
	}

	public function show($id)
	{
		try {
			$data = $this->'.$packageLower.'Service->show($id);
			return response()->json($data, 200);
		} catch(\Exception $e){
			$data = [
				"message"=> "Error, try again!",
				"text"=> $e->getMessage()
			];
			return response()->json($data, 400);
		}
	}

	public function store(Request $request)
	{
		try {
			$data = $this->'.$packageLower.'Service->store($request);
			return response()->json($data, 200);
		} catch(\Exception $e){
			$data = [
				"message"=> "Error, try again!",
				"code" => $e->getCode(),
				"text "=> $e->getMessage()
			];
			return response()->json($data, 400);
		}
	}

	public function update(Request $request, $id)
	{
		try {
			$data = $this->'.$packageLower.'Service->update($request, $id);
			return response()->json($data, 200);
		} catch(\Exception $e){
			$data = [
				"message" => "Error, try again!",
				 "code" => $e->getCode(),
				"text" => $e->getMessage()
			];
			return response()->json($data, 400);
		}
	}

	public function destroy($id)
	{
		try {
			$data = $this->'.$packageLower.'Service->destroy($id);
			return response()->json($data, 200);
		} catch(\Exception $e){
			$data = [
				"message"=> "Error, try again!",
				"text"=> $e->getMessage()
			];
			return response()->json($data, 400);
		}
	}
}';
		# get list of fields
		$columns = Schema::getColumnListing($table);
		$filtersFields = (array) $columns;
		// exclude laravel fields
		$filtersFields = array_diff($filtersFields, ["created_at", "updated_at", "deleted_at",]);
		$fields = "";
		$fields = "'".implode("', '",$filtersFields)."'";
		if (count($columns)==0) {
			echo "The table ".$table." not exists!";
			die;
		}
		$relations = '';
		$with = [];
		foreach ($filtersFields as $field) {
			$has = ($con || $hasRelation) ? true : false;
			if(substr($field, -3)=='_id') {
				$verify = $this->findModels($module, $field, $has);
				// $verify = $this->findModels($module, $field);
				if($verify){
					$relations .= $verify;
					$with[] = str_replace('_id','',$field);
				}
			}
		}
		$model = '<?php
namespace App\\'.$module.'\\'.$packageUcWords.'\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class '.$package.' extends Model
{
	use Notifiable;
	use SoftDeletes;

	protected $table = \''.$table.'\';

	protected $primaryKey = \'id\';

	public $incrementing = true;

	protected $softDelete = true;

	protected $dates = [
		\'created_at\', \'updated_at\',  \'deleted_at\',
	];

	protected $guarded = [
		\'id\', \'created_at\', \'updated_at\', \'deleted_at\',
	];

	protected $fillable = [
		'.$fields.'
	];

	'.$relations.'
}';

		// Fields array
		$dbFieldsTxt = '$data = [';
		foreach ($filtersFields as $field) {
			if(!in_array($field,['id'])){
				$dbFieldsTxt .= '
				"'.$field.'" => $request->'.$field.',';
			}
		}
		$dbFieldsTxt .= '
			];';

		$allRelations ='';
		if(!empty($with)){
			$allRelations = '"'.implode('", "', $with).'"';
		}

		// Validator
		$this->dbSettings = new DbSettings();
		$tableProp = $this->dbSettings->getTableProp($table);
		$Validator = '$validator = Validator::make($request->all(), [';
		foreach ($tableProp as $prop) {
			if(!in_array($prop[0]['name'], ["id","created_at", "updated_at", "deleted_at",])) {
				$Validator .= '
			"'.$prop[0]['name'].'" => "'.(($prop[0]['nullable']=='no')? 'required' : 'nullable' ).'",';
			}
		}
		$Validator .= '
		]);';

$Service = '<?php
namespace App\\'.$module.'\\'.$packageUcWords.'\Services;

use App\\'.$module.'\\'.$packageUcWords.'\Models\\'.$package.';
use App\\'.$module.'\\'.$packageUcWords.'\Services\\'.$package.'SearchService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class '.$package.'Service
{
	private $'.$packageLower.'SearchService;
	function __construct('.$package.'SearchService $'.$packageLower.'SearchService) {
		$this->'.$packageLower.'SearchService = $'.$packageLower.'SearchService;
	}

	public function index(Request $request)
	{
		return $this->'.$packageLower.'SearchService->search('.$package.'::with(['.$allRelations.']), $request);
	}

	public function show($id)
	{
		$'.$packageLower.' = '.$package.'::findOrFail($id);

		return $'.$packageLower.';
		return '.$package.'::where(["id" => $id])->first();
	}

	public function store($request)
	{
		'.$Validator.'
		if($validator->fails()) {
			throw new \Exception("invalid_fields",400);
		} else {
			'.$dbFieldsTxt.'
			return '.$package.'::create($data);
		}
	}

	public function update($request, $id)
	{
		'.$Validator.'
		if($validator->fails()) {
			throw new \Exception("invalid_fields",400);
		} else {
			'.$dbFieldsTxt.'
			return '.$package.'::where(["id" => $id])->update($data);
		}
	}

	public function destroy($id)
	{
		return '.$package.'::where(["id" => $id])->delete();
	}
}';

		$filters = "";
		foreach ($filtersFields as $field) {
			if(!in_array($field,['created_at','updated_at','deleted_at'])){
				$filters .= 'if ($request->'.$field.') {
			$queryBuilder->where("'.$field.'", "=", $request->'.$field.');
		}

		';
			}
		}

		$id = (isset($filtersFields[0])) ? $filtersFields[0] : 'id';

		$ServiceSearch = '<?php
namespace App\\'.$module.'\\'.$packageUcWords.'\Services;

class '.$package.'SearchService
{
	public function search($queryBuilder, $request)
	{
		'.$filters.'if ($request->order) {
			$order = ($request->order == "asc") ? "asc" : "desc";
			$queryBuilder->orderBy("'.$id.'", $order);
		}

		return $queryBuilder->paginate(($request->count) ? $request->count : 20);
	}
}';
// dd($ServiceSearch);

		if (!is_dir($app)) {
			$this->info('The module '.$module.' not exists');
			die;
		}
		// $mod = $app.$package;
		$mod = $app.$packageUcWords;

		if(@mkdir($mod,0755,true)) {
			// Directories: Models | Controlers | Services
			mkdir($mod.DIRECTORY_SEPARATOR.'Models',0755,true);
			mkdir($mod.DIRECTORY_SEPARATOR.'Controllers',0755,true);
			mkdir($mod.DIRECTORY_SEPARATOR.'Services',0755,true);
			// Archives: Models | Controlers | Services
			$model = str_replace('/',"\\",$model);
			$controller = str_replace('/',"\\",$controller);
			$Service = str_replace('/',"\\",$Service);
			$ServiceSearch = str_replace('/',"\\",$ServiceSearch);
			File::put($mod.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.$package.'.php', $model);
			File::put($mod.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.$package.'Controller.php', $controller);
			File::put($mod.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.$package.'Service.php', $Service);
			File::put($mod.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.$package.'SearchService.php', $ServiceSearch);
			//
			$this->info('The module '.$package.' has created!');
			$this->info('check in '.$mod);
		} else {
			$this->info('The package '.$package.' already exists!');
		}

		if ($route) {
			$base = '\App\\'.$module.'\\';
			$ctrl = $package."Controller";
			// Criando rotas
			$path_route = base_path().DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'api.php';
			$base_package = str_replace('/',"\\",$base.$package);
			$routes ='
/**
* Module '.$package.'
*/
Route::apiResource("'.$route.'","'.$base_package.'\Controllers\\'.$ctrl.'");';

			// File::append($path_route, $routes);
			$this->info('Routes created!');
		}
	}

	private function findModels($module, $field, $all = true)
	{
		if(substr($field, -3)!='_id'){
			return false;
		}
		$table = str_replace('_id','',$field);
		$root = app_path().DIRECTORY_SEPARATOR;
		$app = $root.$module.DIRECTORY_SEPARATOR;
		$allModulesPath = $root.'Modules'.DIRECTORY_SEPARATOR."*";
		$allModules= glob($allModulesPath, GLOB_ONLYDIR);
		$allModulePath = [];
		$allPackagePath = [];
		foreach ($allModules as $modulesPath) {
			$allModulePath = glob($modulesPath.DIRECTORY_SEPARATOR."*", GLOB_ONLYDIR);
			foreach ($allModulePath as $packages) {
				$temp = glob($packages.DIRECTORY_SEPARATOR."*", GLOB_ONLYDIR);
				$allPackagePath = array_merge($allPackagePath,$temp);
				$allPackagePath = array_filter($allPackagePath,function($item){
					return (substr_count($item,'Models'))? $item : false;
				});
			}
		}
		$model = '';
		$files = [];
		foreach ($allPackagePath as $models) {
			$files = glob($models.DIRECTORY_SEPARATOR."*.php");
				foreach ($files as $file) {
				$content = File::get($file);
				if (substr_count($content,'"'.$table.'"')) {
					$className = explode('class ',$content);
					$className = explode(' extends',$className[1]);
					$model = $models.DIRECTORY_SEPARATOR.$className[0];
					$model =  explode('app',$model);
					$model =  'App'.$model[1];
				}
			}

		}

		if(empty($model) && $all===true){
			$package = $this->setPackage($table);
			$packageUcWords = ucwords($table, "_");
			// Alternative model
			$model = "Api\\$packageUcWords\Models\\$package";
		}

		$table_exists = Schema::getColumnListing($table);

		return (!empty($model) && count($table_exists)>0) ? '
	public function '.$table.'(){
		return $this->hasMany("'.$model.'","id","'.$field.'");
	}
' : false;
	}

	private function setPackage($table){
		$package = ucwords($table);
		if(substr_count($table, '_')){
			$split = explode('_',$table);
			$package = '';
			foreach ($split as $key => $value) {
				$package .= ucfirst($value);
			}
		}
		return $package;
	}
}
