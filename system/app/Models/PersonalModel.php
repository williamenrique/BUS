<?php
class PersonalModel extends Mysql {

	public function __construct(){
	//heradar la clase padre 
		parent::__construct();
	}
    /**********funcion para insertar personal*********/
	public function insertPersonal(string $intIdentificacion, string $strNombre, string $strApellido, int $intlistRolId, string $intTxtTlf, string $strEmail, string $strDireccion, int $intTagPersonal, int $intListStatus){
		$this->intIdentificacion = $intIdentificacion;
		$this->strNombre = $strNombre;
		$this->strApellido = $strApellido;
		$this->intlistRolId = $intlistRolId;
		$this->intTxtTlf = $intTxtTlf;
		$this->strEmail = $strEmail;
		$this->strDireccion = $strDireccion;
		$this->intTagPersonal = $intTagPersonal;
		$this->intListStatus = $intListStatus;

		$select = "SELECT * FROM table_personal WHERE personal_cedula = ?";
		$requestSelect = $this->select($select, [$this->intIdentificacion]);
		if(!empty($requestSelect)){
			$requestInsert = 'exist'; // Devolvemos 'exist' si la cédula ya está registrada
		} else {
			$queryInsert = "INSERT INTO table_personal(personal_cedula, personal_nombre, personal_apellido, personal_cargo, personal_tlf, personal_email, personal_direccion, personal_tag, personal_status) VALUES(?,?,?,?,?,?,?,?,?)";
			$arrData = array($this->intIdentificacion, $this->strNombre, $this->strApellido, $this->intlistRolId, $this->intTxtTlf, $this->strEmail, $this->strDireccion, $this->intTagPersonal, $this->intListStatus);
			$requestInsert = $this->insert($queryInsert,$arrData);
		}
		return $requestInsert;
	}

    /**********funcion para traer todo el personal**********/
	public function selectPersonal(){
		$sql = "SELECT p.*, c.* FROM table_per_cargo c 
						INNER JOIN table_personal p  ON p.personal_cargo = c.id_cargo AND p.personal_status  <> 0 ORDER BY p.personal_cedula desc ";
		$request = $this->select_all($sql);
		return $request;
	}
    /**********funcion para seleccionar carg*********/
	public function selectCargo(){
		$sql = "SELECT * FROM table_per_cargo";
		$request = $this->select_all($sql);
		return $request;
	}

    /**
     * Selecciona una lista simplificada de todo el personal para selects.
     */
    public function selectPersonalList(){
        $sql = "SELECT id_personal, personal_cedula, personal_nombre, personal_apellido FROM table_personal WHERE personal_status != 0 ORDER BY personal_nombre ASC";
        $request = $this->select_all($sql);
        return $request;
    }

