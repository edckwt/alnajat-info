<?php
class DB {
  public $db_host;
  public $db_user;
  public $db_pass;
  public $db_name;
  public $version;
  public $db_link;

  function __construct($db_host, $db_name, $db_user, $db_pass){
    $this->version = '3';
    $this->db_host = $db_host;
    $this->db_name = $db_name;
    $this->db_user = $db_user;
    $this->db_pass = $db_pass;

    $this->db_link = @mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name);

    if( mysqli_connect_error() ){
      echo '<p>Not connected, error: <strong>'.mysqli_connect_error().'</strong></p>';
      exit;
    }

    mysqli_set_charset($this->db_link, 'utf8');
  }

  public function N_query($q){
    $sql = mysqli_query($this->db_link, $q);
    return $sql;
  }

  public function N_select($table, $where=''){
    $wh = "";
    if( is_array($where) && count($where) > 0 ){
      $count = count($where);
      $i = 0;
      $wh .= "WHERE ";
      foreach ($where as $key => $value) {
        $i++;
        $v = $this->N_escape_string($value);
        $wh .= $key."='$v'";
        if( $i != $count ){
          $wh .= " AND ";
        }
      }
    }

    $sql = sprintf("SELECT * FROM %s %s", $table, $wh);

    return $this->N_query($sql);
  }

  public function N_fetch_array($data){
    $sql = mysqli_fetch_array($data);
    return $sql;
  }

  public function N_fetch_assoc($data){
    $sql = mysqli_fetch_assoc($data);
    return $sql;
  }

  public function N_fetch_object($data){
    $sql = mysqli_fetch_object($data);
    return $sql;
  }

  public function N_num_rows($data){
    if( $data ){
      $sql = mysqli_num_rows($data);
    }else{
      $sql = false;
    }
    return $sql;
  }

  public function N_insert($table, $val){
    $fields = '';
    $values = '';
    if( is_array($val) && count($val) > 0 ){
      $count = count($val);
      $i = 0;
      foreach ($val as $key => $value) {
        $i++;
        $v = $this->N_escape_string($value);
        $fields .= $key;
        $values .= "'$v'";
        if( $i != $count ){
          $fields .= ", ";
          $values .= ", ";
        }
      }
    }else{
      return false;
    }

    $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $fields, $values);
    return $this->N_query($sql);
  }

  public function N_update($table, $val, $where){
    $set = "";
    if( is_array($val) && count($val) > 0 ){
      $count = count($val);
      $i = 0;
      foreach ($val as $key => $value) {
        $i++;
        $v = $this->N_escape_string($value);
        $set .= $key."='$v'";
        if( $i != $count ){
          $set .= ", ";
        }
      }
    }else{
      return false;
    }

    $wh = "";
    if( is_array($where) && count($where) > 0 ){
      $countx = count($where);
      $ix = 0;
      foreach ($where as $key => $value) {
        $ix++;
        $v = $this->N_escape_string($value);
        $wh .= $key."='$v'";
        if( $ix != $countx ){
          $wh .= " AND ";
        }
      }
    }else{
      return false;
    }

    $sql = sprintf("UPDATE %s SET %s WHERE %s", $table, $set, $wh);

    return $this->N_query($sql);
  }

  public function N_delete($table, $where){
    $wh = "";
    if( is_array($where) && count($where) > 0 ){
      $count = count($where);
      $i = 0;
      foreach ($where as $key => $value) {
        $i++;
        $v = $this->N_escape_string($value);
        $wh .= $key."='$v'";
        if( $i != $count ){
          $wh .= " AND ";
        }
      }
    }else{
      return false;
    }

    $sql = sprintf("DELETE FROM %s WHERE %s", $table, $wh);
    return $this->N_query($sql);
  }

  public function N_insert_id(){
    return mysqli_insert_id($this->db_link);
  }

  public function last_N_insert_id($table, $auto_increment="id"){
    $query = $this->N_query("SELECT ".$auto_increment." FROM ".$table." ORDER BY ".$auto_increment." DESC LIMIT 1");
    $row = $this->N_fetch_array($query);
    $id = $row[$auto_increment];
    return $id;
  }

  public function N_escape_string( $text ){
    $str = trim($text);
    return mysqli_real_escape_string($this->db_link, $str);
  }

  public function N_num_fields($data){
    $sql = mysqli_num_fields($data);
    if( isset($sql) ){
      return $sql;
    }else{
      die( $this->N_error_db() );
      return false;
    }
  }

  public function N_field_name($data, $i){
    $sql = mysqli_fetch_field_direct($data, $i); //mysql_field_name
    if( isset($sql) ){
      return $sql;
    }else{
      die( $this->N_error_db() );
      return false;
    }
  }

  public function N_field_type($data, $i){
    $sql = mysql_field_type($data, $i);
    if( isset($sql) ){
      return $sql;
    }else{
      die( $this->N_error_db() );
      return false;
    }
  }

  public function N_fetch_row($data){
    $sql = mysqli_fetch_row($data);
    if( isset($sql) ){
      return $sql;
    }else{
      die( $this->N_error_db() );
      return false;
    }
  }

  public function N_tablename($data, $i){
    $sql = mysqli_fetch_array($data, $i);
    if( isset($sql) ){
      return $sql;
    }else{
      die( $this->N_error_db() );
      return false;
    }
  }

  public function N_error_db(){
    return mysqli_error($this->db_link);
  }

  public function N_connect_error(){
    return mysqli_connect_error();
  }

  public function N_close() {
    return mysqli_close( $this->db_link );
  }
}
?>
