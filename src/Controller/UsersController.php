<?php
//src/Controller/UsersController.php

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;

class UsersController extends AppController
{
    public function initialize(){
        parent::initialize();
        //Ajoute l'action 'add' de ce controller a la liste des actions autorisées sans être connecté
        $this->Auth->allow(['add']);
    }

	public function index()
    {
    	$users = $this->Users->find()->order('pseudo');
        $this->set(compact('users'));
    }

    public function view($id, $user_id)
    {

        $user = $this->Users->get($id, [
            'contain' => ['Bookmarks.Artists']
        ]);

        $bookmarks = $this->Users->Bookmarks->find();

        /*Favorie en commun*/
        $query = $this->Users->Bookmarks->find();
        $query->select('artist_id')
        ->where(['user_id' => $user_id]);

        $common = $this->Users->Bookmarks->find();
        $common
        ->select('artist_id')
        ->distinct()
        ->where(['artist_id IN' => $query])
        ->andWhere(['user_id' => $id])
        ->group(['artist_id'])
        ->contain(['Artists'])
        ->all();

        /*Favori different*/
        $sql = $this->Users->Bookmarks->find();
        $sql->select('artist_id')
        ->where(['user_id' => $user_id]);

        $different = $this->Users->Bookmarks->find();
        $different
        ->contain(['Artists'])
        ->select('artist_id')
        ->distinct()
        ->where(['artist_id NOT IN' => $sql])
        ->andWhere(['user_id' => $id])
        ->group(['artist_id'])
        ->all();


        $this->set(compact('user', 'bookmarks', 'common', 'different'));

        /*->where(function ($exp, $q) {
        return $exp->in('country_id', ['AFG', 'USA', 'EST']);
        });*/
        # WHERE country_id IN ('AFG', 'USA', 'EST')

        //->where(['id IN' => $ids]);

        //SELECT DISTINCT artist_id FROM bookmarks WHERE artist_id IN ( SELECT artist_id FROM bookmarks WHERE user_id=2 ) AND user_id=1 GROUP BY user_id

        //SELECT DISTINCT artist_id FROM bookmarks WHERE artist_id NOT IN ( SELECT artist_id FROM bookmarks WHERE user_id=1 ) AND user_id=2

    }

    public function add()
    {
        $new = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $new = $this->Users->patchEntity($new, $this->request->getData());

            if ($this->Users->save($new)) {
                $this->Flash->success('Ok');

               return $this->redirect(['controller' => 'artists', 'action' => 'index']);
            }
            $this->Flash->error('Planté');
        }
        $this->set(compact('new'));
    }

    public function login(){
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error('Votre pseudo ou mot de passe est incorrect.');
        }
    }

    public function logout()
    {
        $this->Flash->success('À bientôt');
        $this->Auth->logout();
        return $this->redirect(['controller' => 'artists', 'action' => 'index']);

    }
}