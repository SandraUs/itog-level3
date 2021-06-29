<?php
namespace App\Controllers;

use League\Plates\Engine;
use Delight\Auth\Auth;
use \Tamtamchik\SimpleFlash\Flash;

class Login
{
    
    private $templates, $auth, $flash;
    public function __construct(Engine $engine, Auth $auth, Flash $flash)
    {
        $this->templates = $engine;
        $this->auth = $auth;
        $this->flash = $flash;        
    }
    
    public function showForm()
    {
        echo $this->templates->render('login', ['flash' => $this->flash->display()]);
    }

    public function postHandler()
    {
        if ($_POST['remember'] == "on") {
            // keep logged in for one year
            $rememberDuration = (int) (60 * 60 * 24 * 365.25);
        }
        else {
            // do not keep logged in after session ends
            $rememberDuration = null;
        }


        try {
            $this->auth->login($_POST['email'], $_POST['password'], $rememberDuration);
        
            $this->flash->success('User is logged in');
            
            header('Location: /');
            exit;
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $this->flash->warning('Wrong email address');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->warning('Wrong password');
        }
        catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $this->flash->warning('Email not verified');
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->warning('Too many requests');
        }
    
        echo $this->templates->render('login', ['flash' => $this->flash->display()]);
    }
    
}

?>