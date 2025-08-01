<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Core
 *
 * @author peska
 */
class ComponentCore {

    private static $userID;
    private static $sessionID;
    private static $baseDir = "./component";
    private static $baseDirEvents = "..";
    private static $componentLocation = "/component";

    /**
     * loads all necessary classes of the component
     * @param <type> $configName optional: name of the config file - if not specified, using Config.php
     */
    public static function loadCore($configName="Config.php", $basedir=""){
        //including config file
        if($basedir==""){
           $basedir= ComponentCore::$baseDir;
        }
       // echo $basedir."/config/".$configName;
        //including config file
        require_once($basedir."/config/".$configName);

        //including interfaces
        require_once($basedir."/interface/DatabaseInterface.php");
        require_once($basedir."/interface/ObjectRating.php");
        require_once($basedir."/interface/ObjectSimilarity.php");
        require_once($basedir."/interface/QueryHandler.php");
        require_once($basedir."/interface/QueryToDatabaseInteraction.php");
        require_once($basedir."/interface/UserSimilarity.php");
        require_once($basedir."/interface/UsersToObjectDataLookup.php");
        require_once($basedir."/interface/EventHandler.php");
        require_once($basedir."/interface/EventHandlerReturnedValue.php");

       //including abstract methods
        require_once($basedir."/abstract/AbstractMethod.php");
        require_once($basedir."/abstract/AbstractObjectRating.php");
        require_once($basedir."/abstract/AbstractObjectSimilarity.php");
        require_once($basedir."/abstract/AbstractQueryHandler.php");
        require_once($basedir."/abstract/AbstractUserSimilarity.php");
        require_once($basedir."/abstract/AbstractUsersToObjectDataLookup.php");

        /**
         * !!!Including database implementation!!! change here in case of changing DB
         */
        require_once($basedir."/objects/MySQLDatabase.php");

       //including objects
        require_once($basedir."/objects/Attribute.php");
        require_once($basedir."/objects/AttributeFactory.php");
        require_once($basedir."/objects/ObjectExpression.php");
        require_once($basedir."/objects/Error.php");
        require_once($basedir."/objects/Query.php");
        require_once($basedir."/objects/QueryResponse.php");
        require_once($basedir."/objects/UserExpression.php");
        require_once($basedir."/objects/ExtendedQueryToDatabaseInteraction.php");
        require_once($basedir."/objects/BasicQueryToDatabaseInteraction.php");

       /**
        * including evaulating methods: change here if you want to add another method
        */
        require_once($basedir."/methods/Aggregated.php");
        require_once($basedir."/methods/Dummy.php");
        require_once($basedir."/methods/Item_item_CF.php");
        require_once($basedir."/methods/VSM.php");
        require_once($basedir."/methods/External.php");
        require_once($basedir."/methods/Standard.php");
        require_once($basedir."/methods/standardnegpref.php");
        require_once($basedir."/methods/Attributes.php");
        require_once($basedir."/methods/Randomized.php");
        require_once($basedir."/methods/NRmse.php");
        require_once($basedir."/methods/PearsonCorrelation.php");

        //including public methods
        require_once($basedir."/public/BasicQueryHandler.php");
        require_once($basedir."/public/ExtendedQueryHandler.php");
        require_once($basedir."/public/ComplexQueryHandlerReverse.php");

        require_once($basedir."/public/ErrorLog.php");
        require_once($basedir."/public/ComponentInfo.php");
        require_once($basedir."/public/UserHandler.php");
        require_once($basedir."/public/ExplicitEventHandler.php");

        ComponentCore::createUser();
    }

/**
 * find for the userID of the existing user or creates a new one
 */
    private static function createUser() {
        $userHandler = new UserHandler();
        ComponentCore::$userID = $userHandler->getUserID();
        ComponentCore::$sessionID = $userHandler->getSession();
    }

    /**
     * getter for the userID
     * @return <type> the id of the user
     */
    public static function getUserId() {
        return ComponentCore::$userID ;
    }

    /**
     * loads java scripts using for gathering User data
     * @param <type> $objectID id of the current object (i.e. showing single objects detail page) - if any
     */
    public static function loadJavaScripts($objectID="null", $pageID="index", $pageType = "index"){
        $uid = ComponentCore::$userID;
        $sessionid = ComponentCore::$sessionID;
        $basicPath = ComponentCore::$componentLocation;
        echo "
            <script  type=\"text/javascript\" language=\"javascript\">
                var userID = ".$uid." ;
                var objectID = ".$objectID.";
                var sessionID = ".$sessionid.";
                var pageID = \"".$pageID."\";
                var pageType = \"".$pageType."\";
            </script>
        ";
        echo "
            <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$basicPath."/javascript/storeTrace1.js\"></script>  
            <script type=\"text/javascript\" language=\"javascript\" src=\"".$basicPath."/javascript/storeFunctions1.js\"></script>
            <script type=\"text/javascript\" language=\"javascript\" src=\"".$basicPath."/javascript/ratingHandlers.js\"></script>
            <script type=\"text/javascript\" language=\"javascript\" src=\"".$basicPath."/javascript/xmlhttp_routines.js\"></script>
        ";

    }

/**
 * load component classes necessary for saving an event
 * this method is used by the Component itself only
 * @param <type> $configName optional: name of the config file - if not specified, using Config.php
 */
    public static function loadCoreEvents($configName="Config.php", $basedir=""){
        //including config file
        if($basedir==""){
           $basedir= ComponentCore::$baseDirEvents;
        }
        //echo $basedir."/config/".$configName;
        require_once($basedir."/config/".$configName);

        //including interfaces
        require_once($basedir."/interface/DatabaseInterface.php");
        require_once($basedir."/interface/EventHandler.php");
        require_once($basedir."/interface/EventHandlerReturnedValue.php");

        /**
         * !!!Including database implementation!!! change here in case of changing DB
         */
        require_once($basedir."/objects/MySQLDatabase.php");
        require_once($basedir."/objects/MySQLIFDatabase.php");

       //including objects
        require_once($basedir."/objects/AggregatedDataEvent.php");
        require_once($basedir."/objects/ExplicitUserDataEvent.php");
        require_once($basedir."/objects/ImplicitUserDataEvent.php");
        require_once($basedir."/objects/TestingEvent.php");
        require_once($basedir."/objects/QueryResponse.php");

        //including public methods
        require_once($basedir."/public/AggregatedEventHandler.php");
        require_once($basedir."/public/ExplicitEventHandler.php");
        require_once($basedir."/public/ImplicitEventHandler.php");
        require_once($basedir."/public/TestingEventHandler.php");
    }
}
?>
