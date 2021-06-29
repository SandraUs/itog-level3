<?php
namespace App\Controllers;

use League\Plates\Engine;
use Delight\Auth\Auth;
use \Tamtamchik\SimpleFlash\Flash;
use App\Model\Users as UsersData;


class Users
{
    private $templates;
    public function __construct(Engine $engine, Auth $auth, Flash $flash, UsersData $user)
    {
        $this->templates = $engine;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->user = $user;
        
    }
    
    public function index()
    { 
        if ($this->auth->isLoggedIn() || $this->auth->isRemembered()){
            echo $this->templates->render('users', ['flash' => $this->flash->display(), 'auth' => $this->auth, 'users' => $this->user->getAllUsers()]);
        } else {
            $this->flash->warning('User is not logged in');
            header('Location: /login');
        }
    }

    public function logout()
    {
        try {
            $this->auth->logOutEverywhere();
            $this->flash->warning('User logged out');
            header('Location: /login');
        }
        catch (\Delight\Auth\NotLoggedInException $e) {
            $this->flash->warning('Not logged in');
            header('Location: /login');
        }
    }

    public function createPostHandler()
    {
        try {
            $userId = $this->auth->register($_POST['email'], $_POST['password']);
                
                if ($userId){//если userId создан, то добавляем остальные данные в таблицы

                    //добавляем данные в таблицу users_info
                    $this->user->insert('users_info', 
                    [
                        'user_id',
                        'name',
                        'job_title',
                        'phone',
                        'address',
                        'status',
                        'avatar',
                    ],
                    [
                        'user_id' => $userId,
                        'name' => $_POST['name'],
                        'job_title' => $_POST['job_title'],
                        'phone' => $_POST['phone'],
                        'address' => $_POST['address'],
                        'status' => $_POST['status'],
                        'avatar' => $this->user->getAvatar($userId, $_FILES['avatar']),
                    ]);

                    //добавляем данные в таблицу users_links

                    $this->user->insert('users_links', 
                    [
                        'user_id',
                        'vk',
                        'telegram',
                        'instagram'
                    ],
                    [
                        'user_id' => $userId,
                        'vk' => $_POST['vk'],
                        'telegram' => $_POST['telegram'],
                        'instagram' => $_POST['instagram']
                    ]);
                }


                $this->flash->success('We have signed up a new user with the ID ' . $userId);
                header('Location: /');
                exit;
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
        
            echo $this->templates->render('create', ['flash' => $this->flash->display(), 'auth' => $this->auth, 'name' => $_POST['name'], 'job_title' => $_POST['job_title'], 'phone' => $_POST['phone'], 'address' => $_POST['address'], 'status' => $_POST['status'], 'vk' => $_POST['vk'], 'telegram' => $_POST['telegram'], 'instagram' => $_POST['instagram'], 'email' => $_POST['email']]);
    }


    public function showFormCreate()
    {
        if(!$this->auth->hasRole(\Delight\Auth\Role::ADMIN)){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }

        if ($this->auth->isLoggedIn() || $this->auth->isRemembered()){
            if ($this->auth->hasRole(\Delight\Auth\Role::ADMIN)) {
                echo $this->templates->render('create', ['auth' => $this->auth]);
                exit;
            }else {
                $this->flash->warning('User is not admin');
                header('Location: /');
                exit;
            }
        } else {
                $this->flash->warning('User is not logged in');
                header('Location: /login');
                
        }
       
    }

    public function editPostHandler($id)
    {
        $this->user->editInfo($id);
        $userInfo = $this->user->getById($id);
        echo $this->templates->render('edit', ['auth' => $this->auth, 'user' => $userInfo]);
    }
    
    public function editShowForm($id)
    {

        if(!($this->auth->getUserId() == $id || $this->auth->hasRole(\Delight\Auth\Role::ADMIN))){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }
        
        $userInfo = $this->user->getById($id);
        echo $this->templates->render('edit', ['auth' => $this->auth, 'user' => $userInfo]);
    }

    public function securityPostHandler($id)
    {
        try {
            $this->auth->changePassword($_POST['oldPassword'], $_POST['newPassword']);
        
            $this->flash->success('Password has been changed');
        }
        catch (\Delight\Auth\NotLoggedInException $e) {
            $this->flash->warning('Not logged in');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->warning('Invalid password(s)');
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->warning('Too many requests');
        }
        
        $userInfo = $this->user->getEmailById($id);
        echo $this->templates->render('security', ['auth' => $this->auth, 'user' => $userInfo, 'flash' => $this->flash->display()]);
    }

    public function securityShowForm($id)
    {
        if(!($this->auth->getUserId() == $id || $this->auth->hasRole(\Delight\Auth\Role::ADMIN))){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }
        
        $userInfo = $this->user->getEmailById($id);
        echo $this->templates->render('security', ['auth' => $this->auth, 'user' => $userInfo, 'flash' => $this->flash->display()]);

        
    }

    public function statusPostHandler($id)
    {
        $this->user->setStatus($id);
        
        $userInfo = $this->user->getOneUser($id);
        
        $statuses = [
            'online' => 'Онлайн',
            'dont_disturb' => 'Не беспокоить',
            'out' => 'Отошел'
        ];

        echo $this->templates->render('status', ['auth' => $this->auth, 'user' => $userInfo, 'statuses'=> $statuses, 'flash' => $this->flash->display()]);
    }

    public function statusShowForm($id)
    {
        if(!($this->auth->getUserId() == $id || $this->auth->hasRole(\Delight\Auth\Role::ADMIN))){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }

        $userInfo = $this->user->getOneUser($id);
        
        $statuses = [
            'online' => 'Онлайн',
            'dont_disturb' => 'Не беспокоить',
            'out' => 'Отошел'
        ];

        echo $this->templates->render('status', ['auth' => $this->auth, 'user' => $userInfo, 'statuses'=> $statuses, 'flash' => $this->flash->display()]);
        
    }
    
    public function mediaPostHandler($id)
    {
        $userInfo = $this->user->getOneUser($id);
        
        $this->user->updateAvatarById(
            'users_info',
            [
                'avatar'
            ],
            [
                'id' => $userInfo['user_id'],
                'avatar' => $this->user->getAvatar($userInfo['user_id'], $_FILES['avatar']),
            ]
        );
        
        $userNewInfo = $this->user->getOneUser($id);

        echo $this->templates->render('media', ['auth' => $this->auth, 'user' => $userNewInfo, 'flash' => $this->flash->display()]);
    }

    public function mediaShowForm($id)
    {
        if(!($this->auth->getUserId() == $id || $this->auth->hasRole(\Delight\Auth\Role::ADMIN))){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }

        $userInfo = $this->user->getOneUser($id);

        echo $this->templates->render('media', ['auth' => $this->auth, 'user' => $userInfo, 'flash' => $this->flash->display()]);
    }

    public function delete($id)
    {
        if(!($this->auth->getUserId() == $id || $this->auth->hasRole(\Delight\Auth\Role::ADMIN))){
            $this->flash->warning('Access denied');
            header('Location: /');
            exit;
        }

        $this->user->delete($id);
        
        if($this->auth->hasRole(\Delight\Auth\Role::ADMIN)){
            $this->flash->success('User deleted');
            header('Location: /');
            exit;
        } else {
            $this->auth->logOut();
            $this->flash->success('User deleted');
            header('Location: /login');
            exit;
        }
    }
}