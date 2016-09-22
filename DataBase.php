<?php

/**
 * Class DataBase
 * @author Fco.Javier Domenech Arenos
 */
class DataBase
{
    var $dbhost = 'localhost';
    var $dbname = 'myname';
    var $dbuser = 'myuser';
    var $dbpass = 'mypass';
    var $res;
    var $last_query;
    var $last_insert_id;


    /** Establece y devuelve la conexion con la BD
     */
    public function getConn()
    {
        $this->conn = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname", $this->dbuser, $this->dbpass);

        return $this->conn;
    }

    /* Constructor */
    function Database()
    {
        $this->getConn();
    }

    /** Ejecuta una query
     * en caso de ser un insert o replace into devuelve el id
     * @param query
     * @return res si query, id si insert/replace
     */
    public function doQuery($query)
    {
        $result = false;
        if($this->conn){
            $this->last_query = $query;
            $result = $this->conn->query($query);
        }

        if(((strpos(strtolower($query),'insert ')!==false) or strpos(strtolower($query),'replace ')!==false) and strpos(strtolower($query),' into ')!==false){
            $result = $this->conn->lastInsertId();
            $this->last_insert_id = $result;
        }

        $this->res = $result;
        return $result;
    }

    /** Funcion para convertir un prepared statement a
     * string y asi depurar
     * @param string sql
     * @param array parametros
     * @return string
     * */
    public function debugPreparedStatement($sql_string, array $params = null) {
        if (!empty($params)) {
            $indexed = $params == array_values($params);
            foreach($params as $k=>$v) {
                if (is_object($v)) {
                    if ($v instanceof \DateTime) $v = $v->format('Y-m-d H:i:s');
                    else continue;
                }
                elseif (is_string($v)) $v="'$v'";
                elseif ($v === null) $v='NULL';
                elseif (is_array($v)) $v = implode(',', $v);

                if ($indexed) {
                    $sql_string = preg_replace('/\?/', $v, $sql_string, 1);
                }
                else {
                    if ($k[0] != ':') $k = ':'.$k; //add leading colon if it was left out
                    $sql_string = str_replace($k,$v,$sql_string);
                }
            }
        }
        return $sql_string;
    }


    /**
     * PDO Prepared Statement
     *
     * @param String query to execute
     * @param array params to form the query
     * @return void
     */
    public function doPreparedQuery($query, $params)
    {
        $result = false;
        if($this->conn){
            $this->last_query = $this->debugPreparedStatement($query,$params);
            $statement = $this->conn->prepare($query);
            $result = $statement->execute($params);

            if(((strpos(strtolower($query),'insert ')!==false) or strpos(strtolower($query),'replace ')!==false) and strpos(strtolower($query),' into ')!==false){
                $result = $this->conn->lastInsertId();
                $this->last_insert_id = $result;
            }

            if($this->res != null)
                $this->res->closeCursor();

        }

        $this->res = $result;
        return $result;
    }

    public function closeConn()
    {
        $this->conn = null;
    }

    /** numRows
     * Devuelve el numero de registros encontrados en la ultima query ejecutada
     * @param res opcional, si se suministra se ejecuta sobre este
     * @return int
     */
    public function numRows ($res = null) {

        $res = $res? $res : $this->res;
        $n_rows = $res->rowCount();

        if(!$n_rows)
        {
            $array = $this->res->fetchAll();
            $n_rows = count($array);
        }

        return $n_rows;
    }

    /** existResult
     * @param res opcional, si se suministra se ejecuta sobre este
     * @return bool true si hay resultado de la query ejecutada, false en caso contrario
     */
    public function existResult ($res = null) {
        $res = $res? $res : $this->res;
        if($res)
            return ($this->numRows($res) > 0);
        return false;
    }

    /** fetchObject
     * devuelve siguiente registro almacenado en res
     * @param res opcional, si se suministra se ejecuta sobre este
     * @return stdClass
     */
    public function fetchObject ($res = null) {
        if(!$res)
            $res = $this->res;

        $obj = $res->fetch(PDO::FETCH_OBJ);

        return $obj;
    }

    /** getLastInsertId
     * Devuelve el id asignado al ultimo insert/replace ejecutado
     */
    public function getLastInsertId () {
        return $this->last_insert_id;
    }

    /** getLastQuery
     *
     */
    public function getLastQuery () {
        return $this->last_query;
    }

    /** logLastQuery
     *
     */
    public function logLastQuery () {
        echo "<h5>$this->last_query</h5>";
    }

    /** getUser
     * @return string
     */
    function getUser () {
        return $this->dbuser;
    }

    /** getPass
     * @return string
     */
    function getPass () {
        return $this->dbpass;
    }

    /** getHost
     * @return string
     */
    function getHost () {
        return $this->dbhost;
    }

    /** getDBName
     * @return string
     */
    function getDBName () {
        return $this->dbname;
    }

    /** setCharSet
     * cambia la coalicion de caracteres
     */
    function setCharSet ($charset) {
        $this->conn->exec('set names utf8');
    }

}
