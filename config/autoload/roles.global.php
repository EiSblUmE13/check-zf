<?php


return array(
	 'guest' => array('view','show','list','Application\Index','Application\Ajax')
	,'user' => array()
	,'employee' => array('create','edit','remove','Application\News','Application\Document','Application\Media')
	,'editor' => array('publish','unpublish','Application\User')
	,'chiefeditor' => array()
	,'clientmanager' => array('Application\Client')
	,'clientleader' => array()
	,'admin' => array('Application\Developer')
	,'developer' => array()
	,'urml' => array()
);