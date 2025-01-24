<?php

class MessageManager
{
    private static $messages;

    public static function addDbError($text, $element = null)
    {
        MessageManager::addMessage(Message::TYPE_DB_ERROR, new Message($text, $element, Message::TYPE_DB_ERROR));
    }

    public static function hasDbError()
    {
        return MessageManager::hasMessage(Message::TYPE_DB_ERROR);
    }

    public static function getAllDbError()
    {
        return MessageManager::getAllMesage(Message::TYPE_DB_ERROR);
    }

    public static function addError($text, $element = null)
    {
        MessageManager::addMessage(Message::TYPE_ERROR, new Message($text, $element, Message::TYPE_ERROR));
    }

    public static function hasError()
    {
        return MessageManager::hasMessage(Message::TYPE_ERROR);
    }

    public static function getAllError()
    {
        return MessageManager::getAllMesage(Message::TYPE_ERROR);
    }

    public static function addSuccess($text, $element = null)
    {
        MessageManager::addMessage(Message::TYPE_SUCCESS, new Message($text, $element, Message::TYPE_SUCCESS));
    }

    public static function hasSuccess()
    {
        return MessageManager::hasMessage(Message::TYPE_SUCCESS);
    }

    public static function getAllSuccess()
    {
        return MessageManager::getAllMesage(Message::TYPE_SUCCESS);
    }

    public static function addWarning($text, $element = null)
    {
        MessageManager::addMessage(Message::TYPE_WARNING, new Message($text, $element, Message::TYPE_WARNING));
    }

    public static function hasWarning()
    {
        return MessageManager::hasMessage(Message::TYPE_WARNING);
    }

    public static function getAllWarning()
    {
        return MessageManager::getAllMesage(Message::TYPE_WARNING);
    }

    public static function addInfo($text, $element = null)
    {
        MessageManager::addMessage(Message::TYPE_INFO, new Message($text, $element, Message::TYPE_INFO));
    }

    public static function hasInfo()
    {
        return MessageManager::hasMessage(Message::TYPE_INFO);
    }

    public static function getAllInfo()
    {
        return MessageManager::getAllMesage(Message::TYPE_INFO);
    }

    private static function addMessage($type, $message)
    {
        MessageManager::$messages[$type][] = $message;
    }

    private static function hasMessage($type)
    {
        if (empty(MessageManager::$messages[$type]))
            return false;

        return true;
    }

    private static function getAllMesage($type)
    {
        return MessageManager::$messages[$type];
    }
}

class Message
{

    /**
     *
     * @var String text zpravy
     */
    public $text;

    /**
     *
     * @var int typ zpravy - jedna z moznosti Message::$TYPE_ERROR, Message::$TYPE_SUCCESS, Message::$TYPE_WARNING, Message::$TYPE_INFO
     */
    public $type;

    const TYPE_DB_ERROR = "database-error";
    const TYPE_ERROR = "error";
    const TYPE_SUCCESS = "success";
    const TYPE_WARNING = "warning";
    const TYPE_INFO = "info";

    function __construct($text, $type)
    {
        $this->text = $text;
        $this->type = $type;
    }

}

?>