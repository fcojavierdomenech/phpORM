<?php

require_once('ORM.php');

/**
 * Class Descuento
 * @author Javier Domenech
 *
 * This is an example of how a class should extend the ORM
 */
class Example extends ORM
{
    protected $fields_not_storable = array('');
    protected $id_column_name = 'id';
    protected $tablename = 'examples';

    public $id;    //  	int(11)
    public $name; //  	string(24)
}

//some uses

$example = new Example();
$example->name = 'first example';
$example->save(); //insert

$example = Example::getById(1);
$example->name = 'same example';
$example->save(); //updates

$example = Example::getByColumnValue('name','same example');
$example->name = 'another name';
$example->save();

$example->delete();

