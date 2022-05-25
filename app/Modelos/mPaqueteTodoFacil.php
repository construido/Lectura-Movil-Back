<?php

namespace App\Modelos;

class mPaqueteTodoFacil{

    public $error;
    public $status;
    public $message = "";
    public $messageMostrar = 0;
    public $messageSistema = "";
    public $values;

    function __construct(){
        $this->error   = 0;
        $this->status  = 1;
        $this->message = "Éxito";
        $this->values  = null;
    }
    
}

?>