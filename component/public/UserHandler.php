<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImplicitDataSender
 *
 * @author peska
 */
class UserHandler {
//put your code here
    private $userID;
    private $sessionID;

/**
 * checks for userID of existing user in sessions, then cookies
 * if not found, it creates a new user and saves his userID into the Sessions and cookies
 */
    function __construct() {
        session_start();

        if( isset($_SESSION["user"]) and intval($_SESSION["user"])!=0 ) {
        //everything is 	OK
            $this->userID = $_SESSION["user"];
            $this->sessionID = $_SESSION["session_no"];

        }else if( isset($_COOKIE["user"]) and intval($_COOKIE["user"])!=0 ) {
            //starting new session, session_no++, post updated cookie
                $cookie_expire = time()+60*60*24*30*24;
                if( isset($_COOKIE["session_no"])) {
                    $ses_no = $_COOKIE["session_no"];
                }else {
                    $ses_no = 0;
                }
                $ses_no++;
                $this->userID = $_COOKIE["user"];
                $_SESSION["user"] = $_COOKIE["user"];
                $_SESSION["session_no"] = $ses_no;
                $this->sessionID = $ses_no;

                setcookie("user", $_COOKIE["user"], $cookie_expire);
                setcookie("session_no", $ses_no, $cookie_expire);

            }else {
                $usertable = Config::$userTableName;

                //check whether user is a bot or a human
                $botFreeUser = true;
                $browser = $_SERVER["HTTP_USER_AGENT"];
                $botNames = Config::$botsAndCrawlersNames;
                foreach ($botNames as $name) {
                    if(stripos($browser, $name)!==false){
                        $botFreeUser = false;
                        break;
                    }
                }
                if($botFreeUser) {
                    $query = "insert into  `".$usertable."`
                        (`name`)
                        values
                        (\"Anonym ".Date("Y-m-d h:i:s").", browser:".$_SERVER["HTTP_USER_AGENT"]."\") ";

                    $database = ComponentDatabase::get_instance();
                    $qr = $database->executeQuery( $query );
                    $this->userID = $database->getInsertedId();

                    //starting new session, create cookie
                    $cookie_expire = time()+60*60*24*30*24;
                    $ses_no = 1;
                    $_SESSION["user"] = $this->userID;
                    $_SESSION["session_no"] = $ses_no;
                    $this->sessionID = $ses_no;

                    setcookie("user", $this->userID, $cookie_expire);
                    setcookie("session_no", $ses_no, $cookie_expire);
                }
            //echo "new user create";
            }
    }

    /**
     * getter for userID
     * @return <type> id of the current user
     */
    public function getUserID(){
        return $this->userID;
    }

    /**
     * getter for userID
     * @return <type> id of the current user
     */
    public function getSession(){
        return $this->sessionID;
    }

}
?>
