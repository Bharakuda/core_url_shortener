<?php


namespace AppBundle\Service;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UserBundle\Entity\User;

class CreateAdminUser
{
    public function createAdmin(){
        $admin = new User();
        $admin->setUsername('Admin');
        $admin->setPassword('');
        $admin->setRoles(array('ROLE_ADMIN'));
        $em = $this->getDo

        $em = $this->getDoctrine()->getManager();
        $em->persist($admin);
        $em->flush();
    }

}