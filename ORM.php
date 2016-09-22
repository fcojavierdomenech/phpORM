<?php

require_once('DataBase.php');

/**
 * Class ORM
 * @author Fco.Javier Domenech
 */
abstract class ORM
{
    //protected $db;
    public $db;
    protected $id_column_name;
    protected $tablename;
    protected $orm_fields_not_storable = array(
        'db',
        'tablename',
        'id_column_name',
        'fields_not_storable',
        'orm_fields_not_storable'
    );

    /** ORM
     * constructor
     */
    function ORM ($db = null) {

        $this->db = $db;
        if(!$db)
            $this->db = new DataBase();
    }

    /** getArrayFieldsNotStorable
     *
     */
    private function getArrayFieldsNotStorable () {
        $array = array_merge($this->fields_not_storable,$this->orm_fields_not_storable);
        $array[] = $this->id_column_name;
        return $array;
    }

    /** Devuelve el id, pk en la BD
     * @return int
     */
    private function getIdColumnName()
    {
        return $this->id_column_name;
    }

    /** Devuelve el nombre de la tabla de la BD
     * @return string
     */
    private function getTableName()
    {
        return $this->tablename;
    }

    private function isFieldStorable($field)
    {
        if(!in_array($field,$this->getArrayFieldsNotStorable()))
            return true;

        return false;
    }

    /**
     * save
     * guarda en la BD
     * Si no existe inserta
     * si existe actualiza
     * @return id
     */
    public function save() {
        $tablename = $this->getTableName();
        $id_column_name = $this->getIdColumnName();

        $sql = "SELECT * FROM ".$tablename." WHERE $id_column_name='".$this->{$id_column_name}."'";
        $this->db->doQuery($sql);

        if(!$this->db->existResult())
            return $this->insert();

        return $this->update();
    }


    /** Actualiza el registro en la BD
     * @return int id
     */
    public function update()
    {
        $tablename = $this->getTableName();
        $id_column_name = $this->getIdColumnName();

        $sql = "UPDATE $tablename SET ";

        $at_least_one_field = false;

        foreach ( $this as $field => $value ) {
            if($this->isFieldStorable($field))
            {
                if($at_least_one_field)
                    $sql .= ',';

                $sql .= "$field='$value'";

                $at_least_one_field = true;
            }
        }

        $sql .=" WHERE $id_column_name = '".$this->{$id_column_name}."'";
        $this->db->doQuery($sql);

        return $this->id;
    }

    /** Crea un nuevo registro en la BD
     * @return int id
     */
    public function insert()
    {
        $tablename = $this->getTableName();

        $at_least_one_field = false;

        $fields = '';
        $values = '';
        foreach ( $this as $field => $value ) {

            if($this->isFieldStorable($field))
            {
                if($at_least_one_field)
                {
                    $fields .= ',';
                    $values .= ',';
                }

                $fields .= $field;
                $values .= "'$value'";

                $at_least_one_field = true;
            }
        }

        $sql = "INSERT INTO $tablename ($fields)
            VALUES ($values)";

        $this->db->doQuery($sql);

        $this->id = $this->db->getLastInsertId();

        return $this->id;
    }

    /** delete
     * borra el registro de la BD
     * @return void
     */
    public function delete()
    {
        $tablename = $this->getTableName();
        $id_column_name = $this->getIdColumnName();

        $sql = "DELETE FROM $tablename WHERE $id_column_name = '".$this->{$id_column_name}."'";

        $this->db->doQuery($sql);
    }

    /** Devuelve el objeto dado un id
     * false en caso de no existir
     * @param id
     * @param tablename
     * @param id_column_name
     * @param $called_class_name
     * @param $db opcional objeto a usar en la bd
     * return Object
     */
    public static function getById($id, $db = null)
    {
        if(!$db)
            $db = new DataBase();

        $retorno = false;
        $called_class_name = get_called_class();
        $new_obj = new $called_class_name($db);

        $sql = "SELECT * FROM $new_obj->tablename WHERE $new_obj->id_column_name='".$id."'";
        $res = $db->doQuery($sql);

        if($db->existResult($res))
        {
            $object = $db->fetchObject($res);

            foreach ( $object as $field => $value ) {
                $new_obj->{$field} = $value;
            }

            return $new_obj;
        }
        //$db->logLastQuery();

        return false;
    }

    /** Devuelve el objeto dado su id
     * false en caso de no existir
     * @param column
     * @param value
     * @param $tablename
     * @param $called_class_name
     * @param $db opcional objeto a usar en la bd
     * return Busqueda
     */
    public static function getByColumnValue($column,$value,$db = null)
    {
        if(!$db)
            $db = new DataBase();

        $retorno = false;
        $called_class_name = get_called_class();
        $new_obj = new $called_class_name($db);

        $sql = "SELECT * FROM $new_obj->tablename WHERE $column='".$value."'";
        $res = $db->doQuery($sql);

        if($db->existResult($res))
        {
            $object = $db->fetchObject($res);

            foreach ( $object as $field => $value ) {
                $new_obj->{$field} = $value;
            }

            return $new_obj;
        }
        //$db->logLastQuery();

        return false;
    }

    /** Devuelve un array con los objetos coincidentes en la columna
     * false en caso de no existir
     * @param column
     * @param value
     * @param $tablename
     * @param $called_class_name
     * @param $db opcional objeto a usar en la bd
     * return Busqueda
     */
    public static function getAllByColumnValue($column,$value,$sort_by=null,$sort_order='ASC',$db = null)
    {
        if(!$db)
            $db = new DataBase();

        $retorno = false;
        $called_class_name = get_called_class();
        $new_obj = new $called_class_name($db);

        $sql = "SELECT * FROM $new_obj->tablename WHERE $column='".$value."'";
        if($sort_by!=null)
            $sql.= " order by $sort_by $sort_order";
        $res = $db->doQuery($sql);

        $objects = array();

        while($object = $db->fetchObject($res))
        {
            $new_obj = new $called_class_name($db);

            foreach ( $object as $field => $value ) {
                $new_obj->{$field} = $value;
            }

            $objects[] = $new_obj;
        }

        return $objects;
    }

}
