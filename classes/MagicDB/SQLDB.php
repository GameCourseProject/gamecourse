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
            //echo $sql . "<br>" . $e->getMessage() . "<br>";
            throw new \PDOException($e);
        }
        return $result;
    }
    public function executeQueryWithParams($sql,$data){
        try{
            $stmt=$this->db->prepare($sql);            
            $stmt->execute($data);
        }catch(\PDOException $e ){
            //echo "<br>". $sql . "<br>" . $e->getMessage() . "<br>";
            throw new \PDOException($e);
        }
        return $stmt;
    }
    
    public function dataToQuery(&$sql,$data,$separator,$add=false){
        //takes an array and creates a string with $key=$value(,&).... then adds to sql query str        
        foreach ($data as $key => $value) {
            if ($add)
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
        $this->executeQueryWithParams($sql,$data); 
    }
    
    public function delete($table,$where, $likeParams=null){
        $sql="delete from ".$table." where ";
        $this->dataToQuery($sql,$where,'&&');
        if ($likeParams!=null){
            foreach($likeParams as $key => $value){
                $sql.=" && ". $key." like :".$key;
            }
            $where=array_merge($where,$likeParams);
        }
        $sql.=';';
        $this->executeQueryWithParams($sql,$where);  
    }
    
    public function update($table,$data,$where=null){
        //example: update user set name="Example", email="a@a.a" where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$data,',');
        if($where){
            $sql.= " where ";
            $this->dataToQuery($sql,$where,'&&');
            $data=array_merge($data,$where);
        }
        $sql.=';';
        $this->executeQueryWithParams($sql,$data);     
    }   
    public function updateAdd($table,$collumQuantity,$where){
        //example: update user set n=n+1 where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$collumQuantity,',',true);
        $sql.= " where ";
        $this->dataToQuery($sql,$where,'&&');
        $sql.=';';
        $this->executeQueryWithParams($sql,$where); 
    }
    
    public function select($table,$field,$where,$orderBy=null){
    //ToDo: devia juntar as 2 funçoes select, devia aceitar array de fields,
        //example: select id from user where username='ist181205';
        $sql = "select ".$field." from ".$table;
        $sql.=" where ";
        $this->dataToQuery($sql,$where,'&&');
        if ($orderBy){
            $sql.=" order by " . $orderBy;
        }
        $sql.=';';
        $result = $this->executeQueryWithParams($sql,$where);
        $returnVal=$result->fetch(\PDO::FETCH_ASSOC);
        if ($field=='*' or strpos($field,',')){
            return $returnVal;
        }
        return $returnVal[$field];
    } 
    public function selectMultiple($table,$field='*',$where=null,$orderBy=null){
        //example: select * from course where isActive=true;
        $sql = "select ".$field." from ".$table;
        if ($where){
            $sql.=" where ";
            $this->dataToQuery($sql,$where,'&&');   
        }
        if ($orderBy){
            $sql.=" order by " . $orderBy;
        }
        $sql.=';';
        $result = $this->executeQueryWithParams($sql,$where);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }
}
