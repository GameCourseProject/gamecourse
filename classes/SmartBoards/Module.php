<?php
namespace SmartBoards;
use Modules\Views\Expression\ValueNode;

abstract class Module {
    
    private $id;
    private $name;
    private $version;
    private $dependencies;
    private $dir;
    private $parent;
    private $resources = array();
    
    public function __construct() {
    }

    public function getId() {
        return $this->id;
    }
    public function getCourseId(){
        return $this->getParent()->getId();
    }

    public function getName() {
        return $this->name;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getDependencies() {
        return $this->dependencies;
    }

    public function getDir() {
        return $this->dir;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getData() {
        return $this->parent->getModuleData($this->getId());
    }

    public function setupResources() {
    }

    public function addResources(...$files) {
        $this->resources = array_unique(array_merge($this->resources, \Utils::discoverFiles($this->dir, ...$files)));
    }

    public function getResources() {
        return $this->resources;
    }
    
    public function init() {
    }

    public function initSettingsTabs() {
    }

    public function processRequest() {
    }

    public function cleanModuleData() {
    }

    public function __set($key, $value)  {
        $trace = debug_backtrace();
        if(isset($trace[1]['class']) && $trace[1]['class'] == 'SmartBoards\ModuleLoader') {
            return $this->$key = $value;
        }
        trigger_error('Cannot access private property ' . __CLASS__ . '::$' . $key, E_USER_ERROR);
    } 
    
    //functions that are used in the modules in the functions of the expression language
    
    public function getUserId($user){
        if (is_array($user))
            return $user["value"]["id"];
        else
            return $id=$user;
    }
    //create value node of an object or collection
    public function createNode($value,$type="object"){
        return new ValueNode(["type"=>$type,"value"=>$value]);
    }
    //get award or participations from DB, (moduleInstance can be name or id
    public function getAwardOrParticipation($courseId,$user,$type,$moduleInstance,$initialDate=null,$finalDate=null,$where=[],$object="award"){
        if ($user !== null) {
            $where["user"]=$this->getUserId($user);
        }
        //expected date format DD/MM/YYY needs to be converted to YYYY-MM-DD
        $whereDate=[];
        if ($initialDate !== null) {
            $date = implode("-",array_reverse(explode("/",$initialDate)));
            array_push($whereDate,["date",">",$date]);
        }
        if ($finalDate !== null) {
            //tecnically the final date on the results will be the day before the one given
            //because the timestamp is of that day at midnigth
            $date = implode("-",array_reverse(explode("/",$finalDate)));
            array_push($whereDate,["date","<",$date]);
        }
            
        if ($type !== null) {
            $where["type"]=$type;
            //should only use module instance if the type is specified (so we know if we should use skils or badges)
            if ($moduleInstance !== null && ($type=="badge" || $type=="skill")) {
                if (is_int($moduleInstance))
                    $where["m.id"]=$moduleInstance;
                else
                    $where["name"]=$moduleInstance;
                $where["a.course"]=$courseId;
                $table = $object." a join ".$type." m on moduleInstance=m.id";
                return (Core::$systemDB->selectMultiple($table,$where,"a.*,m.name",null,[],$whereDate));
            }
        }
        $where["course"]=$courseId;
        return Core::$systemDB->selectMultiple($object,$where,"*",null,[], $whereDate);
    }
}
?>
