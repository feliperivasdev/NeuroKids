<?php

namespace App\Http\Controllers;

use App\Models\Institucion;
use Illuminate\Http\Request;

class InstitucionController extends Controller
{
    public function __construct()
    {
        // Constructor vacío por ahora
    }

    public function index()
    {
        try {
            $instituciones = Institucion::select('id', 'nombre')
                ->orderBy('nombre', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Instituciones obtenidas correctamente',
                'data' => $instituciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener instituciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $institucion = Institucion::find($id);
        if (!$institucion) {
            return response()->json([
                'success' => false,
                'message' => 'Institución no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Institución obtenida correctamente',
            'data' => $institucion
        ]);
    }

    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',
                'correo_contacto' => 'required|email|max:255',
                'telefono_contacto' => 'required|string|max:50'
            ]);

            $institucion = new Institucion();
            $institucion->nombre = $request->nombre;
            $institucion->direccion = $request->direccion;
            $institucion->correo_contacto = $request->correo_contacto;
            $institucion->telefono_contacto = $request->telefono_contacto;
            $institucion->save();

            return response()->json([
                'success' => true,
                'message' => 'Institución creada correctamente',
                'data' => $institucion
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la institución',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}