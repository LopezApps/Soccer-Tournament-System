<?php
// models/Equipo.php
class Equipo {
    private $conn;
    private $table_name = "equipos";

    public $id;
    public $nombre_oficial;
    public $nombre_corto;
    public $pais;
    public $confederacion;
    public $logo;
    public $grupo;
    public $eliminado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre_oficial=:nombre_oficial, nombre_corto=:nombre_corto, 
                      pais=:pais, confederacion=:confederacion, logo=:logo, grupo=:grupo";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nombre_oficial = htmlspecialchars(strip_tags($this->nombre_oficial));
        $this->nombre_corto = htmlspecialchars(strip_tags($this->nombre_corto));
        $this->pais = htmlspecialchars(strip_tags($this->pais));
        $this->confederacion = htmlspecialchars(strip_tags($this->confederacion));
        $this->grupo = htmlspecialchars(strip_tags($this->grupo));
        
        $stmt->bindParam(":nombre_oficial", $this->nombre_oficial);
        $stmt->bindParam(":nombre_corto", $this->nombre_corto);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":confederacion", $this->confederacion);
        $stmt->bindParam(":logo", $this->logo);
        $stmt->bindParam(":grupo", $this->grupo);
        
        return $stmt->execute();
    }

    public function leerTodos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY grupo, nombre_oficial";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerPorGrupo($grupo) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE grupo = ? ORDER BY nombre_oficial";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grupo);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_oficial=:nombre_oficial, nombre_corto=:nombre_corto, 
                      pais=:pais, confederacion=:confederacion, grupo=:grupo, eliminado=:eliminado 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre_oficial", $this->nombre_oficial);
        $stmt->bindParam(":nombre_corto", $this->nombre_corto);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":confederacion", $this->confederacion);
        $stmt->bindParam(":grupo", $this->grupo);
        $stmt->bindParam(":eliminado", $this->eliminado, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
