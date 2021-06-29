<?php
namespace App\Controllers;

use League\Plates\Engine;
use Delight\Auth\Auth;
use \Tamtamchik\SimpleFlash\Flash;



class Registration
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
        echo $this->templates->render('registration', ['flash' => $this->flash->display()]);
    }
    
    public function postHandler()
    {
        try {
            $userId = $this->auth->register($_POST['email'], $_POST['password']);
        
            $this->flash->success('We have signed up a new user with the ID ' . $userId);
            header('Location: /login');
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $this->flash->warning('Invalid email address');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->warning('Invalid password');
        }
        catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $this->flash->warning('User already exists');
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->warning('Too many requests');
        }

        echo $this->templates->render('registration', ['flash' => $this->flash->display()]);
            
    }

}