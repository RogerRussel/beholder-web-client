<?php

namespace BeholderWebClient\Eyes\Db\PostgreSQL;

use PDO as PHPDO;
use Exception;
use BeholderWebClient\Eyes\Db\DbStatus as Status;

class Pdo  extends AbstractAdapter {

  protected $pdo;
  protected $ran = false;

  public function checkRequirement(){

    if(!class_exists("PDO"))
      throw new Exception(parent::PREFIX_RQ_FAIL . 'PDO driver not found', Status::INTERNAL_SERVER_ERROR_NUMBER);

  }

  public function testConn(){

    try {

      $dns = "pgsql:host={$this->conf['host']};dbname={$this->conf['dbname']};port={$this->conf['port']};";
      $this->pdo = new PHPDO($dns, $this->conf['user'] , $this->conf['password'],
      [PHPDO::ATTR_ERRMODE => PHPDO::ERRMODE_EXCEPTION]);

    }catch(Exception $ex){
      parent::throwCouldNotConnect($ex->getMessage());
    }

  }

  public function testQuery(){

      if(isset($this->conf['query']['create']))
        $this->execCreatequery('create');

      if(isset($this->conf['query']['insert']))
        $this->execquery('insert', Status::QUERY_INSERT_FAIL_NUMBER, Status::QUERY_INSERT_FAIL);

      if(isset($this->conf['query']['update']))
        $this->execquery('update', Status::QUERY_UPDATE_FAIL_NUMBER, Status::QUERY_UPDATE_FAIL);

      if(isset($this->conf['query']['select']))
        $this->selectquery();

      if(isset($this->conf['query']['delete']))
        $this->execquery('delete', Status::QUERY_DELETE_FAIL_NUMBER, Status::QUERY_DELETE_FAIL);

      if(isset($this->conf['query']['drop']))
        $this->execDropquery('drop');

      if(!$this->ran)
        parent::throwQueryBadFormated();

  }

  protected function execquery($type, $errNo, $errMessage){

    $this->ran = true;

    foreach($this->conf['query'][$type] as $sql ){

      $sth = $this->pdo->prepare($sql);
      $sth->execute();

      if($sth->rowCount() < 1)
        throw new Exception($errMessage . " - {$sql}", $errNo);

    }

  }

  protected function selectquery(){

    $this->ran = true;

    foreach($this->conf['query']['select'] as $sql ) {

      $sth = $this->pdo->prepare($sql);
      $sth->execute();

      $result = $sth->fetch(PHPDO::FETCH_NUM);

      if(count($result) < 1)
        throw new Exception(Status::QUERY_SELECT_FAIL_NUMBER, Status::QUERY_SELECT_FAIL);


    }

  }

  protected function execCreatequery(){

    $this->ran = true;

    try {

      foreach($this->conf['query']['create'] as $sql ){
        $this->pdo->exec($sql);
      }

    } catch(Exception $ex){
        throw new Exception(Status::COULD_NOT_CREATE_TABLE . " - {$sql} " . $ex->getMessage(), Status::COULD_NOT_CREATE_TABLE_NUMBER);
    }

  }

  protected function execDropquery(){

    $this->ran = true;

    try {

      foreach($this->conf['query']['drop'] as $sql ){
        $this->pdo->exec($sql);
      }

    } catch(Exception $ex){
        throw new Exception(Status::COULD_NOT_DROP_TABLE . " - {$sql} " . $ex->getMessage(), Status::COULD_NOT_DROP_TABLE_NUMBER);
    }
  }

  public function closeConnection(){
    $this->pdo = null;
  }

}
