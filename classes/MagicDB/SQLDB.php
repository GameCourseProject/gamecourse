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
            echo "<br>". $sql . "<br>" . $e->getMessage() . "<br>";
            print_r(array_values($data));
            throw new \PDOException($e);
        }
        return $stmt;
    }
    
    public function dataToQuery(&$sql,&$data,$separator,$whereNot=[],$whereCompare=[],$add=false){
        //takes an array and creates a string with $key=$value(,&).... then adds to sql query str        
        foreach ($data as $key => $value) {
            if ($add)
                $sql.=$key.'= '.$key.' + '.$value.$separator;     
            elseif ($value === null && $separator == "&&") 
                $sql .= $key . " is ? " . $separator;
            else
                $sql .= $key . '= ? ' . $separator;
            //$sql.=$key.'= :'.$key.' '.$separator;
        }
        $data=array_values($data);
        foreach ($whereNot as $not){// [key , value] 
            if ($not[1] === null && $separator == "&&") 
                $sql.= $not[0] . " is not ? " .$separator;
            else
                $sql .= $not[0] . "!= ? " . $separator;
            array_push($data,$not[1]);
        }
        
        foreach ($whereCompare as $keyCompVal){
            //ex: ["key","<",5]]
            $sql.= $keyCompVal[0] . $keyCompVal[1] ." ? ".$separator;
            array_push($data,$keyCompVal[2]);
        }
        $sql=substr($sql,0,-(strlen($separator)));
    }

    //functions to construct and execute sql querys
    
    public function insert($table,$data=[]){
        //example: insert into user set name="Example",id=80000,username=ist1800000;
        
        $sql="insert into ".$table;
        if ($data==[]){
            $sql.=" values(default)";
        }else{
            $sql.=" set "; 
            $this->dataToQuery($sql,$data,',');
        }
        $sql.=";";     
        $this->executeQueryWithParams($sql,$data); 
    }
    
    public function delete($table,$where, $likeParams=null,$whereNot=[],$whereCompare=[]){
        $sql="delete from ".$table." where ";
        $this->dataToQuery($sql,$where,'&&',$whereNot,$whereCompare);
        if ($likeParams!=null){
            foreach($likeParams as $key => $value){
                $sql.=" && ". $key." like ? ";
            }
            $where=array_merge($where,array_values($likeParams));
        }
        $sql.=';';
        $this->executeQueryWithParams($sql,$where);  
    }
    
    public function update($table,$data,$where=null,$whereNot=[],$whereCompare=[]){
        //example: update user set name="Example", email="a@a.a" where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$data,',');
        if($where){
            $sql.= " where ";
            $this->dataToQuery($sql,$where,'&&', $whereNot,$whereCompare);
            $data= array_merge($data,$where);
        }
        $sql.=';';
        $this->executeQueryWithParams($sql,$data);     
    }   
    public function updateAdd($table,$collumQuantity,$where,$whereNot=[],$whereCompare=[]){
        //example: update user set n=n+1 where id=80000;
        $sql = "update ".$table." set ";
        $this->dataToQuery($sql,$collumQuantity,',',[],true);
        $sql.= " where ";
        $this->dataToQuery($sql,$where,'&&', $whereNot,$whereCompare);
        $sql.=';';
        $this->executeQueryWithParams($sql,$where); 
    }
    
    public function select($table,$where,$field='*',$orderBy=null,$whereNot=[],$whereCompare=[]){
    //ToDo: devia juntar as 2 funçoes select, devia aceitar array de fields,
        //example: select id from user where username='ist181205';
        $sql = "select ".$field." from ".$table;
        $sql.=" where ";
        $this->dataToQuery($sql,$where,'&&', $whereNot,$whereCompare);
        if ($orderBy){
            $sql.=" order by " . $orderBy;
        }
        $sql.=';';
        $result = $this->executeQueryWithParams($sql,$where);
        $returnVal=$result->fetch(\PDO::FETCH_ASSOC);
        if ($field=='*' or strpos($field,',')){
            return $returnVal;
        }
        if ($pos=strpos($field,".")!==false)
            return $returnVal[substr($field,$pos+1)];
        return $returnVal[$field];
    } 
    public function selectMultiple($table,$where=null,$field='*',$orderBy=null,$whereNot=[],$whereCompare=[],$group=null){
        //example: select * from course where isActive=true;
        $sql = "select ".$field." from ".$table;
        if ($where){
            $sql.=" where ";
            $this->dataToQuery($sql,$where,'&&', $whereNot,$whereCompare);
        }
        if ($group){
            $sql.=" group by ". $group;
        }
        if ($orderBy){
            $sql.=" order by " . $orderBy;
        }
        
        $sql.=';';
        $result = $this->executeQueryWithParams($sql,$where);
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    //this returns the last auto_increment id after an insertion in the DB
    public function getLastId(){
        $result=$this->executeQuery("SELECT LAST_INSERT_ID();");
        return $result->fetch()[0];       
    }
}
