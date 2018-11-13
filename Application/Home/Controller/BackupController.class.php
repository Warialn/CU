<?php
namespace Home\Controller;
use Think\Controller;
class BackupController extends Controller {
    public function index(){
       Layout('Layout/layout');
       $this->display();
    }
}