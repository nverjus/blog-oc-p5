<?php
namespace Blog\Controller;

use NV\MiniFram\Controller;
use NV\MiniFram\Request;
use Blog\Entity\User;
use Blog\Form\LoginFormBuilder;
use Blog\Form\UserFormBuilder;
use Blog\Form\DeleteFormBuilder;
use Blog\Form\ValidateFormBuilder;

class SecurityController extends Controller
{
    public function executeLogIn(Request $request)
    {
        $user = new User([]);

        if ($request->getMethod() == 'POST') {
            $user->setEmail($request->postData('email'));
            $user->setClearPassword($request->postData('clearPassword'));
        }

        $formBuilder = new LoginFormBuilder($user);
        $formBuilder->build();
        $form = $formBuilder->getForm();

        if ($request->getMethod() == 'POST' && $form->isValid()) {
            $user = $this->manager->getRepository('User')->findByEmail($request->postData('email'));
            if ($user !== null) {
                $user->setClearPassword($request->postData('clearPassword'));
                if ($user->verifyPassword()) {
                    if ($user->getIsValidated()) {
                        $user->connect();
                        $this->app->getSession()->setFlash('Bienvenue '.$user->getName());
                        $this->app->getResponse()->redirect('/admin-posts');
                    }
                    $this->app->getSession()->setFlash('Compte en attente de validation');
                }
            }
            if (!$this->app->getSession()->attributeExists('flash')) {
                $this->app->getSession()->setFlash('Identifiants incorrects');
            }
        }

        return $this->render('Security/login.html.twig', array('form' => $form->createView()));
    }

    public function executeLogOut(Request $request)
    {
        $this->app->getSession()->deleteAttribute('user');
        $this->app->getResponse()->redirect('/');
    }

    public function executeSignIn(Request $request)
    {
        $user = new User([]);
        if ($request->getMethod() == 'POST') {
            $user->setName($request->postData('name'));
            $user->setPassword($request->postData('password'));
            $user->setPasswordConfirmation($request->postData('passwordConfirmation'));
            $user->setEmail($request->postData('email'));
        }

        $builder = new UserFormBuilder($user);
        $builder->build();
        $form = $builder->getForm();

        if ($request->getMethod() == 'POST' && $form->isValid()) {
            if ($this->manager->getRepository('User')->findByEmail($request->postData('email')) == null) {
                if ($user->passwordMatch()) {
                    $user->hashPassword();
                    $this->manager->getRepository('User')->save($user);
                    $this->app->getSession()->setFlash('Compte créé, en attente de validation');
                    $this->app->getResponse()->redirect('/blog');
                }
                $this->app->getSession()->setFlash('Les mot de passe ne correspondent pas');
            }
            if (!$this->app->getSession()->attributeExists('flash')) {
                $this->app->getSession()->setFlash('Adresse email déjà utilisée');
            }
        }

        return $this->render('Security/signin.html.twig', array('form' => $form->createView()));
    }

    public function executeAdminUsers(Request $request)
    {
        if (!$this->isGranted('admin')) {
            $this->app->getSession()->setFlash('Vous n\'avez pas les droits nécessaire pour aller sur cette page');
            $this->app->getResponse()->redirect('/blog');
        }

        $usersToValidate = $this->manager->getRepository('User')->findNotValidated();

        $usersValidated = $this->manager->getRepository('User')->findValidated();


        $deleteBuilder = new DeleteFormBuilder;
        $deleteBuilder->build();
        $deleteForm = $deleteBuilder->getForm();

        $validateBuilder = new ValidateFormBuilder;
        $validateBuilder->build();
        $validateForm = $validateBuilder->getForm();

        return $this->render('Security/adminUsers.html.twig', array(
          'usersToValidate' => $usersToValidate,
          'usersValidated' => $usersValidated,
          'deleteForm' => $deleteForm->createView(),
          'validateForm' => $validateForm->createView()
        ));
    }
}
