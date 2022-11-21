<?php

namespace App\DAL;

use App\Models\Trayectoria;
use App\Models\GeneracionFactura;
use App\Models\GeneracionLectura;
use App\Models\GeneracionLecturaMovil;
use App\Models\ModificacionGeneracionLectura;

class SincronizarDAL
{
    public function Get_Trayectoria($Plomero, $DataBaseAlias){
        $loTrayectoria = Trayectoria::on($DataBaseAlias)
            ->select('TRAYECTORIA.*')
            ->join('GENERACIONFACTURA', 'TRAYECTORIA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loTrayectoria;
    }

    public function Get_GeneracionFactura($Plomero, $DataBaseAlias){
        $loGeneracionFactura = GeneracionFactura::on($DataBaseAlias)
            ->where('Cobro', '=', "2022-10") //date('Y-m'))
            ->where('Plomero', '=', $Plomero)
            ->where('Generado', '=', 0)
            ->get();
    
        return $loGeneracionFactura;
    }

    public function Get_GeneracionLectura($Plomero, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
            ->select('GENERACIONLECTURA.*')
            ->join('GENERACIONFACTURA', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->where(function($query){
                $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
            })
            ->get();
    
        return $loGeneracionLectura;
    }

    public function Get_GeneracionLecturaMovil($Plomero, $DataBaseAlias){
        $loGeneracionLecturaMovil = GeneracionLecturaMovil::on($DataBaseAlias)
            ->select('GENERACIONLECTURAMOVIL.*')
            ->join('GENERACIONFACTURA', 'GENERACIONLECTURAMOVIL.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loGeneracionLecturaMovil;
    }

    public function Get_ModificacionGeneracionLectura($Plomero, $DataBaseAlias){
        $loModificacionGeneracionLectura = ModificacionGeneracionLectura::on($DataBaseAlias)
            ->select('MODIFICACIONGENERACIONLECTURA.*')
            ->join('GENERACIONFACTURA', 'MODIFICACIONGENERACIONLECTURA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loModificacionGeneracionLectura;
    }
}