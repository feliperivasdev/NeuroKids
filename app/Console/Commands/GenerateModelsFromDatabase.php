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
        $modelPath = base_path("app/Models/{$modelName}.php");

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
        
        // Consulta específica para PostgreSQL
        $results = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        
        foreach ($results as $result) {
            $tables[] = $result->table_name;
        }

        return $tables;
    }

    /**
     * Obtiene las columnas de una tabla
     */
    private function getTableColumns($tableName)
    {
        // Consulta específica para PostgreSQL
        return DB::select("
            SELECT 
                column_name as field,
                data_type as type,
                is_nullable as null,
                column_default as default,
                character_maximum_length as length
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = ?
            ORDER BY ordinal_position
        ", [$tableName]);
    }

    /**
     * Obtiene las columnas fillable
     */
    private function getFillableColumns($columns)
    {
        $fillable = [];
        
        foreach ($columns as $column) {
            $field = $column->field;
            
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
            $field = $column->field;
            
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
            $field = $column->field;
            $type = $column->type;
            
            // Determinar el cast basándose en el tipo de columna de PostgreSQL
            if (str_contains($type, 'int') || str_contains($type, 'serial')) {
                $casts[$field] = 'integer';
            } elseif (str_contains($type, 'decimal') || str_contains($type, 'numeric') || str_contains($type, 'real') || str_contains($type, 'double')) {
                $casts[$field] = 'float';
            } elseif (str_contains($type, 'timestamp')) {
                $casts[$field] = 'datetime';
            } elseif (str_contains($type, 'date')) {
                $casts[$field] = 'date';
            } elseif (str_contains($type, 'json') || str_contains($type, 'jsonb')) {
                $casts[$field] = 'array';
            } elseif (str_contains($type, 'boolean')) {
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

        // Verificar si la tabla tiene timestamps
        $hasTimestamps = $this->tableHasTimestamps($tableName);

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
    public \$timestamps = " . ($hasTimestamps ? 'true' : 'false') . ";

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

    /**
     * Verifica si la tabla tiene columnas de timestamps
     */
    private function tableHasTimestamps($tableName)
    {
        $columns = $this->getTableColumns($tableName);
        $columnNames = [];
        
        foreach ($columns as $column) {
            $columnNames[] = $column->field;
        }
        
        return in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);
    }
} 