    /********** cambiar el status del personal**********/
	public function statusPersonal(int $intPersonal, int $intStatus){
		$this->intPersonal = $intPersonal;
		$this->intStatus = $intStatus;
		$sql = "UPDATE table_personal SET personal_status = ? WHERE id_personal = ?";
		$arrData = array($this->intStatus, $this->intPersonal);
		$request = $this->update($sql,$arrData);
		return $request;
	}
    /********** cambiar el status y agregar texto**********/
	public function cambioStatusPersonal(int $intPersonal, int $intStatus, string $srtText, int $intUserId){
		//asignamos las propiedades a las variable
		$return = "";
		$this->intPersonal = $intPersonal;
		$this->intStatus = $intStatus;
		$this->srtText = $srtText;
		$this->intUserId = $intUserId;
		$sql =  "INSERT INTO table_per_cambio_status_personal (id_personal, idStatus,textCambio,user_id) VALUES (?,?,?,?)";
		$arrData = array($this->intPersonal,$this->intStatus,$this->srtText,$this->intUserId);// armamos el array con los datos obtenidos
		$request = $this->insert($sql,$arrData);//enviamos el query y el array de datos 
		return $request;
	}
	//TODO: editar personal
	/**********funcion para traer datos de un personal**********/
	public function selectPersonalID(int $intIdPersonal){
		$this->intIdPersonal = $intIdPersonal;
		$sql = "SELECT p.*, c.* FROM table_per_cargo c 
					INNER JOIN table_personal p ON p.personal_cargo = c.id_cargo
					WHERE p.id_personal = ?";
		$request = $this->select($sql, [$this->intIdPersonal]);
		return $request;
	}
	public function updatePersona(int $intIdPersonal, string $intIdentificacion, string $strNombre, string $strApellido, int $intlistRolId, string $intTxtTlf, string $strEmail, string $strDireccion, int $intTagPersonal, int $intListStatus){
		
		$this->intIdPersonal = $intIdPersonal;
		$this->intIdentificacion = $intIdentificacion;
		$this->strNombre = $strNombre;
		$this->strApellido = $strApellido;
		$this->intlistRolId = $intlistRolId;
		$this->intTxtTlf = $intTxtTlf;
		$this->strEmail = $strEmail;
		$this->strDireccion = $strDireccion;
		$this->intTagPersonal = $intTagPersonal;
		$this->intListStatus = $intListStatus;

		// Verificación de cédula duplicada: se activa si la cédula existe en un registro DIFERENTE al que se está editando.
		$sql_check = "SELECT id_personal FROM table_personal WHERE personal_cedula = ? AND id_personal != ?";
		$request_check = $this->select($sql_check, [$this->intIdentificacion, $this->intIdPersonal]);

		if (!empty($request_check)) {
			return 'exist'; // La cédula ya pertenece a otro miembro del personal
		} else {

		$sql = "UPDATE table_personal SET personal_cedula = ?, personal_nombre = ?, personal_apellido = ?, personal_cargo = ?, personal_tlf = ?, personal_email = ?, personal_direccion = ?, personal_tag = ?, personal_status = ? WHERE id_personal= ?";
		$arrData = array($this->intIdentificacion, $this->strNombre, $this->strApellido, $this->intlistRolId, $this->intTxtTlf, $this->strEmail, $this->strDireccion, $this->intTagPersonal, $this->intListStatus, $this->intIdPersonal);
		$request = $this->update($sql, $arrData);
		return $request;
	}

	}
	public function deletePersonal(int $idPersonal){
		$this->intIdPersonal = $idPersonal;
		// Eliminación lógica cambiando el status a 0
		$sql = "UPDATE table_personal SET personal_status = 0 WHERE id_personal = ?";
		$request = $this->update($sql, [$this->intIdPersonal]);
		return $request;
	}

	/**
     * Selecciona un miembro del personal por su Cédula.
     * @param string $cedula - La cédula del personal a buscar.
     * @return array|null - Los datos del personal o null si no se encuentra.
     */
    public function selectPersonalByCedula(string $cedula) {
        $sql = "SELECT personal_nombre, personal_tlf 
                FROM table_personal 
                WHERE personal_cedula = ? AND personal_status != 0";
        return $this->select($sql, [$cedula]);
    }

    /**
     * Busca personal por un término de cédula (LIKE).
     * @param string $term - El término de búsqueda.
     * @return array - Lista de personal coincidente.
     */
    public function searchPersonalByCedula(string $term) {
        $term_like = "%{$term}%";
        $sql = "SELECT id_personal, personal_cedula, personal_nombre, personal_apellido 
                FROM table_personal 
                WHERE (personal_cedula LIKE ? OR CONCAT(personal_nombre, ' ', personal_apellido) LIKE ?) 
                AND personal_status != 0 
                LIMIT 10"; 
        return $this->select_all($sql, [$term_like, $term_like]);
    }

    /**
     * Selecciona el nombre y apellido de un miembro del personal por su Cédula.
     * @param string $cedula - La cédula del personal a buscar.
     * @return array|null - Los datos del personal o null si no se encuentra.
     */
    public function selectPersonalFullNameByCedula(string $cedula) {
        $sql = "SELECT personal_nombre, personal_apellido FROM table_personal WHERE personal_cedula = ? AND personal_status != 0";
        return $this->select($sql, [$cedula]);
    }
}