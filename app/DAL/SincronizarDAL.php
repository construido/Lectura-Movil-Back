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
            ->select('TRAYECTORIA.Trayectoria as id_trayect', 'TRAYECTORIA.GeneracionFactura as id_genfact', 'TRAYECTORIA.Plomero as id_plomero', 'TRAYECTORIA.Cliente as id_socio',
                'TRAYECTORIA.Latitud as latitud', 'TRAYECTORIA.Longitud as longitud', 'TRAYECTORIA.Fecha as fecha', 'TRAYECTORIA.Hora as hora', 'TRAYECTORIA.Estado as estadoins')
            ->join('GENERACIONFACTURA', 'TRAYECTORIA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            //->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loTrayectoria;
    }

    public function Get_GeneracionFactura($Plomero, $DataBaseAlias){
        $loGeneracionFactura = GeneracionFactura::on($DataBaseAlias)
            ->select('GeneracionFactura as id_genfact', 'Cobro as cobro', 'Zona as id_zona', 'Ruta as ruta', 'Generado as generado',
                'FechaGeneracionFactura as f_genfact', 'GeneradoGeneracionLectura as g_genlect', 'Plomero as id_plomero', 'PorcentajeLectura as porclect',
                'Mensaje as id_mensaje', 'MontoTotal as mto_total', 'MontoFiscal as mto_fiscal')
            //->where('Cobro', '=', "2022-10") //date('Y-m'))
            ->where('Plomero', '=', $Plomero)
            ->where('Generado', '=', 0)
            ->get();
    
        return $loGeneracionFactura;
    }

    public function Get_GeneracionLectura($Plomero, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
            ->select('GENERACIONLECTURA.GeneracionFactura as id_genfact', 'GENERACIONLECTURA.Cliente as id_socio', 'GENERACIONLECTURA.CodigoUbicacion as cod_socio',
                'GENERACIONLECTURA.LecturaAnterior as lectant', 'GENERACIONLECTURA.LecturaActual as lectact', 'GENERACIONLECTURA.Consumo', 'GENERACIONLECTURA.MedidorAnormalidad as id_mediest',
                'GENERACIONLECTURA.MediaAnterior as media_ant', 'GENERACIONLECTURA.Cobro as cobro', 'GENERACIONLECTURA.Media as media', 'GENERACIONLECTURA.ConsumoFacturado as consumofac',
                'GENERACIONLECTURA.ConsumoDebito as consumodeb', 'GENERACIONLECTURA.Categoria as id_categ', 'GENERACIONLECTURA.Medidor as id_medidor')
            ->join('GENERACIONFACTURA', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            //->where('GENERACIONFACTURA.Cobro', '=', "2022-10")
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
            ->select('GENERACIONLECTURAMOVIL.GeneracionFactura as id_genfact', 'GENERACIONLECTURAMOVIL.Cliente as id_socio', 'GENERACIONLECTURAMOVIL.Fecha as fecha', 'GENERACIONLECTURAMOVIL.Hora as hora',
                'GENERACIONLECTURAMOVIL.Categoria as id_categ', 'GENERACIONLECTURAMOVIL.Medidor as id_medidor', 'GENERACIONLECTURAMOVIL.TipoConsumo as tipoconsum', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2 as id_medies2',
                'GENERACIONLECTURAMOVIL.AplicacionConMedidor as aplicomedi', 'GENERACIONLECTURAMOVIL.ConsumoFactura as consufactu', 'GENERACIONLECTURAMOVIL.AjusteConsumo as ajustecons',
                'GENERACIONLECTURAMOVIL.AjusteMonto as ajustemont', 'GENERACIONLECTURAMOVIL.DesviacionSignificativa as desvsigni', 'GENERACIONLECTURAMOVIL.InspeccionRequerido as insperequ',
                'GENERACIONLECTURAMOVIL.Facturado as facturado', 'GENERACIONLECTURAMOVIL.ValidoLectura as vallectura')
            ->join('GENERACIONFACTURA', 'GENERACIONLECTURAMOVIL.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            //->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loGeneracionLecturaMovil;
    }

    public function Get_ModificacionGeneracionLectura($Plomero, $DataBaseAlias){
        $loModificacionGeneracionLectura = ModificacionGeneracionLectura::on($DataBaseAlias)
            ->select('MODIFICACIONGENERACIONLECTURA.GeneracionFactura as id_genfact', 'MODIFICACIONGENERACIONLECTURA.Cliente as id_socio', 'MODIFICACIONGENERACIONLECTURA.Usr as id_usr',
                'MODIFICACIONGENERACIONLECTURA.Glosa as glosa', 'MODIFICACIONGENERACIONLECTURA.Fecha as fecha', 'MODIFICACIONGENERACIONLECTURA.Hora as hora', 'MODIFICACIONGENERACIONLECTURA.Cobro as cobro',
                'MODIFICACIONGENERACIONLECTURA.CodigoUbicacion as cod_socio', 'MODIFICACIONGENERACIONLECTURA.LecturaAnterior as lectant', 'MODIFICACIONGENERACIONLECTURA.LecturaActual as lectact',
                'MODIFICACIONGENERACIONLECTURA.Consumo as consumo', 'MODIFICACIONGENERACIONLECTURA.MedidorAnormalidad as id_mediest', 'MODIFICACIONGENERACIONLECTURA.MediaAnterior as media_ant',
                'MODIFICACIONGENERACIONLECTURA.Media as media', 'MODIFICACIONGENERACIONLECTURA.ConsumoFacturado as consumofac', 'MODIFICACIONGENERACIONLECTURA.ConsumoDebito as consumodeb',
                'MODIFICACIONGENERACIONLECTURA.FechaAnterior as fechaant', 'MODIFICACIONGENERACIONLECTURA.HoraAnterior as horaant', 'MODIFICACIONGENERACIONLECTURA.Categoria as id_categ',
                'MODIFICACIONGENERACIONLECTURA.Medidor as id_medidor', 'MODIFICACIONGENERACIONLECTURA.TipoConsumo as tipoconsum', 'MODIFICACIONGENERACIONLECTURA.MedidorAnormalidad2 as id_medies2',
                'MODIFICACIONGENERACIONLECTURA.AplicoMedia as aplicomedi', 'MODIFICACIONGENERACIONLECTURA.ConsuFactu as consufactu', 'MODIFICACIONGENERACIONLECTURA.AjusteConsumo as ajustecons',
                'MODIFICACIONGENERACIONLECTURA.AjusteMonto as ajustemont', 'MODIFICACIONGENERACIONLECTURA.DesviacionSignificativa as desvsigni', 'MODIFICACIONGENERACIONLECTURA.InspeccionRequerida as insperequ',
                'MODIFICACIONGENERACIONLECTURA.Facturado as facturado', 'MODIFICACIONGENERACIONLECTURA.ValidoLectura as vallectura')
            ->join('GENERACIONFACTURA', 'MODIFICACIONGENERACIONLECTURA.GeneracionFactura', 'GENERACIONFACTURA.GeneracionFactura')
            //->where('GENERACIONFACTURA.Cobro', '=', date('Y-m'))
            ->where('GENERACIONFACTURA.Plomero', '=', $Plomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->get();
    
        return $loModificacionGeneracionLectura;
    }
}