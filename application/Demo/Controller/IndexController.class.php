<?php
namespace Demo\Controller;
use Common\Controller\HomebaseController;

class IndexController extends HomebaseController{
	
	function index(){

	    redirect('http://'.$_SERVER['HTTP_HOST'].'/admin');
	}
}