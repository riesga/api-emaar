<?php

namespace App\Http\Controllers;

use App\Models\Cm_cargos;
use App\Models\Cm_cuentacobro;
use App\Models\Cm_cuponpago;
use App\Models\Cm_factura;
use App\Models\Cm_suscriptor;
use App\Models\Cs_secuencia;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuscriptoresController extends Controller
{
    public function consultarSaldo($id)
    {
        if (is_numeric($id)) {
            $validaFacturacion = Cm_cuentacobro::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                ->whereIn('idciclofacturacion', [DB::raw("SELECT idciclofacturacion from cm_vigencia where bloqueomovimiento='S'")])
                ->select('idcuentacobro')
                ->get();

            if ($validaFacturacion != "") {

                $suscr = collect(Cm_suscriptor::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                    ->select(
                        DB::raw('substr(idsuscriptor, 6, 15) as idsuscriptor'),
                        DB::raw("cm_suscriptor.nombre || ' ' || cm_suscriptor.apellido AS nombre"),
                        "cm_suscriptor.facturasconsaldoaseo",
                        DB::raw("cm_suscriptor.saldopdteaseo AS SaldoPendienteAseo"),
                        DB::raw("cm_suscriptor.saldopdteacueducto+cm_suscriptor.saldopdtealcantarillado+cm_suscriptor.saldopdteaseo AS SaldoPendiente")
                    )->first());

                if ($suscr->isEmpty()) {
                    //return array(['error' => 'No se encontró información con los datos ingresados. Verifique la matrícula del suscriptor e intente nuevamente!']);
                    return array(
                        [
                            "estado" => 0,
                            "descripcionestado" => "No se encontró información con los datos ingresados. Verifique la matrícula del suscriptor e intente nuevamente!",
                        ]
                    );
                } else {
                    $cuentacobro = collect(Cm_factura::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                        ->whereRaw("idcuentacobro IN (SELECT max(idcuentacobro) FROM cm_factura WHERE idsuscriptor=" . Cm_suscriptor::PREFIX . $id . ")")
                        ->select(DB::raw('substr(idcuentacobro, 6, 15) as idcuentacobro'), 'idempresaaseo')
                        ->first());

                    $saldoanterior = collect(Cm_cuentacobro::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                        ->where('idcuentacobro', '<', Cm_suscriptor::PREFIX . $cuentacobro['idcuentacobro'])
                        ->where('saldopendientefactura', '<>', 0)
                        ->select(DB::raw('sum(saldopendientefactura) as saldoanterior'))
                        ->first());



                    $merged = $suscr->merge($cuentacobro);
                    $merged = $merged->merge($saldoanterior);
                    $merged = $merged->forget('idempresaaseo');

                    $respuesta = array(
                        'estado' => 1,
                        'descripcionestado' => 'Consulta Exitosa',
                        'DatosSuscriptor' => $merged,
                        "IdEmpresaAseo" => $cuentacobro['idempresaaseo']
                    );


                    return $respuesta;
                }
            } else {
                return array(
                    [
                        "estado" => 0,
                        "descripcionestado" => "Actualmente nos encontramos en proceso de facturación. No es posible realizar la transacción en este momento!",
                    ]
                );
            }
        } else {
            return array(
                [
                    "estado" => 0,
                    "descripcionestado" => "El dato ingresado no es un suscriptor válido!",
                ]
            );
        }
    }

    public function generarAbono(Request $request)
    {

        try {

            $messages = [
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.string' => 'La descripción debe ser una cadena de texto.',
                'descripcion.max' => 'La descripción no puede exceder los 255 caracteres.',
                'suscriptor.required' => 'El suscriptor es obligatorio.',
                'suscriptor.integer' => 'El suscriptor debe ser un número entero.',
                'suscriptor.exists' => 'El suscriptor especificado no existe.',
                'valorcupon.required' => 'El valor del cupón es obligatorio.',
                'valorcupon.integer' => 'El valor del cupón debe ser un número.',
                'valorcupon.min' => 'El valor del cupón debe ser al menos 1 peso.',
            ];

            $validated = $request->validate([
                'suscriptor' => 'required|integer|min:1',
                'cuentacobro' => 'required|integer|min:1',
                'valorcupon' => 'required|integer|min:1',
            ], $messages);

            $cuentacobro = Cm_cuentacobro::find(Cm_suscriptor::PREFIX.$request->cuentacobro); // Attempt to find invoice by ID

            if ($cuentacobro) {

                $maxRetries = 3;
                $attempt = 0;

                while ($attempt < $maxRetries) {

                    try {
                        $respuesta = DB::connection('oracle')->transaction(function () use ($request) {
                            // Bloquear la fila con el valor del consecutivo
                            $secuencia = DB::connection('oracle')->table('cs_secuencia')->where('IDSECUENCIA', 'CMCUPA_IDCUPONPAGO')->lockForUpdate()->first();

                            // Obtener el valor del consecutivo
                            $consecutivo = $secuencia->proximonumero;

                            // Insertar la nueva factura con el consecutivo obtenido
                            DB::connection('oracle')->table('cm_cuponpago')->insert([
                                'idcuponpago' => Cm_suscriptor::PREFIX . $consecutivo,
                                'valor' => $request->valorcupon,
                                'fechageneracion' => date('Y-m-d'),
                                'ditipocuponpago' => 2,
                                'idestadocupon' => 1,
                                'idcuentacobro' => Cm_suscriptor::PREFIX . $request->cuentacobro,
                                'idpais' => 57,
                                'iddepartamento' => 81,
                                'idmunicipio' => 1,
                                'idusuario' => 'SIEWEB',
                                'maquina' => 'API RECAUDO',
                                'idservicio' => 5,
                            ]);

                            DB::connection('oracle')->table('cs_secuencia')->where('IDSECUENCIA', 'CMCUPA_IDCUPONPAGO')->update([
                                'proximonumero' => $consecutivo + 1
                            ]);

                            // Construir la respuesta
                            return [
                                'Estado' => 1,
                                'Descripcionestado' => 'Generación Exitosa',
                                'DatosCupon' => [
                                    'IdSuscriptor' => $request->input('suscriptor'),
                                    'Id' => $consecutivo,
                                    'Fecha' => date('Y-m-d H:i:s'),
                                    'Valor' => $request->input('valorcupon')
                                ],
                            ];
                        });

                        return response()->json($respuesta, 201);

                    } catch (Exception $e) {
                        $attempt++;
                        if ($attempt >= $maxRetries) {
                            return response()->json(['error' => 'Error generando abono, por favor intente de nuevo más tarde'], 500);
                        }
                    }
                }
            } else {
                return response()->json(['message' => 'La factura no existe.'], 404);
            }
        } catch (ValidationException $th) {
            return response()->json($th->validator->errors(), 422);
        }
    }

}