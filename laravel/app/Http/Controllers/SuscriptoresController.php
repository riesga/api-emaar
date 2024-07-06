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
                        DB::raw('substr(idsuscriptor, 6, 15) as IdSuscriptor'),
                        DB::raw("cm_suscriptor.saldopdteaseo AS SaldoActual"),
                        DB::raw("cm_suscriptor.nombre || ' ' || cm_suscriptor.apellido AS Nombre"),
                    )->first());

                if ($suscr->isEmpty()) {
                    //return array(['error' => 'No se encontró información con los datos ingresados. Verifique la matrícula del suscriptor e intente nuevamente!']);
                    return array(
                        [
                            "Estado" => 0,
                            "DescripcionEstado" => "No se encontró información con los datos ingresados. Verifique la matrícula del suscriptor e intente nuevamente!",
                        ]
                    );
                } else {
                    $cuentacobro = collect(Cm_factura::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                        ->whereRaw("idcuentacobro IN (SELECT max(idcuentacobro) FROM cm_factura WHERE idsuscriptor=" . Cm_suscriptor::PREFIX . $id . ")")
                        ->select(DB::raw('substr(idcuentacobro, 6, 15) as idcuentacobro'))
                        ->first());

                    $SaldoAnterior = collect(Cm_cuentacobro::where('idsuscriptor', Cm_suscriptor::PREFIX . $id)
                        ->where('idcuentacobro', '<', Cm_suscriptor::PREFIX . $cuentacobro['idcuentacobro'])
                        ->where('saldopendientefactura', '<>', 0)
                        ->select(DB::raw('sum(saldopendientefactura) as SaldoAnterior'))
                        ->first());

                    if ($SaldoAnterior["saldoanterior"] == null) {
                        $SaldoAnterior["saldoanterior"] = 0;
                    }

                    $merged = $suscr;
                    $merged = $merged->merge($SaldoAnterior);

                    $respuesta = array(
                        'Estado' => 1,
                        'DescripcionEstado' => 'Consulta Exitosa',
                        'DatosSuscriptor' => [
                            "IdSuscriptor" => $id,
                            "SaldoAnterior" => $SaldoAnterior["saldoanterior"],
                            "SaldoActual" => $suscr["saldoactual"],
                            "Nombre" => $suscr["nombre"]
                        ]
                    );


                    return $respuesta;
                }
            } else {
                return array(
                    [
                        "Estado" => 0,
                        "DescripcionEstado" => "Actualmente nos encontramos en proceso de facturación. No es posible realizar la transacción en este momento!",
                    ]
                );
            }
        } else {
            return array(
                [
                    "Estado" => 0,
                    "DescripcionEstado" => "El dato ingresado no es un suscriptor válido!",
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
                'IdSuscriptor' => 'required|integer|min:1',
                'Valor' => 'required|integer|min:1',
            ], $messages);

            $cuentacobro = collect(Cm_factura::where('idsuscriptor', Cm_suscriptor::PREFIX.$request->IdSuscriptor)
            ->whereRaw("idcuentacobro IN (SELECT max(idcuentacobro) FROM cm_factura WHERE idsuscriptor=" . Cm_suscriptor::PREFIX.$request->IdSuscriptor . ")")
            ->select(DB::raw('substr(idcuentacobro, 6, 15) as idcuentacobro'))
            ->first());


            if ($cuentacobro) {

                $maxRetries = 3;
                $attempt = 0;

                while ($attempt < $maxRetries) {

                    try {
                        $respuesta = DB::connection('oracle')->transaction(function () use ($request) {
                            // Bloquear la fila con el valor del consecutivo
                            $secuencia = DB::connection('oracle')->table('cs_secuencia')->where('IDSECUENCIA', 'CMCUPA_IDCUPONPAGO')->lockForUpdate()->first();

                            $idcuentacobro = DB::connection('oracle')->table('cm_factura')->where('idsuscriptor', Cm_suscriptor::PREFIX.$request->IdSuscriptor)->whereRaw("idcuentacobro IN (SELECT max(idcuentacobro) FROM cm_factura WHERE idsuscriptor=" . Cm_suscriptor::PREFIX.$request->IdSuscriptor . ")")
                            ->select(DB::raw('substr(idcuentacobro, 6, 15) as idcuentacobro'))
                            ->first();


                            // Obtener el valor del consecutivo
                            $consecutivo = $secuencia->proximonumero;

                            // Insertar la nueva factura con el consecutivo obtenido
                            DB::connection('oracle')->table('cm_cuponpago')->insert([
                                'idcuponpago' => Cm_suscriptor::PREFIX . $consecutivo,
                                'valor' => $request->Valor,
                                'fechageneracion' => date('Y-m-d'),
                                'ditipocuponpago' => 2,
                                'idestadocupon' => 1,
                                'idcuentacobro' => $idcuentacobro->idcuentacobro,
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
                                'DescripcionEstado' => 'Generación Exitosa',
                                'DatosCupon' => [
                                    'IdSuscriptor' => $request->input('IdSuscriptor'),
                                    'Id' => $consecutivo,
                                    'Fecha' => date('Y-m-d H:i:s'),
                                    'Valor' => intval($request->input('Valor')),
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
                return response()->json(['message' => 'El suscriptor no tiene facturas.'], 404);
            }
        } catch (ValidationException $th) {
            return response()->json($th->validator->errors(), 422);
        }
    }

}
