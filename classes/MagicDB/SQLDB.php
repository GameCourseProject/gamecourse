<?php
namespace MagicDB;

class SQLDB {
    private $db;
    public function __construct($dsn, $username = '', $password = '') {
        try{
            $this->db = new \PDO($dsn, $username, $password);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e){
            echo $e->getMessage();
        }    
    }
    public function executeQuery($sql){
        try{
            $result=$this->db->query($sql);
        }catch(\PDOException $e ){
            echo $sql . "<br>" . $e->getMessage() . "<br>";//ToDo maybe trhrow again
            throw new \PDOException($e);
        }
        return $result;
    }
    public function executeQueryWithParams($sql,$data){
        try{
            $stmt=$this->db->prepare($sql);
        
            $stmt->execute($data);
            //$result=$this->db->query($sql);
        }catch(\PDOException $e ){
            echo "<br>". $sql . "<br>" . $e->getMessage() . "<br>";
            throw new \PDOException($e);
        }
        return $stmt;
    }
    
    public function dataToQuery(&$sql,$data,$separator,$add=false){
        //takes an array and creates a string with $key=$value(,&).... then adds to sql query str        
        foreach ($data as $key => $value) {
            if ($key=="roles" && $separator=='&&')//'roles' is a set so it works differently
                $sql.="FIND_IN_SET( :roles ,roles)".$separator;
            elseif ($add)
                $sql.=$key.'= '.$key.' + '.$value.$separator;             
            else
                $sql.=$key.'= :'.$key.' '.$separator;
        }
        $sql=substr($sql,0,-(strlen($separator)));
    }

    //functions to construct and execute sql querys
    public function insert($table,$data){
        //example: insert into user set name="Example",id=80000,username=ist1800000;
        $sql="insert into ".$table." set "; 
        $this->dataToQuery($sql,$data,',');
        $sql.=";";     
        
        //print_r($sql);
        //print_r($data);
        //print_r(" <<\n>> ");
        $this->executeQueryWithParams($sql,$data); 
    }
    public function delete($table,$where){
        $sql="delete from ".$table." where ";
        $this->dataToQuery($sql,$where,'&&');
        $sql.=';';
        $this->executeQueryWithParams($sql,$where);  
    }
    public function update($table,$data,$where){
        //example: update user set name="Example", email="a@a.a" where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$data,',');
        $sql.= " where ";
        $this->dataToQuery($sql,$where,'&&');
        $sql.=';';
        $data=array_merge($data,$where);
       // print_r($sql);
        //print_r($data);
        //print_r(" <<\n>> ");
        $this->executeQueryWithParams($sql,$data);     
    }   
    public function updateAdd($table,$collumQuantity,$where){
        //example: update user set name="Example", email="a@a.a" where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$collumQuantity,',',true);
        $sql.= " where ";
        $this->dataToQuery($sql,$where,'&&');
        $sql.=';';
        //print_r($sql);
        //print_r($data);
        //print_r(" <<\n>> ");
        $this->executeQueryWithParams($sql,$where);     
        
    }
    public function select($table,$field,$where){
    //ToDo: devia juntar as 2 funçoes select, devia aceitar array de fields,
        //example: select id from user where username='ist181205';
        $sql = "select ".$field." from ".$table." where ";
        $this->dataToQuery($sql,$where,'&&');
        $sql.=';';      
         //{print_r($sql);
        //print_r($where);
        $result = $this->executeQueryWithParams($sql,$where);
        //print_r($result->fetch());
        return $result->fetch();
    } 
    public function selectMultiple($table,$field='*',$where=null){
        //example: select * from course where isActive=true;
        $sql = "select ".$field." from ".$table;
        if ($where){
            $sql.=" where ";
            $this->dataToQuery($sql,$where,'&&');   
        }
        $sql.=';';
        //print_r($sql);
        //print_r(" <<\n>> ");
        $result = $this->executeQueryWithParams($sql,$where);
        //print_r($result->fetchAll(\PDO::FETCH_ASSOC));
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }
}
