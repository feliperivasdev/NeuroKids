<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    private GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function index()
    {
        return response()->json(Lectura::all());
    }

    public function show($id)
    {
        return response()->json(Lectura::findOrFail($id));
    }

    public function store(Request $request)
    {
        $lectura = Lectura::create($request->all());
        return response()->json($lectura, 201);
    }

    public function update(Request $request, $id)
    {
        $lectura = Lectura::findOrFail($id);
        $lectura->update($request->all());
        return response()->json($lectura);
    }

    public function destroy($id)
    {
        Lectura::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function getByNivel($nivel)
    {
        return response()->json(Lectura::where('nivel', $nivel)->get());
    }

    public function generarParaEstudiante(Request $request)
    {
        try {
            $usuario = auth('api')->user();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            // Validar parámetros opcionales
            $this->validate($request, [
                'tema' => 'nullable|string|max:255',
                // Aceptamos cualquier valor y lo casteamos manualmente (para soportar multipart/form-data)
                'guardar_en_bd' => 'nullable'
            ]);
            
            // Obtener perfil del estudiante
            $perfil = $this->obtenerPerfilEstudiante($usuario->id);
            
            // Preparar parámetros para la IA
            $parametros = [
                'tema' => $request->input('tema', 'aventura'),
                'nivel_dificultad' => round($perfil['nivel_promedio']),
                'longitud' => $perfil['nivel_promedio'] > 3 ? 'larga' : 'media',
                'tipo' => 'cuento',
                'usuario_id' => $usuario->id
            ];
            
            // Guardar en BD si se solicita (por defecto true) - casteo robusto desde string/number/bool
            $guardarEnBD = $this->boolFromRequest($request, 'guardar_en_bd', true);

            // Intento optimizado: una sola llamada a Gemini para lectura + preguntas
            $lectura = null;
            $preguntas = [];
            if ($this->gemini->isConfigured()) {
                $todo = $this->generarTodoConIA($parametros);
                if (!empty($todo['lectura'])) {
                    $lectura = Lectura::create($todo['lectura']);
                }
                if (!empty($todo['preguntas']) && $lectura) {
                    $preguntas = $this->persistirPreguntas($lectura, $todo['preguntas']);
                }
            }

            // Fallback: doble paso (lectura y luego preguntas) o simulación
            if (!$lectura) {
                $lecturaData = $this->generarLecturaConIA($parametros);
                $lectura = $guardarEnBD ? Lectura::create($lecturaData) : (object) $lecturaData;

                if ($guardarEnBD && $lectura instanceof Lectura) {
                    $preguntas = $this->generarPreguntasConIA($lectura, $parametros);
                }
            }
            
            // Auto-asignar la lectura al estudiante que la generó si está guardada
            if ($guardarEnBD && $lectura instanceof Lectura) {
                \App\Models\UsuariosLectura::create([
                    'usuario_id' => $usuario->id,
                    'lectura_id' => $lectura->id,
                    'completado' => false,
                    'intentos' => 0,
                    'fecha_inicio' => now(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $guardarEnBD ? 'Lectura generada, guardada y asignada exitosamente con preguntas' : 'Lectura generada exitosamente',
                'data' => [
                    'lectura' => $lectura,
                    'preguntas' => $preguntas
                ]
            ], $guardarEnBD ? 201 : 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generando lectura para estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generarPersonalizada(Request $request)
    {
        try {
            $this->validate($request, [
                'tema' => 'required|string|max:255',
                'nivel_dificultad' => 'required|integer|between:1,5',
                'longitud' => 'required|in:muy corta,corta,media,larga',
                // Aceptamos cualquier string y luego normalizamos; evita 422 por valores como 'opcion_multiple'
                'tipo' => 'nullable|string|max:50',
                // Aceptamos cualquier valor y lo casteamos manualmente (para soportar multipart/form-data)
                'guardar_en_bd' => 'nullable'
            ]);
            
            $usuario = auth('api')->user();
            
            // Generar con parámetros específicos
            $parametros = $request->only(['tema', 'nivel_dificultad', 'longitud', 'tipo']);
            // Normalizar tipo de lectura a valores soportados por el prompt
            $parametros['tipo'] = $this->normalizeTipoLectura($parametros['tipo'] ?? null);
            $parametros['longitud'] = $this->normalizeLongitudLectura($parametros['longitud'] ?? null);
            $parametros['usuario_id'] = $usuario->id ?? null;
            
            // Guardar en BD si se solicita (por defecto true) - casteo robusto desde string/number/bool
            $guardarEnBD = $this->boolFromRequest($request, 'guardar_en_bd', true);

            // Intento optimizado: una sola llamada a Gemini para lectura + preguntas
            $lectura = null;
            $preguntas = [];
            if ($this->gemini->isConfigured()) {
                $todo = $this->generarTodoConIA($parametros);
                if (!empty($todo['lectura'])) {
                    $lectura = $guardarEnBD ? Lectura::create($todo['lectura']) : (object) $todo['lectura'];
                }
                if (!empty($todo['preguntas']) && $guardarEnBD && $lectura instanceof Lectura) {
                    $preguntas = $this->persistirPreguntas($lectura, $todo['preguntas']);
                }
            }

            // Fallback: doble paso (lectura y luego preguntas) o simulación
            if (!$lectura) {
                $lecturaData = $this->generarLecturaConIA($parametros);
                $lectura = $guardarEnBD ? Lectura::create($lecturaData) : (object) $lecturaData;

                if ($guardarEnBD && $lectura instanceof Lectura) {
                    $preguntas = $this->generarPreguntasConIA($lectura, $parametros);
                }
            }
            
            // Auto-asignar la lectura al usuario que la generó (si está autenticado y guardada)
            if ($usuario && $guardarEnBD && $lectura instanceof Lectura) {
                \App\Models\UsuariosLectura::create([
                    'usuario_id' => $usuario->id,
                    'lectura_id' => $lectura->id,
                    'completado' => false,
                    'intentos' => 0,
                    'fecha_inicio' => now(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $guardarEnBD ? 'Lectura personalizada creada, guardada y asignada exitosamente con preguntas' : 'Lectura personalizada generada exitosamente',
                'data' => [
                    'lectura' => $lectura,
                    'preguntas' => $preguntas
                ]
            ], $guardarEnBD ? 201 : 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generando lectura personalizada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sugerenciasParaEstudiante($usuario_id)
    {
        try {
            $usuarioActual = auth('api')->user();
            
            // Verificar permisos: solo el propio usuario o admin pueden ver sugerencias
            if ($usuarioActual->id != $usuario_id && $usuarioActual->rol_id !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver las sugerencias de este usuario'
                ], 403);
            }
            
            // Verificar que el usuario existe
            $usuario = \App\Models\Usuario::find($usuario_id);
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }
            
            // Analizar historial y generar sugerencias
            $sugerencias = $this->analizarYSugerir($usuario_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Sugerencias obtenidas exitosamente',
                'data' => ['sugerencias' => $sugerencias]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo sugerencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function obtenerPerfilEstudiante($usuario_id)
    {
        // Obtener datos del estudiante para personalizar la lectura
        $usuario = \App\Models\Usuario::find($usuario_id);
        
        // Obtener historial de lecturas
        $lecturasAnteriores = \App\Models\UsuariosLectura::where('usuario_id', $usuario_id)
            ->with('lectura')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Calcular nivel promedio
        $nivelPromedio = $lecturasAnteriores->avg('lectura.nivel_dificultad') ?? 1;
        
        return [
            'usuario' => $usuario,
            'nivel_promedio' => $nivelPromedio,
            'lecturas_anteriores' => $lecturasAnteriores,
            'edad_estimada' => $this->calcularEdad($usuario->fecha_nacimiento ?? null)
        ];
    }

    private function analizarYSugerir($usuario_id)
    {
        $perfil = $this->obtenerPerfilEstudiante($usuario_id);
        
        // Temas sugeridos basados en el historial
        $temasSugeridos = [
            'aventuras', 'animales', 'ciencia', 'fantasia', 
            'amistad', 'familia', 'naturaleza', 'deportes'
        ];
        
        return [
            'temas_recomendados' => $temasSugeridos,
            'nivel_sugerido' => $perfil['nivel_promedio'],
            'tipos_preferidos' => ['cuento', 'informativo'],
            'longitud_recomendada' => $perfil['nivel_promedio'] > 3 ? 'larga' : 'media'
        ];
    }

    private function construirPrompt($parametros)
    {
        $nivel = $parametros['nivel_dificultad'] ?? 1;
        $tema = $parametros['tema'] ?? 'aventura';
        $tipo = $parametros['tipo'] ?? 'cuento';
        $longitud = $this->normalizeLongitudLectura($parametros['longitud'] ?? 'media');
        
        $vocabulario = [
            1 => 'muy simple, palabras básicas',
            2 => 'simple, vocabulario cotidiano',
            3 => 'intermedio, algunas palabras nuevas',
            4 => 'avanzado, vocabulario rico',
            5 => 'muy avanzado, vocabulario complejo'
        ];
        
        $longitudPalabras = [
            'corta' => '120-180 palabras',
            'media' => '220-350 palabras',
            'larga' => '400-550 palabras'
        ];
        
    return "Crea un {$tipo} educativo para niños sobre '{$tema}' con las siguientes características:
        
- Nivel de dificultad: {$nivel}/5 (usar vocabulario " . ($vocabulario[$nivel] ?? $vocabulario[1]) . ")
- Longitud: " . ($longitudPalabras[$longitud] ?? $longitudPalabras['media']) . "
- Debe ser educativo y apropiado para niños
- Incluir una moraleja o enseñanza
- Formato: Título en la primera línea, seguido del contenido
- El contenido debe ser conciso, claro y fácil de entender. No agregues explicaciones adicionales ni notas.";
    }

    private function generarLecturaConIA($parametros)
    {
        try {
            $prompt = $this->construirPrompt($parametros);

            $titulo = null;
            $contenido = null;

            if ($this->gemini->isConfigured()) {
                // Intentar con Gemini si está configurado
                $texto = $this->gemini->generateText($prompt, [
                    'temperature' => 0.8,
                ]);

                // Parsear salida esperada: TÍTULO: ... / CONTENIDO: ...
                $matches = [];
                if (preg_match('/T[ÍI]TULO\s*:\s*(.+)/u', $texto, $m1)) {
                    $titulo = trim($m1[1]);
                }
                if (preg_match('/CONTENIDO\s*:\s*([\s\S]+)/u', $texto, $m2)) {
                    $contenido = trim($m2[1]);
                }

                if (!$titulo || !$contenido) {
                    // Fallback: primera línea como título, resto contenido
                    $lines = preg_split("/\r?\n/", trim($texto));
                    $titulo = $titulo ?: trim($lines[0] ?? 'Lectura generada');
                    $contenido = $contenido ?: trim(implode("\n", array_slice($lines, 1)));
                }
            }

            if (!$titulo || !$contenido) {
                // Fallback a simulación local si Gemini no está disponible o falló el parseo
                $lecturaSimulada = $this->simularRespuestaIA($parametros);
                $titulo = $lecturaSimulada['titulo'];
                $contenido = $lecturaSimulada['contenido'];
            }

            $rangoId = $this->obtenerRangoEdadId($parametros['usuario_id'] ?? null, $parametros['nivel_dificultad'] ?? null);

            return [
                // Campos válidos según modelo Lectura
                'titulo' => $titulo,
                'contenido' => $contenido,
                'nivel_dificultad_id' => (int) ($parametros['nivel_dificultad'] ?? 1),
                'rango_edad_id' => $rangoId,
                'generada_por_ia' => true,
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Error generando lectura: ' . $e->getMessage());
        }
    }

    private function simularRespuestaIA($parametros)
    {
        // Simulación temporal - reemplazar con llamada real a IA
        $tema = $parametros['tema'] ?? 'aventura';
        $nivel = $parametros['nivel_dificultad'] ?? 1;
        
        $cuentos = [
            'aventura' => [
                'titulo' => 'La Gran Aventura de Luna',
                'contenido' => 'Luna era una niña muy curiosa que vivía en un pequeño pueblo. Un día encontró un mapa misterioso en el desván de su casa. El mapa mostraba el camino hacia un tesoro escondido en el bosque cercano. Luna decidió seguir el mapa y vivir su propia aventura. En el camino conoció a varios animales que la ayudaron: un conejo sabio, una ardilla traviesa y un búho muy inteligente. Cada uno le enseñó algo importante sobre la amistad y la valentía. Al final, Luna descubrió que el verdadero tesoro no era el oro, sino los amigos que había hecho en el camino y todo lo que había aprendido sobre sí misma.'
            ],
            'animales' => [
                'titulo' => 'El Elefante Que Quería Volar',
                'contenido' => 'Había una vez un elefante llamado Tomás que tenía un sueño muy especial: quería volar como los pájaros. Todos los días miraba al cielo y suspiraba profundamente. Los otros animales se reían de él, pero Tomás no se rendía. Un día, una sabia lechuza le dijo que aunque no podía volar con alas, sí podía "volar" de otras maneras. Tomás aprendió a saltar muy alto, a correr muy rápido, y descubrió que cuando ayudaba a otros animales, su corazón "volaba" de felicidad. La moraleja es que todos tenemos talentos especiales, solo debemos encontrar la manera correcta de usarlos.'
            ]
        ];
        
        $cuentoElegido = $cuentos[$tema] ?? $cuentos['aventura'];
        
        return $cuentoElegido;
    }

    private function calcularEdad($fechaNacimiento)
    {
        if (!$fechaNacimiento) return 8; // edad por defecto
        
        return \Carbon\Carbon::parse($fechaNacimiento)->age;
    }

    // Normaliza el tipo de lectura a uno de: cuento, informativo, poetico
    private function normalizeTipoLectura($tipo): string
    {
        if (!$tipo) return 'cuento';
        $t = strtolower(trim($tipo));
        $map = [
            'cuento' => 'cuento', 'historia' => 'cuento', 'narrativo' => 'cuento', 'narrativa' => 'cuento',
            'informativo' => 'informativo', 'informativa' => 'informativo', 'expositivo' => 'informativo',
            'poetico' => 'poetico', 'poético' => 'poetico', 'poesia' => 'poetico', 'poema' => 'poetico',
            // valores erróneos comunes desde UI que no aplican a lectura; caen a 'cuento'
            'opcion_multiple' => 'cuento', 'multiple_choice' => 'cuento', 'preguntas' => 'cuento', 'quiz' => 'cuento',
        ];
        return $map[$t] ?? 'cuento';
    }

    // Normaliza la longitud a una de: corta, media, larga
    private function normalizeLongitudLectura($longitud): string
    {
        if (!$longitud) return 'media';
        $t = strtolower(trim($longitud));
        $map = [
            'muy corta' => 'corta', 'corta' => 'corta', 'corto' => 'corta', 'short' => 'corta', 'pequena' => 'corta', 'pequeña' => 'corta',
            'media' => 'media', 'mediana' => 'media', 'medio' => 'media', 'normal' => 'media',
            'muy larga' => 'larga', 'larga' => 'larga', 'largo' => 'larga', 'long' => 'larga', 'grande' => 'larga',
        ];
        return $map[$t] ?? 'media';
    }

    /**
     * Determina el rango_edad_id basado en la edad del usuario (si está disponible)
     * o como fallback en el nivel de dificultad y el orden de rangos existentes.
     */
    private function obtenerRangoEdadId(?int $usuarioId, ?int $nivelDificultad): int
    {
        // 1) Intentar mapear por edad del usuario
        if ($usuarioId) {
            $usuario = \App\Models\Usuario::find($usuarioId);
            if ($usuario && isset($usuario->edad) && is_numeric($usuario->edad)) {
                $rango = \App\Models\RangosEdad::where('edad_min', '<=', (int)$usuario->edad)
                    ->where('edad_max', '>=', (int)$usuario->edad)
                    ->value('id');
                if ($rango) {
                    return (int)$rango;
                }
            }
        }

        // 2) Fallback por nivel de dificultad: elegir por orden de edad_min
        $rangosIds = \App\Models\RangosEdad::orderBy('edad_min')->pluck('id')->all();
        if (!empty($rangosIds)) {
            $niv = (int)($nivelDificultad ?? 1);
            if ($niv < 1) { $niv = 1; }
            $idx = min($niv - 1, count($rangosIds) - 1);
            return (int)$rangosIds[$idx];
        }

        // 3) Último recurso: un id cualquiera o 1
        $any = \App\Models\RangosEdad::value('id');
        return (int)($any ?? 1);
    }

    // Convierte un valor de request a booleano de forma robusta (soporta "true", "false", 1, 0, "1", "0", "on", "off", "yes", "no")
    private function boolValue($value, bool $default = true): bool
    {
        if ($value === null) return $default;
        if (is_bool($value)) return $value;
        $map = [
            '1' => true, '0' => false,
            1 => true, 0 => false,
            'true' => true, 'false' => false,
            'on' => true, 'off' => false,
            'yes' => true, 'no' => false,
            'si' => true, 'sí' => true, 'no' => false,
        ];
        $key = is_string($value) ? strtolower(trim($value)) : $value;
        return array_key_exists($key, $map) ? (bool)$map[$key] : ($this->filterBool($value, $default));
    }

    private function filterBool($value, bool $default = true): bool
    {
        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered === null ? $default : $filtered;
    }

    private function boolFromRequest(\Illuminate\Http\Request $request, string $key, bool $default = true): bool
    {
        return $this->boolValue($request->input($key, null), $default);
    }

    private function construirPromptTodo($parametros)
    {
        $nivel = $parametros['nivel_dificultad'] ?? 1;
        $tema = $parametros['tema'] ?? 'aventura';
        $tipo = $parametros['tipo'] ?? 'cuento';
        $longitud = $this->normalizeLongitudLectura($parametros['longitud'] ?? 'media');
        $numPreguntas = max(3, min($this->calcularNumeroPreguntas($nivel), 5));

        $longitudPalabras = [
            'corta' => '120-180 palabras',
            'media' => '220-350 palabras',
            'larga' => '400-550 palabras'
        ];

    return "Genera en ESPAÑOL un JSON con UNA lectura {$tipo} para niños sobre '{$tema}' y {$numPreguntas} preguntas multiple_choice. RESPONDE SOLO con JSON válido, sin markdown ni comentarios.

Requisitos lectura:
- Nivel de dificultad {$nivel}/5.
- Longitud: " . ($longitudPalabras[$longitud] ?? $longitudPalabras['media']) . " (sé conciso para ahorrar tokens).
- Educativa, apropiada para niños, con moraleja.
- Campos: titulo, contenido.

Requisitos preguntas:
- {$numPreguntas} preguntas tipo multiple_choice.
- 4 opciones por pregunta, exactamente una correcta.
- Opciones breves (<= 120 caracteres).
- Cubre literal, inferencial y crítica.

Formato de salida EXACTO:
{
  \"lectura\": {
    \"titulo\": \"...\",
    \"contenido\": \"...\"
  },
  \"preguntas\": [
    {
      \"texto_pregunta\": \"...\",
      \"tipo_pregunta\": \"multiple_choice\",
      \"respuestas\": [
        {\"texto\": \"...\", \"es_correcta\": true},
        {\"texto\": \"...\", \"es_correcta\": false},
        {\"texto\": \"...\", \"es_correcta\": false},
        {\"texto\": \"...\", \"es_correcta\": false}
      ]
    }
  ]
}";
    }

    private function generarTodoConIA(array $parametros): array
    {
        if (!$this->gemini->isConfigured()) {
            return [];
        }

        try {
            $prompt = $this->construirPromptTodo($parametros);
            $schema = [
                'type' => 'object',
                'properties' => [
                    'lectura' => [
                        'type' => 'object',
                        'properties' => [
                            'titulo' => ['type' => 'string'],
                            'contenido' => ['type' => 'string']
                        ],
                        'required' => ['titulo', 'contenido']
                    ],
                    'preguntas' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'texto_pregunta' => ['type' => 'string'],
                                'tipo_pregunta' => ['type' => 'string'],
                                'respuestas' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'texto' => ['type' => 'string'],
                                            'es_correcta' => ['type' => 'boolean']
                                        ],
                                        'required' => ['texto', 'es_correcta']
                                    ],
                                    'minItems' => 4,
                                    'maxItems' => 4
                                ]
                            ],
                            'required' => ['texto_pregunta', 'respuestas']
                        ]
                    ]
                ],
                'required' => ['lectura', 'preguntas']
            ];

            $json = $this->gemini->generateJson($prompt, $schema);

            if (!is_array($json) || empty($json['lectura'])) {
                return [];
            }

            $titulo = trim((string) ($json['lectura']['titulo'] ?? 'Lectura generada'));
            $contenido = trim((string) ($json['lectura']['contenido'] ?? ''));

            if ($titulo === '' || $contenido === '') {
                return [];
            }

            $rangoId = $this->obtenerRangoEdadId($parametros['usuario_id'] ?? null, $parametros['nivel_dificultad'] ?? null);
            $lectura = [
                'titulo' => $titulo,
                'contenido' => $contenido,
                'nivel_dificultad_id' => (int) ($parametros['nivel_dificultad'] ?? 1),
                'rango_edad_id' => $rangoId,
                'generada_por_ia' => true,
            ];

            // Normalizar preguntas
            $preguntas = [];
            foreach (($json['preguntas'] ?? []) as $item) {
                if (!isset($item['texto_pregunta']) || !isset($item['respuestas']) || !is_array($item['respuestas'])) {
                    continue;
                }
                // Garantizar una correcta
                $hasCorrect = false;
                foreach ($item['respuestas'] as $r) {
                    if (!empty($r['es_correcta'])) { $hasCorrect = true; break; }
                }
                if (!$hasCorrect && isset($item['respuestas'][0])) {
                    $item['respuestas'][0]['es_correcta'] = true;
                }
                $preguntas[] = [
                    'texto_pregunta' => (string) $item['texto_pregunta'],
                    'tipo_pregunta' => $item['tipo_pregunta'] ?? 'multiple_choice',
                    'respuestas' => array_map(function ($r) {
                        return [
                            'texto' => (string) ($r['texto'] ?? ''),
                            'es_correcta' => (bool) ($r['es_correcta'] ?? false),
                        ];
                    }, $item['respuestas'])
                ];
            }

            return ['lectura' => $lectura, 'preguntas' => $preguntas];
        } catch (\Throwable $e) {
            \Log::warning('Fallo generación unificada con Gemini: ' . $e->getMessage());
            return [];
        }
    }

    private function persistirPreguntas(Lectura $lectura, array $preguntas): array
    {
        $guardadas = [];
        foreach ($preguntas as $index => $p) {
            if (!isset($p['texto_pregunta']) || !isset($p['respuestas']) || !is_array($p['respuestas'])) {
                continue;
            }
            $pregunta = \App\Models\PreguntasLectura::create([
                'lectura_id' => $lectura->id,
                'texto' => $p['texto_pregunta'],
                'tipo' => $p['tipo_pregunta'] ?? 'opcion_multiple',
                'orden' => $index + 1,
            ]);

            $respuestasCreadas = [];
            foreach ($p['respuestas'] as $idx => $r) {
                $respuesta = \App\Models\RespuestasLectura::create([
                    'pregunta_id' => $pregunta->id,
                    'texto' => (string) ($r['texto'] ?? ''),
                    'es_correcta' => (bool) ($r['es_correcta'] ?? false),
                    'orden' => $idx + 1,
                ]);
                $respuestasCreadas[] = $respuesta;
            }
            $pregunta->respuestas = $respuestasCreadas;
            $guardadas[] = $pregunta;
        }
        return $guardadas;
    }

    private function generarPreguntasConIA($lectura, $parametros)
    {
        try {
            $preguntasGeneradas = $this->crearPreguntasAutomaticas($lectura, $parametros);
            $preguntasGuardadas = [];
            
            foreach ($preguntasGeneradas as $index => $preguntaData) {
                // Crear la pregunta
                $pregunta = \App\Models\PreguntasLectura::create([
                    'lectura_id' => $lectura->id,
                    'texto' => $preguntaData['texto_pregunta'],
                    'tipo' => $preguntaData['tipo_pregunta'] ?? 'opcion_multiple',
                    'orden' => $index + 1,
                ]);
                
                // Crear las respuestas para la pregunta
                $respuestasCreadas = [];
                foreach ($preguntaData['respuestas'] as $respuestaIndex => $respuestaData) {
                    $respuesta = \App\Models\RespuestasLectura::create([
                        'pregunta_id' => $pregunta->id,
                        'texto' => (string) ($respuestaData['texto'] ?? ''),
                        'es_correcta' => (bool) ($respuestaData['es_correcta'] ?? false),
                        'orden' => $respuestaIndex + 1,
                ]);
                    $respuestasCreadas[] = $respuesta;
                }
                
                $pregunta->respuestas = $respuestasCreadas;
                $preguntasGuardadas[] = $pregunta;
            }
            
            return $preguntasGuardadas;
            
        } catch (\Exception $e) {
            // Si falla la generación de preguntas, al menos la lectura ya está creada
            \Log::error('Error generando preguntas automáticas: ' . $e->getMessage());
            return [];
        }
    }

    private function crearPreguntasAutomaticas($lectura, $parametros)
    {
        // Intentar generar preguntas con Gemini si está disponible
        $prompt = $this->construirPromptPreguntas($lectura, $parametros);

        if ($this->gemini->isConfigured()) {
            try {
                $schema = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'texto_pregunta' => ['type' => 'string'],
                            'tipo_pregunta' => ['type' => 'string'],
                            'respuestas' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'texto' => ['type' => 'string'],
                                        'es_correcta' => ['type' => 'boolean']
                                    ],
                                    'required' => ['texto', 'es_correcta']
                                ],
                                'minItems' => 4,
                                'maxItems' => 4
                            ]
                        ],
                        'required' => ['texto_pregunta', 'respuestas']
                    ]
                ];
                $json = $this->gemini->generateJson($prompt, $schema);
                if (is_array($json) && count($json) > 0) {
                    // Validar estructura mínima
                    $preguntas = [];
                    foreach ($json as $item) {
                        if (!isset($item['texto_pregunta']) || !isset($item['respuestas']) || !is_array($item['respuestas'])) {
                            continue;
                        }
                        // Asegurar al menos una correcta
                        $hasCorrect = false;
                        foreach ($item['respuestas'] as $r) {
                            if (!empty($r['es_correcta'])) { $hasCorrect = true; break; }
                        }
                        if (!$hasCorrect) {
                            // marcar la primera como correcta si ninguna lo es
                            if (isset($item['respuestas'][0])) {
                                $item['respuestas'][0]['es_correcta'] = true;
                            }
                        }
                        $preguntas[] = [
                            'texto_pregunta' => (string) $item['texto_pregunta'],
                            'tipo_pregunta' => $item['tipo_pregunta'] ?? 'multiple_choice',
                            'respuestas' => array_map(function ($r) {
                                return [
                                    'texto' => (string) ($r['texto'] ?? ''),
                                    'es_correcta' => (bool) ($r['es_correcta'] ?? false),
                                ];
                            }, $item['respuestas'])
                        ];
                    }
                    if (count($preguntas) > 0) {
                        return $preguntas;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Fallo generación de preguntas con Gemini, usando simulación. Error: ' . $e->getMessage());
            }
        }

        // Fallback a simulación local
        return $this->simularPreguntasIA($lectura, $parametros);
    }

    private function construirPromptPreguntas($lectura, $parametros)
    {
        $nivel = $parametros['nivel_dificultad'] ?? 1;
        $numPreguntas = max(3, min($this->calcularNumeroPreguntas($nivel), 5));
        
        return "Basándote en esta lectura, crea {$numPreguntas} preguntas de comprensión lectora para nivel {$nivel}/5, en español, y responde SOLO con JSON válido sin comentarios ni texto adicional.

LECTURA:
Título: {$lectura->titulo}
Contenido: {$lectura->contenido}

Requisitos:
- Preguntas tipo multiple_choice con 4 opciones.
- Exactamente UNA correcta por pregunta.
- Textos breves (<= 120 caracteres por opción).
- Cubre literal, inferencial y crítica.

Formato de salida (JSON):
[
  {
    \"texto_pregunta\": \"¿Cuál era el nombre del personaje principal?\",
    \"tipo_pregunta\": \"multiple_choice\",
    \"respuestas\": [
      {\"texto\": \"Respuesta correcta\", \"es_correcta\": true},
      {\"texto\": \"Respuesta incorrecta 1\", \"es_correcta\": false},
      {\"texto\": \"Respuesta incorrecta 2\", \"es_correcta\": false},
      {\"texto\": \"Respuesta incorrecta 3\", \"es_correcta\": false}
    ]
  }
]";
    }

    private function simularPreguntasIA($lectura, $parametros)
    {
        // Simulación temporal - reemplazar con IA real
        $nivel = $parametros['nivel_dificultad'] ?? 1;
        
        $preguntasBase = [
            [
                'texto_pregunta' => '¿Cuál es el tema principal de la lectura?',
                'tipo_pregunta' => 'multiple_choice',
                'respuestas' => [
                    ['texto' => $parametros['tema'] ?? 'Aventura', 'es_correcta' => true],
                    ['texto' => 'Ciencia ficción', 'es_correcta' => false],
                    ['texto' => 'Romance', 'es_correcta' => false],
                    ['texto' => 'Terror', 'es_correcta' => false]
                ]
            ],
            [
                'texto_pregunta' => '¿Qué aprendiste de esta historia?',
                'tipo_pregunta' => 'multiple_choice',
                'respuestas' => [
                    ['texto' => 'La importancia de la amistad y la perseverancia', 'es_correcta' => true],
                    ['texto' => 'Que hay que tener miedo', 'es_correcta' => false],
                    ['texto' => 'Que no hay que intentar cosas nuevas', 'es_correcta' => false],
                    ['texto' => 'Que es mejor estar solo', 'es_correcta' => false]
                ]
            ],
            [
                'texto_pregunta' => '¿Qué personaje ayudó a Luna con sabiduría?',
                'tipo_pregunta' => 'multiple_choice',
                'respuestas' => [
                    ['texto' => 'El búho', 'es_correcta' => true],
                    ['texto' => 'El perro', 'es_correcta' => false],
                    ['texto' => 'El gato', 'es_correcta' => false],
                    ['texto' => 'El pez', 'es_correcta' => false]
                ]
            ],
            [
                'texto_pregunta' => '¿Cuál es la moraleja principal?',
                'tipo_pregunta' => 'multiple_choice',
                'respuestas' => [
                    ['texto' => 'El verdadero tesoro es lo aprendido y las amistades', 'es_correcta' => true],
                    ['texto' => 'El tesoro es el oro', 'es_correcta' => false],
                    ['texto' => 'Siempre hay que ganar', 'es_correcta' => false],
                    ['texto' => 'No hay moraleja', 'es_correcta' => false]
                ]
            ]
        ];
        
        // Ajustar número de preguntas según el nivel
        $numeroPreguntas = $this->calcularNumeroPreguntas($nivel);
        
        return array_slice($preguntasBase, 0, $numeroPreguntas);
    }

    private function calcularNumeroPreguntas($nivel)
    {
        // Más preguntas para niveles más altos (limitamos a 5 para ahorrar tokens)
        return min(2 + $nivel, 5); // Entre 3 y 5 preguntas
    }

    /**
     * API para obtener preguntas de una lectura específica
     */
    public function getPreguntasLectura($lectura_id)
    {
        try {
            $lectura = \App\Models\Lectura::find($lectura_id);
            
            if (!$lectura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lectura no encontrada'
                ], 404);
            }
            
            $preguntas = \App\Models\PreguntasLectura::where('lectura_id', $lectura_id)
                ->with('respuestas')
                ->orderBy('orden')
                ->get();
            
            // Transformar para que el frontend vea los campos esperados
            $preguntasFormateadas = $preguntas->map(function ($p) {
                return [
                    'id' => $p->id,
                    'lectura_id' => $p->lectura_id,
                    'texto_pregunta' => $p->texto,
                    'tipo_pregunta' => $p->tipo,
                    'orden' => $p->orden,
                    'respuestas' => $p->respuestas
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'lectura' => $lectura,
                    'preguntas' => $preguntasFormateadas
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo preguntas de la lectura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para responder preguntas y evaluar comprensión
     */
    public function evaluarRespuestas(Request $request, $lectura_id)
    {
        try {
            $this->validate($request, [
                'respuestas' => 'required|array',
                'respuestas.*.pregunta_id' => 'required|integer',
                'respuestas.*.respuesta_id' => 'required|integer',
                'tiempo_total_segundos' => 'nullable|integer'
            ]);
            
            $usuario = auth('api')->user();
            $respuestasUsuario = $request->input('respuestas');
            
            $correctas = 0;
            $total = count($respuestasUsuario);
            $detalles = [];
            
            foreach ($respuestasUsuario as $respuestaData) {
                $respuesta = \App\Models\RespuestasLectura::find($respuestaData['respuesta_id']);
                
                if ($respuesta && $respuesta->es_correcta) {
                    $correctas++;
                }
                
                $detalles[] = [
                    'pregunta_id' => $respuestaData['pregunta_id'],
                    'respuesta_id' => $respuestaData['respuesta_id'],
                    'es_correcta' => $respuesta ? $respuesta->es_correcta : false
                ];
                
                // Opcional: guardar resultado en otra tabla si aplica. Omitido por diferencias de esquema actuales.
            }
            
            $puntuacion = $total > 0 ? round(($correctas / $total) * 100, 2) : 0;
            
            // Actualizar progreso en UsuariosLectura
            $usuarioLectura = \App\Models\UsuariosLectura::where('usuario_id', $usuario->id)
                ->where('lectura_id', $lectura_id)
                ->first();
            
            if ($usuarioLectura) {
                $usuarioLectura->update([
                    'completado' => true,
                    'puntuacion' => $puntuacion,
                    'tiempo_lectura_segundos' => $request->input('tiempo_total_segundos'),
                    'fecha_completada' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluación completada',
                'data' => [
                    'puntuacion' => $puntuacion,
                    'correctas' => $correctas,
                    'total' => $total,
                    'detalles' => $detalles,
                    'aprobado' => $puntuacion >= 70 // 70% para aprobar
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error evaluando respuestas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}