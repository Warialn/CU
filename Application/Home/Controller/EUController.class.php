<?php
namespace Home\Controller;
use Think\Controller;
class EUController extends Controller {
    public function index(){
       Layout('Layout/layout');
       $this->display();
    }
}