<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GenerateModelsFromDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:models-from-db {--table= : Nombre específico de la tabla} {--all : Generar modelos para todas las tablas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera modelos Eloquent basándose en las tablas de la base de datos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tableName = $this->option('table');
        $generateAll = $this->option('all');

        if ($tableName) {
            $this->generateModelForTable($tableName);
        } elseif ($generateAll) {
            $this->generateModelsForAllTables();
        } else {
            $this->error('Debes especificar --table=nombre_tabla o --all');
            return 1;
        }

        return 0;
    }

    /**
     * Genera modelos para todas las tablas
     */
    private function generateModelsForAllTables()
    {
        $tables = $this->getTables();
        
        $this->info('Generando modelos para ' . count($tables) . ' tablas...');
        
        foreach ($tables as $table) {
            $this->generateModelForTable($table);
        }
        
        $this->info('¡Modelos generados exitosamente!');
    }

    /**
     * Genera un modelo para una tabla específica
     */
    private function generateModelForTable($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            $this->error("La tabla '{$tableName}' no existe.");
            return;
        }

        $modelName = Str::studly(Str::singular($tableName));
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            if (!$this->confirm("El modelo {$modelName} ya existe. ¿Deseas sobrescribirlo?")) {
                return;
            }
        }

        $columns = $this->getTableColumns($tableName);
        $fillable = $this->getFillableColumns($columns);
        $hidden = $this->getHiddenColumns($columns);
        $casts = $this->getColumnCasts($columns);

        $modelContent = $this->generateModelContent($modelName, $tableName, $fillable, $hidden, $casts);

        file_put_contents($modelPath, $modelContent);

        $this->info("Modelo {$modelName} generado exitosamente en: {$modelPath}");
    }

    /**
     * Obtiene todas las tablas de la base de datos
     */
    private function getTables()
    {
        $tables = [];
        $databaseName = DB::connection()->getDatabaseName();
        
        $results = DB::select("SHOW TABLES FROM `{$databaseName}`");
        
        foreach ($results as $result) {
            $tables[] = array_values((array) $result)[0];
        }

        return $tables;
    }

    /**
     * Obtiene las columnas de una tabla
     */
    private function getTableColumns($tableName)
    {
        return DB::select("DESCRIBE `{$tableName}`");
    }

    /**
     * Obtiene las columnas fillable
     */
    private function getFillableColumns($columns)
    {
        $fillable = [];
        
        foreach ($columns as $column) {
            $column = (array) $column;
            $field = $column['Field'];
            
            // Excluir columnas comunes que no deberían ser fillable
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = $field;
            }
        }

        return $fillable;
    }

    /**
     * Obtiene las columnas hidden
     */
    private function getHiddenColumns($columns)
    {
        $hidden = [];
        
        foreach ($columns as $column) {
            $column = (array) $column;
            $field = $column['Field'];
            
            // Incluir columnas sensibles en hidden
            if (in_array($field, ['password', 'contrasena', 'contrasena_hash', 'api_token'])) {
                $hidden[] = $field;
            }
        }

        return $hidden;
    }

    /**
     * Obtiene los casts para las columnas
     */
    private function getColumnCasts($columns)
    {
        $casts = [];
        
        foreach ($columns as $column) {
            $column = (array) $column;
            $field = $column['Field'];
            $type = $column['Type'];
            
            // Determinar el cast basándose en el tipo de columna
            if (str_contains($type, 'int')) {
                $casts[$field] = 'integer';
            } elseif (str_contains($type, 'decimal') || str_contains($type, 'float')) {
                $casts[$field] = 'float';
            } elseif (str_contains($type, 'datetime')) {
                $casts[$field] = 'datetime';
            } elseif (str_contains($type, 'date')) {
                $casts[$field] = 'date';
            } elseif (str_contains($type, 'json')) {
                $casts[$field] = 'array';
            } elseif (str_contains($type, 'boolean') || str_contains($type, 'tinyint(1)')) {
                $casts[$field] = 'boolean';
            }
        }

        return $casts;
    }

    /**
     * Genera el contenido del modelo
     */
    private function generateModelContent($modelName, $tableName, $fillable, $hidden, $casts)
    {
        $fillableString = "['" . implode("', '", $fillable) . "']";
        $hiddenString = "['" . implode("', '", $hidden) . "']";
        
        $castsString = "[\n";
        foreach ($casts as $field => $cast) {
            $castsString .= "        '{$field}' => '{$cast}',\n";
        }
        $castsString .= "    ]";

        return "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class {$modelName} extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected \$table = '{$tableName}';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected \$fillable = {$fillableString};

    /**
     * Los atributos que deben ocultarse para arrays.
     *
     * @var array
     */
    protected \$hidden = {$hiddenString};

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected \$casts = {$castsString};

    /**
     * Indica si el modelo debe ser timestamped.
     *
     * @var bool
     */
    public \$timestamps = " . (in_array('created_at', array_column($this->getTableColumns($tableName), 'Field')) ? 'true' : 'false') . ";

    /**
     * Obtiene el nombre de la clave primaria.
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'id';
    }
}
";
    }
} 