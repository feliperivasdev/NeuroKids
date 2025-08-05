<?php

/* inicializar el archivo  */
namespace App\Http\Controllers;

/* hacer las importaciones necesarias */
use App\Models\Institucion;
use Illuminate\Http\Request;

/* crear la clase */
class InstitucionController extends Controller
{
    /* crear el constructor */
    public function __construct()
    {
        // Constructor vacío por ahora
    }

    /* crear el método index */
    public function index()
    {
        return response()->json(Institucion::all());
    }

    /* crear el método show */
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

    /* crear el método store */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'correo_contacto' => 'required|email|max:255',
            'telefono_contacto' => 'required|string|max:50',
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
    }
}

/*Aslly */
