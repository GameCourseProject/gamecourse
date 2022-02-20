<?php
chdir('C:\xampp\htdocs\gamecourse');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
require_once 'classes/ClassLoader.class.php';


use GameCourse\Core;
use Modules\Skills\Skills;

use PHPUnit\Framework\TestCase;


class ModuleSkillsTest extends TestCase
{
    protected $skills;

    public static function setUpBeforeClass():void {
        Core::init();
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Skills::ID . "/create.sql"));
    }

    protected function setUp():void {
        $this->skills = new Skills();
    }

    protected function tearDown():void {
        Core::$systemDB->deleteAll("course");
    }

    public static function tearDownAfterClass(): void {
        Core::$systemDB->executeQuery(file_get_contents("modules/" . Skills::ID . "/delete.sql"));
    }

    //Data Providers
    public function newTierSuccessProvider(){
        return array(
            array(["tier" => "Tier 1", "reward" => 100]),           //standar case
            array(["tier" => "", "reward" => 100]),                 //empty tier
            array(["tier" => "Tier1", "reward" => 0])               //zero reward
        );
    }

    public function invalidNewTierProvider(){
        return array(
            array(["tier" => "Tier1", "reward" => -100]),     //negative reward
            array(["tier" => null, "reward" => 100]),         //null tier
            array(["tier" => "Tier1", "reward" => null])      //null reward
        );
    }

    public function editTierSuccessProvider(){
        return array(
            array(["tier" => "First Tier", "reward" => 350]),                       //same data
            array(["tier" => "Tier One", "reward" => 350]),                         //change tier
            array(["tier" => "", "reward" => 350]),                                 //change tier to empty string
            array(["tier" => "First Tier", "reward" => 100]),                       //change reward
            array(["tier" => "First Tier", "reward" => 0]),                         //change reward to zero
            array(["tier" => "First Tier", "reward" => 350, "seqId" => 5]),         //change seqId (nothing should c),
            array(["tier" => "First Tier", "reward" => 350, "seqId" => null]),      //null seqId (nothing should c),
            array(["tier" => "First Tier", "reward" => 350, "treeId" => 1]),        //change treeId (nothing should c),
            array(["tier" => "First Tier", "reward" => 350, "treeId" => null])      //null treeId (nothing should change)
        );
    }

    public function invalidEditTierProvider(){
        return array(
            array(["tier" => null, "reward" => 100]),               //null tier
            array(["tier" => "Tier One", "reward" => -1]),          //negative reward
        );
    }

    public function saveMaxSuccessProvider(){
        return array(
            array(0),                       //zero max
            array(1),                       //one max
            array(100)                      //different positive max
        );
    }

    public function invalidSaveMaxProvider(){
        return array(
            array(-1),                       //negative max
            array(-100),                     //negative max
        );
    }

    public function createFolderForSkillResourcesSuccessProvider(){
        return array(
            array("ReTrailer", "ReTrailer"),                    //standard skill name
            array(" Name With Spaces ", "NameWithSpaces"),      //skill name with spaces
            array("Re-edição", "Re-edição")                     //skill name with accented characters
        );
    }

    public function invalidCreateFolderForSkillResourcesProvider(){
        return array(
            array("ReTrailer", "ReTrailer"),                    //standard skill names
            array("NameWithSpaces", " Name With Spaces "),      //skill name with spaces
            array("ReTrailer", "retrailer"),                    //case sensitive names
            array("Re-edição", "Re-edição")                     //skill name with accented characters
        );
    }

    public function invalidImportFileProvider(){
        return array(
            array(""),                                               //empty file
            array(null),                                             //null file
            array("tier;name;dependencies;color;xp\n")               //empty file with header
        );
    }

    public function newSkillSuccessNormalDependenciesProvider(){
        return array(
            array(array("name" => "Looping GIF", "tier" => "2", "color" => "#00c140", "dependencies" => "Pixel Art + Course Logo"), "#00c140", 3),      //standard
            array(array("name" => "", "tier" => "2", "color" => "#00c140", "dependencies" => "Pixel Art + Course Logo"), "#00c140", 3),                 //empty name
            array(array("name" => "Looping GIF", "tier" => "3", "color" => null, "dependencies" => "Pixel Art + Course Logo"), "", 1),                  //null color
            array(array("name" => "Looping GIF", "tier" => "2", "color" => "", "dependencies" => "Pixel Art + Course Logo"), "", 3),                    //empty color
        );
    }

    public function newSkillFailProvider(){
        return array(
            array(array("name" => null, "tier" => "2", "color" => "#00c140", "dependencies" => "Pixel Art + Course Logo"), "name"),               //null name
            array(array("name" => "Looping GIF", "tier" => null, "color" => "#00c140", "dependencies" => "Pixel Art + Course Logo"), "tier")      //null tier
        );
    }

    public function editSkillSuccessProvider(){
        return array(
            array(array("name" => "Looping GIF", "tier" => "2", "color" => "#00a8a8", "seqId" => 1, "description" => null, "dependencies" => "Wildcard + Album Cover")),    //same data
            array(array("name" => "GIFted", "tier" => "2", "color" => "#00a8a8", "seqId" => 1, "description" => null, "dependencies" => "Wildcard + Album Cover")),         //change name
            array(array("name" => "Looping GIF", "tier" => "1", "color" => "#00a8a8", "seqId" => 1, "description" => null, "dependencies" => "Wildcard + Album Cover")),         //change tier
            array(array("name" => "Looping GIF", "tier" => "1", "color" => "#agtu78", "seqId" => 1, "description" => null, "dependencies" => "Wildcard + Album Cover")),         //change color
            array(array("name" => "Looping GIF", "tier" => "2", "color" => "#00a8a8", "seqId" => 4, "description" => null, "dependencies" => "Wildcard + Album Cover"))          //change seqId, nothing happens
        );
    }
    
    /**
     * @dataProvider newTierSuccessProvider
     */
    public function testNewTierSuccess($tierData){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->newTier($tierData, $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $expectedTiers = array(
            array("id" => $tiers[0]["id"], "seqId" => 1, "tier" => $tierData["tier"], "treeId" => $treeId, "reward" => $tierData["reward"])
        );
        $this->assertEquals($expectedTiers, $tiers);
    }

    /**
     * @dataProvider invalidNewTierProvider
     */
    public function testNewTierInvalidValuesFail($tierData){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        try {
            $this->skills->newTier($tierData, $courseId);
            $this->fail("PDOException should have been thrown for invalid value on newTier.");

        } catch (\PDOException $e) {
            $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []); 
            $this->assertEmpty($tiers);
        } 
    }

    public function testNewTierInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tierData = array("tier" => "Tier 1", "reward" => 100);

        //When
        $this->skills->newTier($tierData, $courseId + 1);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $this->assertEmpty($tiers);
    }

    public function testNewTierNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tierData = array("tier" => "Tier 1", "reward" => 100);

        //When
        $this->skills->newTier($tierData, null);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $this->assertEmpty($tiers);
    }

    /**
     * @dataProvider editTierSuccessProvider
     */
    public function testEditTierSuccess($tierData){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 350]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "Always the same", "reward" => 0]);
        $tierData["id"] = $tier1;

        //When
        $this->skills->editTier($tierData, $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*" , "id");
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => $tierData["tier"], "reward" => $tierData["reward"]),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 5, "tier" => "Always the same", "reward" => 0)
        );
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testEditTierNullReward(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 350]);
        $tierData = array("id" => $tier, "tier" => "First Tier", "reward" => null);

        //When
        $this->skills->editTier($tierData, $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $expectedTiers = array(
            array("id" => $tier, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 0)
        );
        $this->assertEquals($expectedTiers, $tiers);
        
    }

    /**
     * @dataProvider editTierSuccessProvider
     */
    public function testEditTierTwoCoursesSuccess($tierData){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $tree1 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course1, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 1, "tier" => "First Tier", "reward" => 350]);
        $tierData["id"] = $tier1;

        $course2 = Core::$systemDB->insert("course", ["name" => "Forensics Cyber-Security", "short" => "FCS", "year" => "2020-2021", "color" => "#329da8", "isActive" => 1, "isVisible" => 1]);
        $tree2 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course2, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree2, "seqId" => 1, "tier" => "First Tier", "reward" => 350]);

        //When
        $this->skills->editTier($tierData, $course1);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*" , "id");
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $tree1, "seqId" => 1, "tier" => $tierData["tier"], "reward" => $tierData["reward"]),
            array("id" => $tier2, "treeId" => $tree2, "seqId" => 1, "tier" => "First Tier", "reward" => 350)
        );
        $this->assertEquals($expectedTiers, $tiers);
    }

    /**
     * @dataProvider invalidEditTierProvider
     */
    public function testEditTierInvalidValuesFail($tierData){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 350]);
        $tierData["id"] = $tier;

        try {
            $this->skills->editTier($tierData, $courseId);
            $this->fail("PDOException should have been thrown for invalid value on editTier.");

        } catch (\PDOException $e) {
            $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
            $expectedTiers = array(
                array("id" => $tier, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 350)
            );
            $this->assertEquals($expectedTiers, $tiers);
        } 
    }

    public function testGetTiersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $tiers = $this->skills->getTiers($courseId);

        //Then
        $expectedTiers = array("First Tier", "Second Tier", "");
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testGetTiersWithXpSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        //When
        $tiers = $this->skills->getTiers($courseId, true);

        //Then
        $expectedTiers = array(
            array("seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("seqId" => 2, "tier" => "Second Tier", "reward" => 250),
            array("seqId" => 5, "tier" => "", "reward" => 0)
        );
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testGetTiersTwoCoursesSuccess(){

        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $tree1 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course1, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        $course2 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $tree2 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course2, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree2, "seqId" => 2, "tier" => "Different Second Tier", "reward" => 250]);
        $tier5 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree2, "seqId" => 5, "tier" => " ", "reward" => 0]);
        
        //When
        $tiers = $this->skills->getTiers($course2);

        //Then
        $expectedTiers = array("Different Second Tier", " ");
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testGetTiersNoTiers(){
       
        //Given
       $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
       $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
       //When
       $tiers = $this->skills->getTiers($courseId);

       //Then
       $this->assertEmpty($tiers);
    }
    
    public function testGetTiersNoTree(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
       
        //When
        $tiers = $this->skills->getTiers($courseId);

        //Then
        $this->assertEmpty($tiers);
    }

    public function testGetTiersInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $tiers = $this->skills->getTiers($courseId + 1);

        //Then
        $this->assertEmpty($tiers);
    }

    public function testGetTiersNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $tiers = $this->skills->getTiers(null);

        //Then
        $this->assertEmpty($tiers);
    }

    /**
     * @dataProvider saveMaxSuccessProvider
     */
    public function testSaveMaxRewardSuccess($max){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->saveMaxReward($max, $courseId);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $expectedMaxReward = array(
            array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => $max)
        );
        $this->assertEquals($expectedMaxReward, $maxReward);
    }

    public function testSaveMaxRewardSameValueSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->saveMaxReward(DEFAULT_MAX_TREE_XP, $courseId);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $expectedMaxReward = array(
            array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedMaxReward, $maxReward);
    }

    public function testSaveMaxRewardNullMaxSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->saveMaxReward(null, $courseId);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $expectedMaxReward = array(
            array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => 0)
        );
        $this->assertEquals($expectedMaxReward, $maxReward);
    }

    /**
     * @dataProvider invalidSaveMaxProvider
     */
    public function testSaveMaxRewardInvalidValuesFail($max){

        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        try {
            $this->skills->saveMaxReward($max, $courseId);
            $this->fail("PDOException should have been thrown for invalid value on saveMax.");

        } catch (\PDOException $e) {
            $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
            $expectedMaxReward = array(
                array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
            );
            $this->assertEquals($expectedMaxReward, $maxReward);
        } 
    }

    public function testSaveMaxRewardNoTree(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $this->skills->saveMaxReward(100, $courseId);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $this->assertEmpty($maxReward);
    }

    public function testSaveMaxRewardInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->saveMaxReward(100, $courseId + 1);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $expectedMaxReward = array(
            array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedMaxReward, $maxReward);
    }

    public function testSaveMaxRewardNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $this->skills->saveMaxReward(100, null);

        //Then
        $maxReward = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $expectedMaxReward = array(
            array("id" => $maxReward[0]["id"], "course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP)
        );
        $this->assertEquals($expectedMaxReward, $maxReward);
    }

    /**
     * @dataProvider saveMaxSuccessProvider
     */
    public function testGetMaxRewardSuccess($max){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => $max]);

        //When
        $maxReward = $this->skills->getMaxReward($courseId);

        //Then
        $this->assertEquals($max, $maxReward);
    }

    public function testGetMaxRewardNullRewardSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => null]);

        //When
        $maxReward = $this->skills->getMaxReward($courseId);

        //Then
        $this->assertEquals(0, $maxReward);
    }

    public function testGetMaxRewardInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);

        //When
        $maxReward = $this->skills->getMaxReward($courseId + 1);

        //Then
        $this->assertFalse($maxReward);
    }

    public function testGetMaxRewardNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);

        //When
        $maxReward = $this->skills->getMaxReward(null);

        //Then
        $this->assertFalse($maxReward);
    }
    
    /**
     * @depends testGetTiersWithXpSuccess
     */
    public function testGetTierItemsSuccess(){
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        

        //When
        $tierItems = $this->skills->get_tiers_items($courseId);

        //Then
        $expectedHeader = ['Tier', 'XP'];
        $expectedDisplayAtributes = ['tier', 'reward'];
        $expectedAtributes = array(
            array('name' => "Tier", 'id' => 'tier', 'type' => "text", 'options' => ""),
            array('name' => "XP", 'id' => 'reward', 'type' => "number", 'options' => "")
        );
        $expectedItems = array(
            array("seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("seqId" => 2, "tier" => "Second Tier", "reward" => 250),
            array("seqId" => 5, "tier" => "", "reward" => 0)
        );

        $expectedTierItems = array(
            "listName" => "Tiers", "itemName" => "Tier", "header" => $expectedHeader, 
            "displayAttributes" => $expectedDisplayAtributes, "allAttributes" => $expectedAtributes,
            "items" => $expectedItems
        );

        $this->assertEquals($expectedTierItems, $tierItems);

    }

    /**
     * @depends testGetTiersNoTiers
     */
    public function testGetTierItemsNoTiers(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);

        //When
        $tierItems = $this->skills->get_tiers_items($courseId);

        //Then
        $expectedHeader = ['Tier', 'XP'];
        $expectedDisplayAtributes = ['tier', 'reward'];
        $expectedAtributes = array(
            array('name' => "Tier", 'id' => 'tier', 'type' => "text", 'options' => ""),
            array('name' => "XP", 'id' => 'reward', 'type' => "number", 'options' => "")
        );
        $expectedItems = array();

        $expectedTierItems = array(
            "listName" => "Tiers", "itemName" => "Tier", "header" => $expectedHeader, 
            "displayAttributes" => $expectedDisplayAtributes, "allAttributes" => $expectedAtributes,
            "items" => $expectedItems
        );

        $this->assertEquals($expectedTierItems, $tierItems);
        
    }

    /**
     * @depends testGetTiersInexistingCourse
     */
    public function testGetTierItemsInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        //When
        $tierItems = $this->skills->get_tiers_items($courseId + 1);

        //Then
        $expectedHeader = ['Tier', 'XP'];
        $expectedDisplayAtributes = ['tier', 'reward'];
        $expectedAtributes = array(
            array('name' => "Tier", 'id' => 'tier', 'type' => "text", 'options' => ""),
            array('name' => "XP", 'id' => 'reward', 'type' => "number", 'options' => "")
        );
        $expectedItems = array();

        $expectedTierItems = array(
            "listName" => "Tiers", "itemName" => "Tier", "header" => $expectedHeader, 
            "displayAttributes" => $expectedDisplayAtributes, "allAttributes" => $expectedAtributes,
            "items" => $expectedItems
        );

        $this->assertEquals($expectedTierItems, $tierItems);
    }

    /**
     * @depends testGetTiersNullCourse
     */
    public function testGetTierItemsNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => 100]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        //When
        $tierItems = $this->skills->get_tiers_items(null);

        //Then
        $expectedHeader = ['Tier', 'XP'];
        $expectedDisplayAtributes = ['tier', 'reward'];
        $expectedAtributes = array(
            array('name' => "Tier", 'id' => 'tier', 'type' => "text", 'options' => ""),
            array('name' => "XP", 'id' => 'reward', 'type' => "number", 'options' => "")
        );
        $expectedItems = array();

        $expectedTierItems = array(
            "listName" => "Tiers", "itemName" => "Tier", "header" => $expectedHeader, 
            "displayAttributes" => $expectedDisplayAtributes, "allAttributes" => $expectedAtributes,
            "items" => $expectedItems
        );

        $this->assertEquals($expectedTierItems, $tierItems);
        
    }

    public function testDeleteTierSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $result = $this->skills->deleteTier(array("id" => $tier3, "tier" => ""), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250)
        );

        $this->assertNull($result);
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierTwoCoursesSuccess(){
        
        //Given
        $course1 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $tree1 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course1, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree1, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $course2 = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $tree2 = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $course2, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $tree2, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $this->skills->deleteTier(array("id" => $tier2, "tier" => "Second Tier"), $course1);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $tree1, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier3, "treeId" => $tree2, "seqId" => 5, "tier" => "", "reward" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierWithSkillsFail(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill1"]);

        $this->expectOutputString("This tier has skills. Please delete them first or change their tier.");

        //When
        $this->skills->deleteTier(array("id" => $tier1, "tier" => "First Tier"), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250)
        );

        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierWrongTierNameNoSkills(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $this->skills->deleteTier(array("id" => $tier3, "tier" => "Not the right name"), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250)
        );

        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierWrongTierNameWithSkills(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill1"]);

        //When
        $this->skills->deleteTier(array("id" => $tier1, "tier" => "Not the right name"), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250),
            array("id" => $tier3, "treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0)
        );
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);

        $this->assertEmpty($skills);
        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $this->skills->deleteTier(array("id" => $tier3, "tier" => ""), $courseId + 1);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250),
            array("id" => $tier3, "treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testDeleteTierNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0]);
        
        //When
        $this->skills->deleteTier(array("id" => $tier3, "tier" => ""), null);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100),
            array("id" => $tier2, "treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250),
            array("id" => $tier3, "treeId" => $treeId, "seqId" => 5, "tier" => "", "reward" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
    }

    public function testGetNumberOfSkillsInTierSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);
        $skill1T2 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier($treeId, "First Tier");

        //Then
        $this->assertEquals(2, $nSkills); 
    }

    public function testGetNumberOfSkillsInTierNoSkills(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier($treeId, "Second Tier");

        //Then
        $this->assertEquals(0, $nSkills);
    }

    public function testGetNumberOfSkillsInTierInexistingTier(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier($treeId, "Third Tier");

        //Then
        $this->assertEquals(0, $nSkills);
    }

    public function testGetNumberOfSkillsInTierNullTier(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier($treeId, null);

        //Then
        $this->assertEquals(0, $nSkills);
    }

    public function testGetNumberOfSkillsInTierInexistingTree(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier($treeId + 1, "First Tier");

        //Then
        $this->assertEquals(0, $nSkills);
    }

    public function testGetNumberOfSkillsInTierNullTree(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);

        //When
        $nSkills = $this->skills->getNumberOfSkillsInTier(null, "First Tier");

        //Then
        $this->assertEquals(0, $nSkills);
    }
    
    public function testActiveItemActivateSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);
        $skill1T2 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2"]);

        //When
        $this->skills->toggleItemParam($skill2T1, "isActive");

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("color" => null, "page" => null, "id" => $skill1T1, "isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill2T1, "isActive" => 1, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill1T2, "isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2")
        );

        $this->assertEquals($expectedSkills, $skills);
    }

    public function testActiveItemDeactivateSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);
        $skill1T2 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2"]);

        //When
        $this->skills->toggleItemParam($skill1T1, "isActive");

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("color" => null, "page" => null, "id" => $skill1T1, "isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill2T1, "isActive" => 1, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill1T2, "isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2")
        );

        $this->assertEquals($expectedSkills, $skills);
    }

    public function testActiveItemInexistingSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);
        $skill1T2 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2"]);

        //When
        $this->skills->toggleItemParam($skill1T2 + 1, "isActive");

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("color" => null, "page" => null, "id" => $skill1T1, "isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill2T1, "isActive" => 1, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill1T2, "isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2")
        );

        $this->assertEquals($expectedSkills, $skills);
    }

    public function testActiveItemNullSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "reward" => 100]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["treeId" => $treeId, "seqId" => 2, "tier" => "Second Tier", "reward" => 250]);
        
        $skill1T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"]);
        $skill2T1 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"]);
        $skill1T2 = Core::$systemDB->insert(Skills::TABLE, ["isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2"]);

        //When
        $this->skills->toggleItemParam(null, "isActive");

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("color" => null, "page" => null, "id" => $skill1T1, "isActive" => 0, "treeId" => $treeId, "seqId" => 1, "tier" => "First Tier", "name" => "Skill 1 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill2T1, "isActive" => 0, "treeId" => $treeId, "seqId" => 2, "tier" => "First Tier", "name" => "Skill 2 of tier 1"),
            array("color" => null, "page" => null, "id" => $skill1T2, "isActive" => 1, "treeId" => $treeId, "seqId" => 1, "tier" => "Second Tier", "name" => "Skill 1 of tier 2")
        );

        $this->assertEquals($expectedSkills, $skills);
    }

    /**
     * @dataProvider createFolderForSkillResourcesSuccessProvider
     */
    public function testCreateFolderForSkillResourcesSuccess($skillName, $dir){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        mkdir($folder);
        mkdir($skillsFolder);

        //When
        $this->skills->createFolderForSkillResources($skillName, $courseId);

        //Then
        $this->assertDirectoryExists($skillsFolder . "/" . $dir);
        rmdir($skillsFolder . "/" . $dir);
        rmdir($skillsFolder);
        rmdir($folder);

    }

    /**
     * @dataProvider invalidCreateFolderForSkillResourcesProvider
     */
    public function testCreateFolderForSkillResourcesDirAlreadyExists($originalSkill, $repeatedSkill){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($skillsFolder . "/" . $originalSkill);

        //When
        $this->skills->createFolderForSkillResources($repeatedSkill, $courseId);

        //Then
        $this->assertDirectoryExists($skillsFolder . "/" . $originalSkill);
        rmdir($skillsFolder . "/" . $originalSkill);
        rmdir($skillsFolder);
        rmdir($folder);
    }

    /**
     * @depends testGetNumberOfSkillsInTierSuccess
     * @dataProvider newSkillSuccessNormalDependenciesProvider
     */
    public function testNewSkillNormalDependenciesSuccess($skillData, $color, $seqId){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        //When
        $this->skills->newSkill($skillData, $courseId);

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 2, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 1, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 2, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[4]["id"], "seqId" => $seqId, "name" => $skillData["name"], "color" => $color, "page" => null, "tier" => $skillData["tier"], "treeId" => $treeId, "isActive" => 1)
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skills[4]["id"])
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skill1, "isTier" => 0),
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skill2, "isTier" => 0)
        );

        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @depends testGetNumberOfSkillsInTierSuccess
     */
    public function testNewSkillWildcardDependenciesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $skillData = array("name" => "Looping GIF", "tier" => "3", "color" => "#00c140", "dependencies" => "1 + Course Logo | 1 + 2");
            
        //When
        $this->skills->newSkill($skillData, $courseId);

        //Then
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 2, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 1, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 2, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[4]["id"], "seqId" => 1, "name" => "Looping GIF", "color" => "#00c140", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1)
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skills[4]["id"]),
            array("id" => $dependencies[1]["id"], "superSkillId" => $skills[4]["id"])
        );
        
        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $tier2, "isTier" => 1)
        );

        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @dataProvider newSkillFailProvider
     */
    public function testNewSkillFail($skillData, $missingField){
        
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        try {
            $this->skills->newSkill($skillData, $courseId);
            $this->fail("PDOException should have been thrown for null " . $missingField . " on newSkill.");

        } catch (\PDOException $e) {
            $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
            $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, []);
            $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, []);
                
            $this->assertEmpty($skills);
            $this->assertEmpty($dependencies);
            $this->assertEmpty($skillDependencies);
        } 
    }

    public function testNewSkillNoTree(){
        
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $skillData = array("name" => "Looping GIF", "tier" => "3", "color" => "#00c140", "dependencies" => "");

        try {
            $this->skills->newSkill($skillData, $courseId);
            $this->fail("PDOException should have been thrown on newSkill.");

        } catch (\PDOException $e) {
            $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
            $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, []);
            $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, []);
                
            $this->assertEmpty($skills);
            $this->assertEmpty($dependencies);
            $this->assertEmpty($skillDependencies);
            
            rmdir($skillsFolder);
            rmdir($rulesFolder);
            rmdir($folder);
        } 
    }

    /**
     * @dataProvider editSkillSuccessProvider
     */
    public function testEditSkillKeepDependenciesSuccess($skillData){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData["id"] = $skill5;

        //When
        $result = $this->skills->editSkill($skillData, $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => $skillData["name"], "color" => $skillData["color"], "page" => null, "tier" => $skillData["tier"], "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill6),
            array("id" => $dependency2, "superSkillId" => $skill6),
            array("id" => $dependency3, "superSkillId" => $skill5),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");
        $this->assertFileExists($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");

        unlink($rulesFolder . "/1 - skills.txt");
        unlink($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);

    }

    public function testEditSkillAddDependenciesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData = array("id" => $skill5, "name" => "Looping GIF", "tier" => "2", "color" => "#00a8a8", "seqId" => 4, "description" => null, "dependencies" => "Wildcard + Album Cover | Course Logo + Movie Poster");

        //When
        $result = $this->skills->editSkill($skillData, $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => $skillData["name"], "color" => $skillData["color"], "page" => null, "tier" => $skillData["tier"], "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill5),
            array("id" => $dependencies[1]["id"], "superSkillId" => $skill5),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skill4, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");
        $this->assertFileExists($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");

        unlink($rulesFolder . "/1 - skills.txt");
        unlink($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);

    }

    public function testEditSkillRemoveDependenciesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData = array("id" => $skill5, "name" => "Looping GIF", "tier" => "2", "color" => "#00a8a8", "seqId" => 4, "description" => null, "dependencies" => null);

        //When
        $result = $this->skills->editSkill($skillData, $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => $skillData["name"], "color" => $skillData["color"], "page" => null, "tier" => $skillData["tier"], "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency2, "superSkillId" => $skill6)
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");
        $this->assertFileExists($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");

        unlink($rulesFolder . "/1 - skills.txt");
        unlink($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);

    }
    
    public function testEditSkillInexistingSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData = array("id" => $skill6 + 1, "name" => "Loop", "tier" => "2", "color" => "#aaaaaa", "seqId" => 4, "description" => null, "dependencies" => null);

        //When
        $result = $this->skills->editSkill($skillData, $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill5),
            array("id" => $dependency2, "superSkillId" => $skill6)
            
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0),
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testEditSkillEmptyStringsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);
        
        $skillData = array("id" => $skill5, "name" => "", "tier" => "2", "color" => "", "seqId" => 4, "description" => "", "dependencies" => "");

        //When
        $result = $this->skills->editSkill($skillData, $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => $skillData["name"], "color" => $skillData["color"], "page" => null, "tier" => $skillData["tier"], "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency2, "superSkillId" => $skill6)
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");
        $this->assertFileExists($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");

        unlink($rulesFolder . "/1 - skills.txt");
        unlink($skillsFolder . "/" . str_replace(' ', '', $skillData["name"]) . ".html");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testEditSkillWrongCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData = array("id" => $skill5, "name" => "Loop", "tier" => "2", "color" => "#aaaaaa", "seqId" => 4, "description" => null, "dependencies" => null);

        //When
        $result = $this->skills->editSkill($skillData, $courseId + 1);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill5),
            array("id" => $dependency2, "superSkillId" => $skill6)
            
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0),
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testEditSkillNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill6]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0]);

        $skillData = array("id" => $skill5, "name" => "Loop", "tier" => "2", "color" => "#aaaaaa", "seqId" => 4, "description" => null, "dependencies" => null);

        //When
        $result = $this->skills->editSkill($skillData, null);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill5),
            array("id" => $dependency2, "superSkillId" => $skill6)
            
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill3, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill3, "isTier" => 0),
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @depends testNewSkillWildcardDependenciesSuccess
     */
    public function testImportItemsNoHeaderNoReplaceSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $file = "Wildcard;Pixel Art;;#ddaa00;300
                Wildcard;Fake Speech;;#aadd00;300
                Wildcard;reTrailer;;#00aa88;300
                Wildcard;Morphing;;#FFFF00;300
                1;Course Logo;;#2773ed;150
                1;Album Cover;;#ff76bc;150
                1;Movie Poster;;#00c140;150
                1;Podcast;;#4617b5;150
                1;Reporter;;#c1004f;150
                1;Audiobook;;#b11d01;150
                1;Radio Commercial;;#78ba50;150
                1;Looping GIF;;#00a8a8;150
                2;Publicist;Radio Commercial+Movie Poster;#008387;300
                2;Course Image;Album Cover + Movie Poster;#006ac0;300
                2;Alien Invasions;Movie Poster+Looping GIF;#599be5;300
                2;reMIDI;Album Cover+Audiobook;#7200ad;300
                2;Negative Space;Course Logo+Album Cover;#ff76bc;300
                2;Doppelganger;Reporter+Audiobook;#aa76bc;300
                2;Cinemagraph;Looping GIF+Reporter;#66abaa;300
                2;Flawless Duet;Podcast+Radio Commercial;#ae113e;300
                3;Stop Motion;Doppelganger+Alien Invasions|Wildcard+Doppelganger|Wildcard+Alien Invasions;#78ba00;700
                3;Foley;reMIDI+Flawless Duet|Wildcard+reMIDI|Wildcard+Flawless Duet;#98ba80;700
                3;Kinetic;Course Image+Negative Space|Wildcard+Negative Space|Wildcard+Course Image;#20aeff;700
                3;Music Mashup;reMIDI+Cinemagraph|Wildcard+reMIDI|Wildcard+Cinemagraph;#2773ed;700
                3;Cartoonist;Flawless Duet+Alien Invasions|Publicist+Negative Space;#aa3fff;700
                3;Scene Reshooting;Doppelganger+Cinemagraph|Wildcard+Doppelganger|Wildcard+Cinemagraph;#ff76bc;700
                3;Animated Publicist;Publicist+Course Image|Wildcard+Publicist|Wildcard+Course Image;#4617b5;700
                4;Director;Stop Motion+Foley|Cartoonist+Scene Reshooting;#ff76bc;1500
                4;Audio Visualizer;Kinetic+Music Mashup|Cartoonist+Animated Publicist;#00a8a8;1500";

        //When
        $newItems = $this->skills->importItems($file, false);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tiers[0]["id"], "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tiers[1]["id"], "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tiers[2]["id"], "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
            array("id" => $tiers[3]["id"], "seqId" => 4, "treeId" => $treeId, "tier" => "3", "reward" => 700),
            array("id" => $tiers[4]["id"], "seqId" => 5, "treeId" => $treeId, "tier" => "4", "reward" => 1500)
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skills[0]["id"], "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[1]["id"], "seqId" => 2, "name" => "Fake Speech", "color" => "#aadd00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[2]["id"], "seqId" => 3, "name" => "reTrailer", "color" => "#00aa88", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[3]["id"], "seqId" => 4, "name" => "Morphing", "color" => "#FFFF00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[4]["id"], "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[5]["id"], "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[6]["id"], "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[7]["id"], "seqId" => 4, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[8]["id"], "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[9]["id"], "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[10]["id"], "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[11]["id"], "seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[12]["id"], "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[13]["id"], "seqId" => 2, "name" => "Course Image", "color" => "#006ac0", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[14]["id"], "seqId" => 3, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[15]["id"], "seqId" => 4, "name" => "reMIDI", "color" => "#7200ad", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[16]["id"], "seqId" => 5, "name" => "Negative Space", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[17]["id"], "seqId" => 6, "name" => "Doppelganger", "color" => "#aa76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[18]["id"], "seqId" => 7, "name" => "Cinemagraph", "color" => "#66abaa", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[19]["id"], "seqId" => 8, "name" => "Flawless Duet", "color" => "#ae113e", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[20]["id"], "seqId" => 1, "name" => "Stop Motion", "color" => "#78ba00", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[21]["id"], "seqId" => 2, "name" => "Foley", "color" => "#98ba80", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[22]["id"], "seqId" => 3, "name" => "Kinetic", "color" => "#20aeff", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[23]["id"], "seqId" => 4, "name" => "Music Mashup", "color" => "#2773ed", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[24]["id"], "seqId" => 5, "name" => "Cartoonist", "color" => "#aa3fff", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[25]["id"], "seqId" => 6, "name" => "Scene Reshooting", "color" => "#ff76bc", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[26]["id"], "seqId" => 7, "name" => "Animated Publicist", "color"=> "#4617b5", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[27]["id"], "seqId" => 1, "name" => "Director", "color" => "#ff76bc", "page" => null, "tier" => "4", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[28]["id"], "seqId" => 2, "name" => "Audio Visualizer", "color" => "#00a8a8", "page" => null, "tier" => "4", "treeId" => $treeId, "isActive" => 1)
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skills[12]["id"]),
            array("id" => $dependencies[1]["id"], "superSkillId" => $skills[13]["id"]),
            array("id" => $dependencies[2]["id"], "superSkillId" => $skills[14]["id"]),
            array("id" => $dependencies[3]["id"], "superSkillId" => $skills[15]["id"]),
            array("id" => $dependencies[4]["id"], "superSkillId" => $skills[16]["id"]),
            array("id" => $dependencies[5]["id"], "superSkillId" => $skills[17]["id"]),
            array("id" => $dependencies[6]["id"], "superSkillId" => $skills[18]["id"]),
            array("id" => $dependencies[7]["id"], "superSkillId" => $skills[19]["id"]),
            array("id" => $dependencies[8]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[9]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[10]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[11]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[12]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[13]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[14]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[15]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[16]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[17]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[18]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[19]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[20]["id"], "superSkillId" => $skills[24]["id"]),
            array("id" => $dependencies[21]["id"], "superSkillId" => $skills[24]["id"]),
            array("id" => $dependencies[22]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[23]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[24]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[25]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[26]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[27]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[28]["id"], "superSkillId" => $skills[27]["id"]),
            array("id" => $dependencies[29]["id"], "superSkillId" => $skills[27]["id"]),
            array("id" => $dependencies[30]["id"], "superSkillId" => $skills[28]["id"]),
            array("id" => $dependencies[31]["id"], "superSkillId" => $skills[28]["id"])
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skills[10]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[9]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $skills[4]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $skills[8]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $skills[9]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[6]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[6]["id"], "normalSkillId" => $skills[8]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[7]["id"], "normalSkillId" => $skills[7]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[7]["id"], "normalSkillId" => $skills[10]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[8]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[8]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[9]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[9]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[10]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[10]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[11]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[11]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[12]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[12]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[13]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[13]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[14]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[14]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[15]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[15]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[16]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[16]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[17]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[17]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[18]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[18]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[19]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[19]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[20]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[20]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[21]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[21]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[22]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[22]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[23]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[23]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[24]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[24]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[25]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[25]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[26]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[26]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[27]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[27]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[28]["id"], "normalSkillId" => $skills[20]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[28]["id"], "normalSkillId" => $skills[21]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[29]["id"], "normalSkillId" => $skills[24]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[29]["id"], "normalSkillId" => $skills[25]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[30]["id"], "normalSkillId" => $skills[22]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[30]["id"], "normalSkillId" => $skills[23]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[31]["id"], "normalSkillId" => $skills[24]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[31]["id"], "normalSkillId" => $skills[26]["id"], "isTier" => 0)
        );

        $this->assertEquals(29, $newItems);
        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @depends testNewSkillWildcardDependenciesSuccess
     */
    public function testImportItemsWithHeaderNoReplaceSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $file = "tier;name;dependencies;color;xp
                Wildcard;Pixel Art;;#ddaa00;300
                Wildcard;Fake Speech;;#aadd00;300
                Wildcard;reTrailer;;#00aa88;300
                Wildcard;Morphing;;#FFFF00;300
                1;Course Logo;;#2773ed;150
                1;Album Cover;;#ff76bc;150
                1;Movie Poster;;#00c140;150
                1;Podcast;;#4617b5;150
                1;Reporter;;#c1004f;150
                1;Audiobook;;#b11d01;150
                1;Radio Commercial;;#78ba50;150
                1;Looping GIF;;#00a8a8;150
                2;Publicist;Radio Commercial+Movie Poster;#008387;300
                2;Course Image;Album Cover + Movie Poster;#006ac0;300
                2;Alien Invasions;Movie Poster+Looping GIF;#599be5;300
                2;reMIDI;Album Cover+Audiobook;#7200ad;300
                2;Negative Space;Course Logo+Album Cover;#ff76bc;300
                2;Doppelganger;Reporter+Audiobook;#aa76bc;300
                2;Cinemagraph;Looping GIF+Reporter;#66abaa;300
                2;Flawless Duet;Podcast+Radio Commercial;#ae113e;300
                3;Stop Motion;Doppelganger+Alien Invasions|Wildcard+Doppelganger|Wildcard+Alien Invasions;#78ba00;700
                3;Foley;reMIDI+Flawless Duet|Wildcard+reMIDI|Wildcard+Flawless Duet;#98ba80;700
                3;Kinetic;Course Image+Negative Space|Wildcard+Negative Space|Wildcard+Course Image;#20aeff;700
                3;Music Mashup;reMIDI+Cinemagraph|Wildcard+reMIDI|Wildcard+Cinemagraph;#2773ed;700
                3;Cartoonist;Flawless Duet+Alien Invasions|Publicist+Negative Space;#aa3fff;700
                3;Scene Reshooting;Doppelganger+Cinemagraph|Wildcard+Doppelganger|Wildcard+Cinemagraph;#ff76bc;700
                3;Animated Publicist;Publicist+Course Image|Wildcard+Publicist|Wildcard+Course Image;#4617b5;700
                4;Director;Stop Motion+Foley|Cartoonist+Scene Reshooting;#ff76bc;1500
                4;Audio Visualizer;Kinetic+Music Mashup|Cartoonist+Animated Publicist;#00a8a8;1500";

        //When
        $newItems = $this->skills->importItems($file, false);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tiers[0]["id"], "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tiers[1]["id"], "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tiers[2]["id"], "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
            array("id" => $tiers[3]["id"], "seqId" => 4, "treeId" => $treeId, "tier" => "3", "reward" => 700),
            array("id" => $tiers[4]["id"], "seqId" => 5, "treeId" => $treeId, "tier" => "4", "reward" => 1500)
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skills[0]["id"], "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[1]["id"], "seqId" => 2, "name" => "Fake Speech", "color" => "#aadd00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[2]["id"], "seqId" => 3, "name" => "reTrailer", "color" => "#00aa88", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[3]["id"], "seqId" => 4, "name" => "Morphing", "color" => "#FFFF00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[4]["id"], "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[5]["id"], "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[6]["id"], "seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[7]["id"], "seqId" => 4, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[8]["id"], "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[9]["id"], "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[10]["id"], "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[11]["id"], "seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[12]["id"], "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[13]["id"], "seqId" => 2, "name" => "Course Image", "color" => "#006ac0", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[14]["id"], "seqId" => 3, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[15]["id"], "seqId" => 4, "name" => "reMIDI", "color" => "#7200ad", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[16]["id"], "seqId" => 5, "name" => "Negative Space", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[17]["id"], "seqId" => 6, "name" => "Doppelganger", "color" => "#aa76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[18]["id"], "seqId" => 7, "name" => "Cinemagraph", "color" => "#66abaa", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[19]["id"], "seqId" => 8, "name" => "Flawless Duet", "color" => "#ae113e", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[20]["id"], "seqId" => 1, "name" => "Stop Motion", "color" => "#78ba00", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[21]["id"], "seqId" => 2, "name" => "Foley", "color" => "#98ba80", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[22]["id"], "seqId" => 3, "name" => "Kinetic", "color" => "#20aeff", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[23]["id"], "seqId" => 4, "name" => "Music Mashup", "color" => "#2773ed", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[24]["id"], "seqId" => 5, "name" => "Cartoonist", "color" => "#aa3fff", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[25]["id"], "seqId" => 6, "name" => "Scene Reshooting", "color" => "#ff76bc", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[26]["id"], "seqId" => 7, "name" => "Animated Publicist", "color"=> "#4617b5", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[27]["id"], "seqId" => 1, "name" => "Director", "color" => "#ff76bc", "page" => null, "tier" => "4", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[28]["id"], "seqId" => 2, "name" => "Audio Visualizer", "color" => "#00a8a8", "page" => null, "tier" => "4", "treeId" => $treeId, "isActive" => 1)
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skills[12]["id"]),
            array("id" => $dependencies[1]["id"], "superSkillId" => $skills[13]["id"]),
            array("id" => $dependencies[2]["id"], "superSkillId" => $skills[14]["id"]),
            array("id" => $dependencies[3]["id"], "superSkillId" => $skills[15]["id"]),
            array("id" => $dependencies[4]["id"], "superSkillId" => $skills[16]["id"]),
            array("id" => $dependencies[5]["id"], "superSkillId" => $skills[17]["id"]),
            array("id" => $dependencies[6]["id"], "superSkillId" => $skills[18]["id"]),
            array("id" => $dependencies[7]["id"], "superSkillId" => $skills[19]["id"]),
            array("id" => $dependencies[8]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[9]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[10]["id"], "superSkillId" => $skills[20]["id"]),
            array("id" => $dependencies[11]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[12]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[13]["id"], "superSkillId" => $skills[21]["id"]),
            array("id" => $dependencies[14]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[15]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[16]["id"], "superSkillId" => $skills[22]["id"]),
            array("id" => $dependencies[17]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[18]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[19]["id"], "superSkillId" => $skills[23]["id"]),
            array("id" => $dependencies[20]["id"], "superSkillId" => $skills[24]["id"]),
            array("id" => $dependencies[21]["id"], "superSkillId" => $skills[24]["id"]),
            array("id" => $dependencies[22]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[23]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[24]["id"], "superSkillId" => $skills[25]["id"]),
            array("id" => $dependencies[25]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[26]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[27]["id"], "superSkillId" => $skills[26]["id"]),
            array("id" => $dependencies[28]["id"], "superSkillId" => $skills[27]["id"]),
            array("id" => $dependencies[29]["id"], "superSkillId" => $skills[27]["id"]),
            array("id" => $dependencies[30]["id"], "superSkillId" => $skills[28]["id"]),
            array("id" => $dependencies[31]["id"], "superSkillId" => $skills[28]["id"])
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skills[10]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skills[6]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[9]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $skills[4]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $skills[5]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $skills[8]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $skills[9]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[6]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[6]["id"], "normalSkillId" => $skills[8]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[7]["id"], "normalSkillId" => $skills[7]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[7]["id"], "normalSkillId" => $skills[10]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[8]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[8]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[9]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[9]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[10]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[10]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[11]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[11]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[12]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[12]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[13]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[13]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[14]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[14]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[15]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[15]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[16]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[16]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[17]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[17]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[18]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[18]["id"], "normalSkillId" => $skills[15]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[19]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[19]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[20]["id"], "normalSkillId" => $skills[19]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[20]["id"], "normalSkillId" => $skills[14]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[21]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[21]["id"], "normalSkillId" => $skills[16]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[22]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[22]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[23]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[23]["id"], "normalSkillId" => $skills[17]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[24]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[24]["id"], "normalSkillId" => $skills[18]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[25]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[25]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[26]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[26]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[27]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[27]["id"], "normalSkillId" => $skills[13]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[28]["id"], "normalSkillId" => $skills[20]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[28]["id"], "normalSkillId" => $skills[21]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[29]["id"], "normalSkillId" => $skills[24]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[29]["id"], "normalSkillId" => $skills[25]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[30]["id"], "normalSkillId" => $skills[22]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[30]["id"], "normalSkillId" => $skills[23]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[31]["id"], "normalSkillId" => $skills[24]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[31]["id"], "normalSkillId" => $skills[26]["id"], "isTier" => 0)
        );

        $this->assertEquals(29, $newItems);
        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @depends testEditSkillKeepDependenciesSuccess
     */
    public function testImportItemsReplaceSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier5 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 5, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        
        $file = "tier;name;dependencies;color;xp
                Wildcard;Pixel Art;;#ddaa00;400
                Wildcard;Morphing;;#FFFF00;400
                1;Course Logo;;#2773ed;150
                1;Album Cover;;#ff76bc;150
                1;Movie Poster;;#00c140;150
                1;Podcast;;#4617b5;150
                1;Reporter;;#c1004f;150
                1;Audiobook;;#b11d01;150
                1;Radio Commercial;;#78ba50;150
                1;Looping GIF;;#00a8a8;150
                2;Publicist;Radio Commercial+Movie Poster;#008387;300
                2;Alien Invasions;Movie Poster+Looping GIF;#599be5;300
                2;Doppelganger;Reporter+Audiobook;#aa76bc;300
                3;Stop Motion;Doppelganger+Alien Invasions|Wildcard+Doppelganger|Wildcard+Alien Invasions;#78ba00;700";

        //When
        $newItems = $this->skills->importItems($file);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
            array("id" => $tier5, "seqId" => 5, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500),
            array("id" => $tiers[4]["id"], "seqId" => 5, "treeId" => $treeId, "tier" => "3", "reward" => 700)
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[10]["id"], "seqId" => 2, "name" => "Morphing", "color" => "#FFFF00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[11]["id"], "seqId" => 2, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[12]["id"], "seqId" => 3, "name" => "Doppelganger", "color" => "#aa76bc", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[13]["id"], "seqId" => 1, "name" => "Stop Motion", "color" => "#78ba00", "page" => null, "tier" => "3", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skill10),
            array("id" => $dependencies[1]["id"], "superSkillId" => $skills[11]["id"]),
            array("id" => $dependencies[2]["id"], "superSkillId" => $skills[12]["id"]),
            array("id" => $dependencies[3]["id"], "superSkillId" => $skills[13]["id"]),
            array("id" => $dependencies[4]["id"], "superSkillId" => $skills[13]["id"]),
            array("id" => $dependencies[5]["id"], "superSkillId" => $skills[13]["id"]),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skill4, "isTier" => 0),
            array("dependencyId" => $dependencies[1]["id"], "normalSkillId" => $skill9, "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependencies[2]["id"], "normalSkillId" => $skill7, "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[3]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[4]["id"], "normalSkillId" => $skills[12]["id"], "isTier" => 0),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $tiers[0]["id"], "isTier" => 1),
            array("dependencyId" => $dependencies[5]["id"], "normalSkillId" => $skills[11]["id"], "isTier" => 0),
        );

        $this->assertEquals(4, $newItems);
        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");
        $this->assertFileExists($skillsFolder . "/AlbumCover.html");
        $this->assertFileExists($skillsFolder . "/Audiobook.html");
        $this->assertFileExists($skillsFolder . "/CourseLogo.html");
        $this->assertFileExists($skillsFolder . "/LoopingGIF.html");
        $this->assertFileExists($skillsFolder . "/MoviePoster.html");
        $this->assertFileExists($skillsFolder . "/PixelArt.html");
        $this->assertFileExists($skillsFolder . "/Podcast.html");
        $this->assertFileExists($skillsFolder . "/RadioCommercial.html");
        $this->assertFileExists($skillsFolder . "/Reporter.html");
        $this->assertFileExists($skillsFolder . "/Publicist.html");

        unlink($rulesFolder . "/1 - skills.txt");
        unlink($skillsFolder . "/AlbumCover.html");
        unlink($skillsFolder . "/Audiobook.html");
        unlink($skillsFolder . "/CourseLogo.html");
        unlink($skillsFolder . "/LoopingGIF.html");
        unlink($skillsFolder . "/MoviePoster.html");
        unlink($skillsFolder . "/PixelArt.html");
        unlink($skillsFolder . "/Podcast.html");
        unlink($skillsFolder . "/RadioCommercial.html");
        unlink($skillsFolder . "/Reporter.html");
        unlink($skillsFolder . "/Publicist.html");
        
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @depends testNewSkillWildcardDependenciesSuccess
     */
    public function testImportItemsMissingDependencySkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $file = "tier;name;dependencies;color;xp
        1;Course Logo;;#2773ed;150
        1;Album Cover;;#ff76bc;150
        1;Audiobook;;#b11d01;150
        1;Radio Commercial;;#78ba50;150
        1;Looping GIF;;#00a8a8;150
        2;Publicist;Book Cover+Radio Commercial;#008387;300";

        $this->expectOutputString("The skill Book Cover does not exist");
        //When
        $newItems = $this->skills->importItems($file);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tiers[0]["id"], "seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tiers[1]["id"], "seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skills[0]["id"], "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[1]["id"], "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[2]["id"], "seqId" => 3, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[3]["id"], "seqId" => 4, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[4]["id"], "seqId" => 5, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skills[5]["id"], "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1)
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependencies[0]["id"], "superSkillId" => $skills[5]["id"]),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependencies[0]["id"], "normalSkillId" => $skills[3]["id"], "isTier" => 0)
        );

        $this->assertEquals(6, $newItems);
        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    /**
     * @dataProvider invalidImportFileProvider
     */
    public function testImportItemsInvalidFile($file){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $file = "";

        //When
        $this->skills->importItems($file, false);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, []);
        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, []);
            
        $this->assertEmpty($tiers);
        $this->assertEmpty($skills);
        $this->assertEmpty($dependencies);
        $this->assertEmpty($skillDependencies);
        $this->assertFileDoesNotExist($rulesFolder . "/1 - skills.txt");

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testExportItemsSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "3", "reward" => 700]);
        $tier5 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 5, "treeId" => $treeId, "tier" => "4", "reward" => 1500]);
        
        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Fake Speech", "color" => "#aadd00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "reTrailer", "color" => "#00aa88", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Morphing", "color" => "#FFFF00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill11 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill12 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill13 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill14 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Course Image", "color" => "#006ac0", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill15 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill16 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "reMIDI", "color" => "#7200ad", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill17 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Negative Space", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill18 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Doppelganger", "color" => "#aa76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill19 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Cinemagraph", "color" => "#66abaa", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill20 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Flawless Duet", "color" => "#ae113e", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill21 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Stop Motion", "color" => "#78ba00", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill22 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Foley", "color" => "#98ba80", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill23 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Kinetic", "color" => "#20aeff", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill24 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Music Mashup", "color" => "#2773ed", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill25 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Cartoonist", "color" => "#aa3fff", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill26 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Scene Reshooting", "color" => "#ff76bc", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill27 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Animated Publicist", "color"=> "#4617b5", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill28 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Director", "color" => "#ff76bc", "page" => null, "tier" => "4", "treeId" => $treeId]);
        $skill29 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audio Visualizer", "color" => "#00a8a8", "page" => null, "tier" => "4", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill13]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill14]);
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill15]);
        $dependency4 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill16]);
        $dependency5 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill17]);
        $dependency6 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill18]);
        $dependency7 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill19]);
        $dependency8 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill20]);
        $dependency9 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill21]);
        $dependency10 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill21]);
        $dependency11 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill21]);
        $dependency12 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill22]);
        $dependency13 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill22]);
        $dependency14 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill22]);
        $dependency15 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill23]);
        $dependency16 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill23]);
        $dependency17 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill23]);
        $dependency18 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill24]);
        $dependency19 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill24]);
        $dependency20 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill24]);
        $dependency21 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill25]);
        $dependency22 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill25]);
        $dependency23 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill26]);
        $dependency24 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill26]);
        $dependency25 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill26]);
        $dependency26 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill27]);
        $dependency27 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill27]);
        $dependency28 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill27]);
        $dependency29 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill28]);
        $dependency30 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill28]);
        $dependency31 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill29]);
        $dependency32 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill29]);

        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill11, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill12, "isTier" => 0]);
        $skillDependency7 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency8 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill10, "isTier" => 0]);
        $skillDependency9 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill5, "isTier" => 0]);
        $skillDependency10 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency11 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency6, "normalSkillId" => $skill9, "isTier" => 0]);
        $skillDependency12 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency6, "normalSkillId" => $skill10, "isTier" => 0]);
        $skillDependency13 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency7, "normalSkillId" => $skill12, "isTier" => 0]);
        $skillDependency14 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency7, "normalSkillId" => $skill9, "isTier" => 0]);
        $skillDependency15 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency8, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency16 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency8, "normalSkillId" => $skill11, "isTier" => 0]);
        $skillDependency17 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency9, "normalSkillId" => $skill18, "isTier" => 0]);
        $skillDependency18 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency9, "normalSkillId" => $skill15, "isTier" => 0]);
        $skillDependency19 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency10, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency20 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency10, "normalSkillId" => $skill18, "isTier" => 0]);
        $skillDependency21 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency11, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency22 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency11, "normalSkillId" => $skill15, "isTier" => 0]);
        $skillDependency23 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency12, "normalSkillId" => $skill16, "isTier" => 0]);
        $skillDependency24 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency12, "normalSkillId" => $skill20, "isTier" => 0]);
        $skillDependency25 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency13, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency26 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency13, "normalSkillId" => $skill16, "isTier" => 0]);
        $skillDependency27 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency14, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency28 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency14, "normalSkillId" => $skill20, "isTier" => 0]);
        $skillDependency29 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency15, "normalSkillId" => $skill14, "isTier" => 0]);
        $skillDependency30 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency15, "normalSkillId" => $skill17, "isTier" => 0]);
        $skillDependency31 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency16, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency32 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency16, "normalSkillId" => $skill17, "isTier" => 0]);
        $skillDependency33 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency17, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency34 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency17, "normalSkillId" => $skill14, "isTier" => 0]);
        $skillDependency35 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency18, "normalSkillId" => $skill16, "isTier" => 0]);
        $skillDependency36 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency18, "normalSkillId" => $skill19, "isTier" => 0]);
        $skillDependency37 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency19, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency38 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency19, "normalSkillId" => $skill16, "isTier" => 0]);
        $skillDependency39 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency20, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency40 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency20, "normalSkillId" => $skill19, "isTier" => 0]);
        $skillDependency41 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency21, "normalSkillId" => $skill20, "isTier" => 0]);
        $skillDependency42 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency21, "normalSkillId" => $skill15, "isTier" => 0]);
        $skillDependency43 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency22, "normalSkillId" => $skill13, "isTier" => 0]);
        $skillDependency44 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency22, "normalSkillId" => $skill17, "isTier" => 0]);
        $skillDependency45 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency23, "normalSkillId" => $skill18, "isTier" => 0]);
        $skillDependency46 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency23, "normalSkillId" => $skill19, "isTier" => 0]);
        $skillDependency47 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency24, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency48 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency24, "normalSkillId" => $skill18, "isTier" => 0]);
        $skillDependency49 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency25, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency50 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency25, "normalSkillId" => $skill19, "isTier" => 0]);
        $skillDependency51 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency26, "normalSkillId" => $skill13, "isTier" => 0]);
        $skillDependency52 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency26, "normalSkillId" => $skill14, "isTier" => 0]);
        $skillDependency53 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency27, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency54 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency27, "normalSkillId" => $skill13, "isTier" => 0]);
        $skillDependency55 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency28, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency56 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency28, "normalSkillId" => $skill14, "isTier" => 0]);
        $skillDependency57 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency29, "normalSkillId" => $skill21, "isTier" => 0]);
        $skillDependency58 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency29, "normalSkillId" => $skill22, "isTier" => 0]);
        $skillDependency59 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency30, "normalSkillId" => $skill25, "isTier" => 0]);
        $skillDependency60 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency30, "normalSkillId" => $skill26, "isTier" => 0]);
        $skillDependency61 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency31, "normalSkillId" => $skill23, "isTier" => 0]);
        $skillDependency62 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency31, "normalSkillId" => $skill24, "isTier" => 0]);
        $skillDependency63 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency32, "normalSkillId" => $skill25, "isTier" => 0]);
        $skillDependency64 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency32, "normalSkillId" => $skill27, "isTier" => 0]);
    
        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $expectedFile .= "Wildcard;Pixel Art;;#ddaa00;300\n";
        $expectedFile .= "Wildcard;Fake Speech;;#aadd00;300\n";
        $expectedFile .= "Wildcard;reTrailer;;#00aa88;300\n";
        $expectedFile .= "Wildcard;Morphing;;#FFFF00;300\n";
        $expectedFile .= "1;Course Logo;;#2773ed;150\n";
        $expectedFile .= "1;Album Cover;;#ff76bc;150\n";
        $expectedFile .= "1;Movie Poster;;#00c140;150\n";
        $expectedFile .= "1;Podcast;;#4617b5;150\n";
        $expectedFile .= "1;Reporter;;#c1004f;150\n";
        $expectedFile .= "1;Audiobook;;#b11d01;150\n";
        $expectedFile .= "1;Radio Commercial;;#78ba50;150\n";
        $expectedFile .= "1;Looping GIF;;#00a8a8;150\n";
        $expectedFile .= "2;Publicist;Movie Poster + Radio Commercial;#008387;300\n";
        $expectedFile .= "2;Course Image;Album Cover + Movie Poster;#006ac0;300\n";
        $expectedFile .= "2;Alien Invasions;Movie Poster + Looping GIF;#599be5;300\n";
        $expectedFile .= "2;reMIDI;Album Cover + Audiobook;#7200ad;300\n";
        $expectedFile .= "2;Negative Space;Course Logo + Album Cover;#ff76bc;300\n";
        $expectedFile .= "2;Doppelganger;Reporter + Audiobook;#aa76bc;300\n";
        $expectedFile .= "2;Cinemagraph;Reporter + Looping GIF;#66abaa;300\n";
        $expectedFile .= "2;Flawless Duet;Podcast + Radio Commercial;#ae113e;300\n";
        $expectedFile .= "3;Stop Motion;Alien Invasions + Doppelganger |  Wildcard + Doppelganger |  Wildcard + Alien Invasions;#78ba00;700\n";
        $expectedFile .= "3;Foley;reMIDI + Flawless Duet |  Wildcard + reMIDI |  Wildcard + Flawless Duet;#98ba80;700\n";
        $expectedFile .= "3;Kinetic;Course Image + Negative Space |  Wildcard + Negative Space |  Wildcard + Course Image;#20aeff;700\n";
        $expectedFile .= "3;Music Mashup;reMIDI + Cinemagraph |  Wildcard + reMIDI |  Wildcard + Cinemagraph;#2773ed;700\n";
        $expectedFile .= "3;Cartoonist;Alien Invasions + Flawless Duet |  Publicist + Negative Space;#aa3fff;700\n";
        $expectedFile .= "3;Scene Reshooting;Doppelganger + Cinemagraph |  Wildcard + Doppelganger |  Wildcard + Cinemagraph;#ff76bc;700\n";
        $expectedFile .= "3;Animated Publicist;Publicist + Course Image |  Wildcard + Publicist |  Wildcard + Course Image;#4617b5;700\n";
        $expectedFile .= "4;Director;Stop Motion + Foley |  Cartoonist + Scene Reshooting;#ff76bc;1500\n";
        $expectedFile .= "4;Audio Visualizer;Kinetic + Music Mashup |  Cartoonist + Animated Publicist;#00a8a8;1500";

        $this->assertIsArray($result);
        $this->assertEquals("Skills - Produção de Conteúdo Multimédia", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testExportItemsNoTreeSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $this->assertIsArray($result);
        $this->assertEquals("Skills - Multimedia Content Production", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsNoTiersSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $this->assertIsArray($result);
        $this->assertEquals("Skills - Multimedia Content Production", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsNoSkillsSuccess(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $this->assertIsArray($result);
        $this->assertEquals("Skills - Multimedia Content Production", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);
    }

    public function testExportItemsInexistingCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill11 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill12 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill13 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill14 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Course Image", "color" => "#006ac0", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill15 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill16 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "reMIDI", "color" => "#7200ad", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill17 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Negative Space", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill13]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill14]);
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill15]);
        $dependency4 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill16]);
        $dependency5 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill17]);

        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill11, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill12, "isTier" => 0]);
        $skillDependency7 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency8 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill10, "isTier" => 0]);
        $skillDependency9 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill5, "isTier" => 0]);
        $skillDependency10 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill6, "isTier" => 0]);
    
        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $this->assertIsArray($result);
        $this->assertEquals("Skills - ", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testExportItemsNullCourse(){

        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill11 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill12 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill13 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill14 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Course Image", "color" => "#006ac0", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill15 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Alien Invasions", "color" => "#599be5", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill16 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "reMIDI", "color" => "#7200ad", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill17 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Negative Space", "color" => "#ff76bc", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill13]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill14]);
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill15]);
        $dependency4 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill16]);
        $dependency5 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill17]);

        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill11, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill7, "isTier" => 0]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill12, "isTier" => 0]);
        $skillDependency7 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency8 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill10, "isTier" => 0]);
        $skillDependency9 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill5, "isTier" => 0]);
        $skillDependency10 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency5, "normalSkillId" => $skill6, "isTier" => 0]);
    
        //When
        $result = $this->skills->exportItems();

        //Then
        $expectedFile = "tier;name;dependencies;color;xp\n";
        $this->assertIsArray($result);
        $this->assertEquals("Skills - ", $result[0]);
        $this->assertEquals($expectedFile, $result[1]);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteDataRowsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier5 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 5, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        //When
        $this->skills->deleteDataRows($courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $trees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, []);
        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, []);
        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, []);
        $awardWildcard = Core::$systemDB->selectMultiple("award_wildcard", []);
        $this->assertEmpty($tiers);
        $this->assertEmpty($trees);
        $this->assertEmpty($skills);
        $this->assertEmpty($dependencies);
        $this->assertEmpty($skillDependencies);
        $this->assertEmpty($awardWildcard);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteDataRowsNoSkillsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        //When
        $this->skills->deleteDataRows($courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, []);
        $trees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $this->assertEmpty($tiers);
        $this->assertEmpty($trees);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
        
    }

    public function testDeleteDataRowsNoTiersSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        //When
        $this->skills->deleteDataRows($courseId);

        //Then
        $trees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $this->assertEmpty($trees);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteDataRowsNoTreeSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        //When
        $this->skills->deleteDataRows($courseId);

        //Then
        $trees = Core::$systemDB->selectMultiple(Skills::TABLE_TREES, []);
        $this->assertEmpty($trees);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteDataRowsInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        //When
        $this->skills->deleteDataRows($courseId + 1);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
            array("id" => $tier4, "seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500)
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10)
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteDataRowsNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Produção de Conteúdo Multimédia";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0]);
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        //When
        $this->skills->deleteDataRows(null);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
            array("id" => $tier4, "seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500)
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 8, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 1, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10)
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill9, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertDirectoryExists($skillsFolder);
        $this->assertDirectoryExists($rulesFolder);

        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testTierHasWildcardSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 1500]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "4", "reward" => 2000]);
        $tier5 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 5, "treeId" => $treeId, "tier" => "5", "reward" => 2500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "5", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill6, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill7]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill1, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill8]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier2, "isTier" => 1]);
        
        $dependency4 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $skill8, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency4, "normalSkillId" => $tier2, "isTier" => 0]);
        
        //When
        $hasWildcards1 = $this->skills->tierHasWildcards("1", $courseId);
        $hasWildcards2 = $this->skills->tierHasWildcards("2", $courseId);
        $hasWildcards3 = $this->skills->tierHasWildcards("3", $courseId);
        $hasWildcards4 = $this->skills->tierHasWildcards("4", $courseId);
        $hasWildcards5 = $this->skills->tierHasWildcards("5", $courseId);

        //Then
        $this->assertTrue($hasWildcards1);
        $this->assertTrue($hasWildcards2);
        $this->assertFalse($hasWildcards3);
        $this->assertFalse($hasWildcards4);
        $this->assertFalse($hasWildcards5);
    }

    public function testTierHasWildcardInexistingTier(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "3", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill6, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill7]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill1, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill2, "isTier" => 0]);

        //When
        $hasWildcards = $this->skills->tierHasWildcards("Potatoes", $courseId);

        //Then
        $this->assertFalse($hasWildcards);
    }

    public function testTierHasWildcardNullTier(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "3", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill6, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill7]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill1, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill2, "isTier" => 0]);

        //When
        $hasWildcards = $this->skills->tierHasWildcards(null, $courseId);

        //Then
        $this->assertFalse($hasWildcards);
    }

    public function testTierHasWildcardInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "3", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill6, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill7]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill1, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill2, "isTier" => 0]);

        //When
        $hasWildcards = $this->skills->tierHasWildcards("1", $courseId + 1);

        //Then
        $this->assertFalse($hasWildcards);
    }

    public function testTierHasWildcardNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Produção de Conteúdo Multimédia", "short" => "PCM", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        
        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "3", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "3", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "3", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill5]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill6, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $tier1, "isTier" => 1]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill7]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill1, "isTier" => 0]);
        Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill2, "isTier" => 0]);

        //When
        $hasWildcards = $this->skills->tierHasWildcards("1", null);

        //Then
        $this->assertFalse($hasWildcards);
    }

    public function testGetSkillsSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);
        
        
        //When
        $skills = $this->skills->getSkills($courseId);
        
        //Then
        $expectedSkills = array(
            array("id" => $skill1, "color" => "#92820a", "name" => "Pixel Art", "seqId" => 1, "xp" => 300, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "Wildcard"),
            array("id" => $skill2, "color" => "#2773ed", "name" => "Course Logo", "seqId" => 1, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill3, "color" => "#ff76bc", "name" => "Album Cover", "seqId" => 2, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill5, "color" => "#4617b5", "name" => "Podcast", "seqId" => 3, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill4, "color" => "#00c140", "name" => "Movie Poster", "seqId" => 4, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill6, "color" => "#c1004f", "name" => "Reporter", "seqId" => 5, "xp" => 150, "allActive" => 1, "isActive" => 0, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill7, "color" => "#b11d01", "name" => "Audiobook", "seqId" => 6, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill8, "color" => "#78ba50", "name" => "Radio Commercial", "seqId" => 7, "xp" => 150, "allActive" => 1, "isActive" => 1, "dependencies" => null, "dependenciesList" => array(), "description" => "", "tier" => "1"),
            array("id" => $skill9, "color" => "#00a8a8", "name" => "Looping GIF", "seqId" => 1, "xp" => 300, "allActive" => 1, "isActive" => 1, "dependencies" => "Wildcard + Album Cover ", "dependenciesList" => array(array("Wildcard", "Album Cover")), "description" => "", "tier" => "2"),
            array("id" => $skill10,"color" => "#008387", "name" => "Publicist",  "seqId" => 2, "xp" => 300, "allActive" => 0, "isActive" => 1, "dependencies" => "Course Logo + Radio Commercial |  Podcast + Reporter  ", "dependenciesList" => array(array("Course Logo", "Radio Commercial"), array("Podcast", "Reporter")), "description" => "", "tier" => "2")
        );
        $this->assertEquals($expectedSkills, $skills);
    }

    public function testGetSkillsNoSkills(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);
       
        //When
        $skills = $this->skills->getSkills($courseId);
        
        //Then
        $this->assertEmpty($skills);
    }

    public function testGetSkillsNoTree(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);

        //When
        $skills = $this->skills->getSkills($courseId);
        
        //Then
        $this->assertEmpty($skills);
    }

    public function testGetSkillsInexistingCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);
        
        
        //When
        $skills = $this->skills->getSkills($courseId + 1);

        //Then
        $this->assertEmpty($skills);
    }
    
    public function testGetSkillsNullCourse(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);
        $tier4 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 4, "treeId" => $treeId, "tier" => "Empty Tier", "reward" => 1500]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);
        
        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);
        
        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);
        
        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);
        
        
        //When
        $skills = $this->skills->getSkills(null);

        //Then
        $this->assertEmpty($skills);
    }

    public function testGetSkillDependenciesSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);


        //When
        $dependencies10 = $this->skills->getSkillDependencies($skill10);
        $dependencies9 = $this->skills->getSkillDependencies($skill9);

        //Then
        $expectedDependencies9 = array(
            "allActive" => 1,
            "dependencies" => array("Wildcard", "Album Cover")
        );
        $expectedDependencies10 = array(
            "allActive" => 0,
            "dependencies" => array("Course Logo", "Radio Commercial", "Podcast", "Reporter")
        );

        $this->assertEquals($expectedDependencies9, $dependencies9);
        $this->assertEquals($expectedDependencies10, $dependencies10);
    }

    public function testGetSkillDependenciesNoDependencies(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);


        //When
        $dependencies1 = $this->skills->getSkillDependencies($skill1);

        //Then
        $expectedDependencies1 = array(
            "allActive" => 1,
            "dependencies" => array()
        );

        $this->assertEquals($expectedDependencies1, $dependencies1);
    }

    public function testGetSkillDependenciesInexistingSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);


        //When
        $dependencies = $this->skills->getSkillDependencies($skill10 + 1);

        //Then
        $expectedDependencies = array(
            "allActive" => 1,
            "dependencies" => array()
        );

        $this->assertEquals($expectedDependencies, $dependencies);
    }

    public function testGetSkillDependenciesNullSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#92820a", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);


        //When
        $dependencies = $this->skills->getSkillDependencies(null);

        //Then
        $expectedDependencies = array(
            "allActive" => 1,
            "dependencies" => array()
        );

        $this->assertEquals($expectedDependencies, $dependencies);
    }

    public function testDeleteSkillSuccess(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);


        //When
        $this->skills->deleteSkill(array("id" => $skill4), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10),
            array("id" => $dependency3, "superSkillId" => $skill9),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteSkillWithNormalDependenciesFail(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);

        $this->expectOutputString("This skill is a dependency of others skills. You must remove them first.");

        //When
        $result = $this->skills->deleteSkill(array("id" => $skill8), $courseId);

        //Then
        $this->assertNull($result);

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10),
            array("id" => $dependency3, "superSkillId" => $skill9),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteSkillWithWildcardDependencies(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);

        //When
        $this->skills->deleteSkill(array("id" => $skill1), $courseId);

        //Then
        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10),
            array("id" => $dependency3, "superSkillId" => $skill9),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteSkillInexistingSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);

        //When
        $result = $this->skills->deleteSkill(array("id" => $skill10 + 1), $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10),
            array("id" => $dependency3, "superSkillId" => $skill9),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }

    public function testDeleteSkillNullSkill(){
        
        //Given
        $courseId = Core::$systemDB->insert("course", ["name" => "Multimedia Content Production", "short" => "MCP", "year" => "2019-2020", "color" => "#79bf43", "isActive" => 1, "isVisible" => 1]);
        $treeId = Core::$systemDB->insert(Skills::TABLE_TREES, ["course" => $courseId, "maxReward" => DEFAULT_MAX_TREE_XP]);
        $folder = COURSE_DATA_FOLDER . '/' . $courseId . '-' . "Multimedia Content Production";
        $skillsFolder = $folder . "/" . Skills::ID;
        $rulesFolder = $folder . "/rules";
        mkdir($folder);
        mkdir($skillsFolder);
        mkdir($rulesFolder);
        file_put_contents($rulesFolder . "/1 - skills.txt", "");

        $tier1 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300]);
        $tier2 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150]);
        $tier3 = Core::$systemDB->insert(Skills::TABLE_TIERS, ["seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300]);

        $skill1 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId]);
        $skill2 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill3 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill4 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill5 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill6 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0]);
        $skill7 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill8 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId]);
        $skill9 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId]);
        $skill10 = Core::$systemDB->insert(Skills::TABLE, ["seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId]);

        $dependency1 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency1 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0]);
        $skillDependency2 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0]);

        $dependency2 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill10]);
        $skillDependency3 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0]);
        $skillDependency4 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0]);

        $dependency3 = Core::$systemDB->insert(Skills::TABLE_SUPER_SKILLS, ["superSkillId" => $skill9]);
        $skillDependency5 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1]);
        $skillDependency6 = Core::$systemDB->insert(Skills::TABLE_DEPENDENCIES, ["dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0]);

        //When
        $result = $this->skills->deleteSkill(array("id" => null), $courseId);

        //Then

        $tiers = Core::$systemDB->selectMultiple(Skills::TABLE_TIERS, [], "*", "id");
        $expectedTiers = array(
            array("id" => $tier1, "seqId" => 1, "treeId" => $treeId, "tier" => "Wildcard", "reward" => 300),
            array("id" => $tier2, "seqId" => 2, "treeId" => $treeId, "tier" => "1", "reward" => 150),
            array("id" => $tier3, "seqId" => 3, "treeId" => $treeId, "tier" => "2", "reward" => 300),
        );

        $skills = Core::$systemDB->selectMultiple(Skills::TABLE, [], "*", "id");
        $expectedSkills = array(
            array("id" => $skill1, "seqId" => 1, "name" => "Pixel Art", "color" => "#ddaa00", "page" => null, "tier" => "Wildcard", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill2, "seqId" => 1, "name" => "Course Logo", "color" => "#2773ed", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill3, "seqId" => 2, "name" => "Album Cover", "color" => "#ff76bc", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill4, "seqId" => 4, "name" => "Movie Poster", "color" => "#00c140", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill5, "seqId" => 3, "name" => "Podcast", "color" => "#4617b5", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill6, "seqId" => 5, "name" => "Reporter", "color" => "#c1004f", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 0),
            array("id" => $skill7, "seqId" => 6, "name" => "Audiobook", "color" => "#b11d01", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill8, "seqId" => 7, "name" => "Radio Commercial", "color" => "#78ba50", "page" => null, "tier" => "1", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill9, "seqId" => 1, "name" => "Looping GIF", "color" => "#00a8a8", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
            array("id" => $skill10, "seqId" => 2, "name" => "Publicist", "color" => "#008387", "page" => null, "tier" => "2", "treeId" => $treeId, "isActive" => 1),
        );

        $dependencies = Core::$systemDB->selectMultiple(Skills::TABLE_SUPER_SKILLS, [], "*", "id");
        $expectedDependencies = array(
            array("id" => $dependency1, "superSkillId" => $skill10),
            array("id" => $dependency2, "superSkillId" => $skill10),
            array("id" => $dependency3, "superSkillId" => $skill9),
        );

        $skillDependencies = Core::$systemDB->selectMultiple(Skills::TABLE_DEPENDENCIES, [], "*", "dependencyId");
        $expectedSkillDependencies = array(
            array("dependencyId" => $dependency1, "normalSkillId" => $skill8, "isTier" => 0),
            array("dependencyId" => $dependency1, "normalSkillId" => $skill2, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill6, "isTier" => 0),
            array("dependencyId" => $dependency2, "normalSkillId" => $skill5, "isTier" => 0),
            array("dependencyId" => $dependency3, "normalSkillId" => $tier1, "isTier" => 1),
            array("dependencyId" => $dependency3, "normalSkillId" => $skill3, "isTier" => 0)
        );

        $this->assertEquals($expectedTiers, $tiers);
        $this->assertEquals($expectedSkills, $skills);
        $this->assertEquals($expectedDependencies, $dependencies);
        $this->assertEqualsCanonicalizing($expectedSkillDependencies, $skillDependencies);
        $this->assertFileExists($rulesFolder . "/1 - skills.txt");

        unlink($rulesFolder . "/1 - skills.txt");
        rmdir($skillsFolder);
        rmdir($rulesFolder);
        rmdir($folder);
    }
    
}