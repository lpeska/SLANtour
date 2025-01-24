<?php


class AdminModul {

    const GROUP_BOF = "bof";
    const GROUP_WEB = "web";
    const GROUP_ADMIN = "admin";
    const GROUP_OTHERS = "others";
    const GROUP_HIDDEN = "hidden";

    public static $GROUP_LIST = array(self::GROUP_BOF, self::GROUP_WEB, self::GROUP_ADMIN, self::GROUP_OTHERS, self::GROUP_HIDDEN);
    public static $GROUP_LIST_VISIBLE = array(self::GROUP_BOF, self::GROUP_WEB, self::GROUP_ADMIN, self::GROUP_OTHERS);

    public $id;
    public $url;
    public $name;
    public $group;

    function __construct($id, $name, $url, $group)
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->group = $group;
    }


}